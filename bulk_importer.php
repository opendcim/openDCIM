<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');
  require_once('PHPExcel/PHPExcel/IOFactory.php');

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Bulk Importer");

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

    echo "<meta http-equiv='refresh' content='0; url=" . $_SERVER['PHP_SELF'] . "?stage=headers'>";
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

    $content .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">
                    <input type="hidden" name="stage" value="validate">
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

    foreach ( array( "DataCenterID", "Cabinet", "Position", "Label", "Height", "Manufacturer", "Model", "Hostname", "SerialNo", "AssetTag", "ESX", "InstallDate", "Reservation", "Owner", "PrimaryContact" ) as $fieldName ) {
      $content .= '<div>
                    <div>' . __($fieldName) . ': </div><div><select name="' . $fieldName . '">';
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

    $content .= "<div><div></div><div><input type='submit' value='" . __("Validate") . "' name='submit'></div></div>";

    $content .= '</form>
        </div>';
  } elseif ( isset($_REQUEST['stage']) && $_REQUEST['stage'] == 'validate' ) {
    // Certain fields we are going to require that the values exist in the db already
    //
    // Data Center
    // Cabinet
    // Manufacturer
    // Model
    // Owner (Department)
    // Primary Contact
    //
    // Everything else is just meta data
    //

    // Once again, open the uploaded Excel file.  Will possibly move to a function to eliminate repetition.
    $targetFile = $_SESSION['inputfile'];
    try {
      $inFileType = PHPExcel_IOFactory::identify($targetFile);
      $objReader = PHPExcel_IOFactory::createReader($inFileType);
      $objXL = $objReader->load($targetFile);
    } catch (Exception $e) {
      die("Error opening file: ".$e->getMessage());
    }

    // Start off assuming we're valid, then set it once we're not
    $valid = true;

    // Now go through the values in the sheet to validate the required key fields to see if there are any errors before
    // we do any actual inserts into the database

    $sheet = $objXL->getSheet(0);
    $highestRow = $sheet->getHighestRow();

    $content = "<h3>" . __("The following required fields are not mapped") . ":</h3><ul>";

    foreach ( array( "DataCenterID", "Cabinet", "Position", "Label", "Height", "Manufacturer", "Model" ) as $required ) {
      if ( ! isset($_REQUEST[$required]) || isset($_REQUEST[$required]) && $_REQUEST[$required] == 0 ) {
        $content .= "<li>$required";
        $valid = false;
      }
    }

    if ( ! $valid ) {
      $content .= "</ul></div>";
    } else {
      $content = "";
    }
    if ( $valid ) {
      $values = array();
      $fields = array( "DataCenterID", "Cabinet", "Manufacturer", "Model", "Owner", "PrimaryContact" );
      // Skip the first row, which has the headers in it
      for ( $n = 2; $n <= $highestRow; $n++ ) {
        foreach( $fields as $fname ) {
          $addr = chr( 64 + $_REQUEST[$fname]);
          $row[$fname] = $sheet->getCell( $addr . $n )->getValue();
        }

        // Have to do this part by hand because some fields are actually context dependent upon others
        $values["DataCenterID"][] = $row["DataCenterID"];
        $tmpCab["DataCenterID"] = $row["DataCenterID"];
        $tmpCab["Cabinet"] = $row["Cabinet"];
        $values["Cabinet"][] = $tmpCab;
        $values["Manufacturer"][] = $row["Manufacturer"];
        $tmpModel["Manufacturer"] = $row["Manufacturer"];
        $tmpModel["Model"] = $row["Model"];
        $values["Model"][] = $tmpModel;
        $values["Owner"][] = $row["Owner"];
        $values["PrimaryContact"][] = $row["PrimaryContact"];
      }

      foreach( $values as $key => $val ) {
        $values[$key] = array_unique( $values[$key], SORT_REGULAR );
      }
      
      if ( $valid ) {
        // This could probably be economized in some fashion, but I can just crank this out faster one at a time and worry about efficiency later
        //

        $tmpCon = "<h3>" . __("The following values in the import file require entries in openDCIM before you may proceed.") . "</h3>";
        $tmpCon .= "<ul>";

        $st = $dbh->prepare( "select DataCenterID from fac_DataCenter where ucase(Name)=ucase(:Name)" );
        foreach ( $values["DataCenterID"] as $val ) {
          $st->execute( array( ":Name" => $val ));
          if ( ! $st->fetch()) {
            $valid = false;
            $tmpCon .= "<li>" . __("Data Center") . ": $val";
          }
        }

        // To check validity of cabinets, we have to know the data center for that specific cabinet.
        $st = $dbh->prepare( "select CabinetID from fac_Cabinet where ucase(Location)=ucase( :Location ) and DataCenterID in (select DataCenterID from fac_DataCenter where ucase(Name)=ucase( :DataCenter ))" );
        foreach( $values["Cabinet"] as $row ) {
          $st->execute( array( ":Location"=>$row["Cabinet"], ":DataCenter"=>$row["DataCenterID"] ));
          if ( ! $st->fetch()) {
            $valid = false;
            $tmpCon .= "<li>" . __("Cabinet") . ": " . $row["DataCenterID"] . " - " . $row["Cabinet"];
          }
        }

        // Check the Manufacturer Names for validity
        $st = $dbh->prepare( "select ManufacturerID from fac_Manufacturer where ucase(Name)=ucase(:Name)" );
        foreach ( $values["Manufacturer"] as $val ) {
          $st->execute( array( ":Name" => $val ) );
          if ( ! $st->fetch() ) {
            $valid = false;
            $tmpCon .= "<li>" . __("Manufacturer");
          }
        }

        // Check the Model for validity, which is like cabinets - it requires the Manufacturer paired with the Model to check.
        $st = $dbh->prepare( "select TemplateID from fac_DeviceTemplate where ucase(Model)=ucase(:Model) and ManufacturerID in (select ManufacturerID from fac_Manufacturer where ucase(Name)=ucase(:Manufacturer))" );        
        foreach( $values["Model"] as $row ) {
          $st->execute( array( ":Model"=>$row["Model"], ":Manufacturer"=>$row["Manufacturer"] ));
          if ( ! $st->fetch() ) {
            $valid = false;
            $tmpCon .= "<li>" . __("Model") . ": " . $row["Manufacturer"] . " - " . $row["Model"];
          }
        }

        // Check for the Department to be valid
        $st = $dbh->prepare( "select DeptID from fac_Department where ucase(Name)=ucase( :Name )" );
        foreach( $values["Owner"] as $val ) {
          $st->execute( array( ":Name"=>$val ));
          if ( ! $st->fetch() ) {
            $valid = false;
            $tmpCon .= "<li>" . __("Department") . ": " . $val;
          }
        }

        // Finally, check on the Primary Contact

        //  TO DO LATER

        if ( ! $valid ) {
          $content .= $tmpCon . "</ul>";
        } else {
          $content = '<form action="' . $_SERVER['PHP_SELF']. '" method="POST" ENCTYPE="multipart/form-data">';
          $content .= "<h3>" . __( "The file has passed validation.  Press the Process button to import." ) . "</h3>";
          $content .= '<input type="hidden" name="stage" value="process">
                    <div>
                    <input type="submit" value="' . __("Process") . '" name="submit">
                    </div>
                  </form>';
        }

      }
    }
  } elseif ( isset($_REQUEST['stage']) && $_REQUEST['stage'] == 'process' ) {
    // Open the file to finally do some actual inserts this time

    $targetFile = $_SESSION['inputfile'];
    try {
      $inFileType = PHPExcel_IOFactory::identify($targetFile);
      $objReader = PHPExcel_IOFactory::createReader($inFileType);
      $objXL = $objReader->load($targetFile);
    } catch (Exception $e) {
      die("Error opening file: ".$e->getMessage());
    }


  } else {
    //
    //  No parameters were passed with the URL, so this is the top level, where
    //  we need to ask for the user to specify a file to upload.
    //
    $content = '<form action="' . $_SERVER['PHP_SELF']. '" method="POST" ENCTYPE="multipart/form-data">';
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
