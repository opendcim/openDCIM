<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

  if(!$person->BulkOperations){
    header('Location: '.redirect());
    exit;
  }

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Bulk Container/Datacenter Importer");

  $content = "";

  if ( isset( $_FILES['inputfile'] )) {
    //
    //  File name has been specified, so we're uploading a new file.  Need to simply make sure
    //  that it's at least a valid file that PHPExcel can open and that we can move it to
    //  the /tmp directory.  We'll set the filename as a session variable so that we can keep track
    //  of it more simply as we move from stage to stage.
    //
    $target_dir = '/tmp/';
    $targetFile = $target_dir . basename($_FILES['inputfile']['name']);

    try {
      $inFileType = PHPExcel_IOFactory::identify($_FILES['inputfile']['tmp_name']);
      $objReader = PHPExcel_IOFactory::createReader($inFileType);
      $objXL = $objReader->load($_FILES['inputfile']['tmp_name']);
    } catch (Exception $e) {
      die("Error opening file: ".$e->getMessage());
    }

    move_uploaded_file( $_FILES['inputfile']['tmp_name'], $targetFile );

    $_SESSION['inputfile'] = $targetFile;

    echo "<meta http-equiv='refresh' content='0; url=" . $_SERVER['SCRIPT_NAME'] . "?stage=headers'>";
    exit;
  } elseif ( isset( $_REQUEST['stage'] ) && $_REQUEST['stage'] == 'headers' ) {
    //
    //  File has been moved, so now we're ready to map out the columns to fields for processing.
    //  If you don't want to have to map every time, you can simply make your spreadsheet columns
    //  appear in the same order as they show up on this page.  That way you can just click right
    //  on to the next stage, which is validation.
    //

    // Make sure that we can still access the file
    $targetFile = $_SESSION['inputfile'];
    try {
      $inFileType = PHPExcel_IOFactory::identify($targetFile);
      $objReader = PHPExcel_IOFactory::createReader($inFileType);
      $objXL = $objReader->load($targetFile);
    } catch (Exception $e) {
      die("Error opening file: ".$e->getMessage());
    }

    // We're good, so now get the top row so that we can map it out to fields

    $content = "<h3>" . __("Pick the appropriate column header (line 1) for each field name listed below." ) . "</h3>";
    $content .= "<h3>" . __("Mouse over each field for help text.") . "</h3>";

    $content .= '<form method="POST">
                    <input type="hidden" name="stage" value="process">
                    <div class="table">';

    // Find out how many columns are in the spreadsheet so that we can load them as possible values for the fields
    // and we don't really care how many rows there are at this point.
    $sheet = $objXL->getSheet(0);
    $highestColumn = $sheet->getHighestColumn();

    $headerList = $sheet->rangeToArray('A1:' . $highestColumn . '1' );

    $fieldList = array( "None" );
    foreach( $headerList[0] as $fName ) {
      $fieldList[] = $fName;
    }

    $fieldNum = 1;

    foreach ( array( "DataCenter"=>"The unique name of the data center to be added to the openDCIM database.  If it does not already exist, a basic record will be added.", "Container"=>"Optionally, the unique name of the Container that the Data Center is a member of.  If it does not exist, it will be created.",  "Zone"=>"The name of an Zone for this record.  If it does not exist, it will be created with no coordinates, but attached to the given Data Center.  The combination of Data Center + Zone must be unique.  Optional.", "Row"=>"The name of a row to add to the database.  The combination of Data Center + Zone + Row (or Data Center + Row) must be unique.  Optional." ) as $fieldName=>$helpText ) {
      $content .= '<div>
                    <div><span title="' . __($helpText) . '">' . __($fieldName) . '</span>: </div><div><select name="' . $fieldName . '">';
      for ( $n = 0; $n < sizeof( $fieldList ); $n++ ) {
        if ( $n == $fieldNum )
            $selected = "SELECTED";
        else
            $selected = "";

        $content .= "<option value=$n $selected>$fieldList[$n]</option>\n";
      }

      $content .= '</select>
                    </div>
                  </div>';

      $fieldNum++;
    }

    $content .= "<div><div></div><div><input type='submit' value='" . __("Process") . "' name='submit'></div></div>";

    $content .= '</form>
        </div>';
  } elseif ( isset($_REQUEST['stage']) && $_REQUEST['stage'] == 'process' ) {
    // This is much simpler than the bulk device import, so there is no Validate stage
    // so instead we just ask for what the key value fields are and then try to make matches.
    // Any that we can't find a unique match for get printed out as errors.
    //

    $targetFile = $_SESSION['inputfile'];
    try {
      $inFileType = PHPExcel_IOFactory::identify($targetFile);
      $objReader = PHPExcel_IOFactory::createReader($inFileType);
      $objXL = $objReader->load($targetFile);
    } catch (Exception $e) {
      die("Error opening file: ".$e->getMessage());
    }

    // Start off with the assumption that we have zero processing errors
    $errors = false;

    $sheet = $objXL->getSheet(0);
    $highestRow = $sheet->getHighestRow();

    // Also make sure we start with an empty string to display
    $content = "";
    $fields = array( "DataCenter", "Container", "Zone", "Row" );

    for ( $n = 2; $n <= $highestRow; $n++ ) { 
      $rowError = false;

      // Load up the $row[] array with the values according to the mapping supplied by the user
      foreach( $fields as $fname ) {
        $addr = chr( 64 + $_REQUEST[$fname]);
        if ( $_REQUEST[$fname] != 0 ) {
          $row[$fname] = sanitize($sheet->getCell( $addr . $n )->getValue());
        }
      }

      // Stop processing once you hit the first blank cell for 'Location' - some Excel files will return $sheet->getHighestRow() way past the end of any meaningful data
      if ( $row["DataCenter"] == "" ) {
        break;
      }

      $ContainerID = 0;

      // First, check the Container existence, and add, if needed
      if ( $row["Container"] != "" )  {
        $st = $dbh->prepare("select count(*) as TotalHits, ContainerID from fac_Container where UCASE(Name)=UCASE(:Name)");
        $st->execute( array( ":Name"=>$row["Container"]));
        if ( ! $val = $st->fetch() ) {
          $info = $dbh->errorInfo();
          error_log( "PDO Error: {$info[2]}");
        }
        
        if ( $val["TotalHits"] != 1 ) {
          $st = $dbh->prepare("insert into fac_Container set Name=:Name");
          $st->execute( array( ":Name"=>$row["Container"]));
          $ContainerID = $dbh->lastInsertID();
          $content .= "<li>Container Added: $ContainerID - " . $row["Container"] . "</li>\n";
        } else {
          $ContainerID = $val["ContainerID"];
        }
      }

      /*
       *
       *  Section for looking up the DataCenter and setting the true DataCenterID
       *
       */
      $st = $dbh->prepare( "select count(DataCenterID) as TotalMatches, DataCenterID from fac_DataCenter where ucase(Name)=ucase(:DataCenter)" );
      $st->execute( array( ":DataCenter"=>$row["DataCenter"] ));
      if ( ! $val = $st->fetch() ) {
        $info = $dbh->errorInfo();
        error_log( "PDO Error: {$info[2]}");
      }

      if ( $val["TotalMatches"] == 1 ) {
        $DataCenterID = $val["DataCenterID"];
      } else {
        $st = $dbh->prepare("insert into fac_DataCenter set ContainerID=:ContainerID, Name=:Name");
        $st->execute( array( ":ContainerID"=>$ContainerID, ":Name"=>$row["DataCenter"]));
        $DataCenterID = $dbh->lastInsertID();
        $content .= "<li>DataCenter Added: $DataCenterID - " . $row["DataCenter"] . "</li>\n";
      }

      /*
       *
       *  Section for looking up the ZoneID and setting the true ZoneID
       *
       */
      
      $ZoneID = 0;

      // Zone is optional, so only do this if we have a non-empty cell
      if ( $row["Zone"] != "" && $DataCenterID > 0 ) {
        $st = $dbh->prepare( "select count(ZoneID) as TotalMatches, ZoneID from fac_Zone where DataCenterID=:DataCenterID and ucase(Description)=ucase(:Zone)" );
        $st->execute( array( ":DataCenterID"=>$DataCenterID, ":Zone"=>$row["Zone"] ));
        if ( ! $val = $st->fetch() ) {
          $info = $dbh->errorInfo();
          error_log( "PDO Error: {$info[2]}");
        }
      }

      if ( $row["Zone"]!="" && $val["TotalMatches"] == 1 ) {
        $ZoneID = $val["ZoneID"];
      } elseif ($row["Zone"]!="" ) {
        $st = $dbh->prepare( "insert into fac_Zone set DataCenterID=:DataCenterID, Description=:Description" );
        $st->execute( array( ":DataCenterID"=>$DataCenterID, ":Description"=>$row["Zone"] ));
        $ZoneID = $dbh->lastInsertID();
        $content .= "<li>Data Center + Zone added:  $ZoneID -  " . $row["DataCenter"] . " - " . $row["Zone"] . "</li>\n";
      }

      /*
       *
       *  Section for looking up the RowID and adding, if not exists
       *
       */
      
      // Rows are also optional
      if ( $row["Row"] != "" && $DataCenterID > 0 ) {
        $st = $dbh->prepare( "select count(RowID) as TotalMatches, CabRowID from fac_CabRow where DataCenterID=:DataCenterID and ZoneID=:ZoneID and ucase(Name)=ucase(:Name)" );
        $st->execute( array( ":DataCenterID"=>$DataCenterID, ":ZoneID"=>$ZoneID, ":Name"=>$row["Row"] ));
        if ( ! $val = $st->fetch() ) {
          $info = $dbh->errorInfo();
          error_log( "PDO Error: {$info[2]}");
        }
      }

      if ( $row["Row"]!="" && $val["TotalMatches"] == 1 ) {
        $ZoneID = $val["CabRowID"];
      } elseif ($row["Zone"]!="" ) {
        $st = $dbh->prepare( "insert into fac_CabRow set DataCenterID=:DataCenterID, ZoneID=:ZoneID, Name=:Name" );
        $st->execute( array( ":DataCenterID"=>$DataCenterID, ":ZoneID"=>$ZoneID, ":Name"=>$row["Row"] ));
        $CabRowID = $dbh->lastInsertID();
        $content .= "<li>Data Center + Zone + Row added:  $CabRowID -  " . $row["DataCenter"] . " - " . $row["Zone"] . " - " . $row["Row"] . "</li>\n";
      }


    }

    if ( ! $errors ) {
      $content = __("All records imported successfully.") . "<ul>" . $content . "</ul>";
    } else {
      $content = __("At least one error was encountered processing the file.  Please see below.") . "<ul>" . $content . "</ul>";
    }
  } else {
    //
    //  No parameters were passed with the URL, so this is the top level, where
    //  we need to ask for the user to specify a file to upload.
    //
    $content = '<form method="POST" ENCTYPE="multipart/form-data">';
    $content .= '<div class="table">
                  <div>
                    <div>' . __("Select file to upload:") . '
                    <input type="file" name="inputfile" id="inputfile">
                    </div>
                  </div>
                  <div>
                    <div>
                    <input type="submit" value="Upload" name="submit">
                    </div>
                  </div>
                  </div>
                  </form>
                  </div>';

  }


  //
  //  Render the page with the main section being whatever has been loaded into the
  //  variable $content - every stage spills out to here other than the file upload
  //
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<div class="center"><div>

<?php
  echo $content;
?>

<!-- CONTENT GOES HERE -->



</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
