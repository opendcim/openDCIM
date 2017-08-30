<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

  if(!$person->BulkOperations){
    header('Location: '.redirect());
    exit;
  }

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Bulk Device Moves/Deletes");

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

    foreach ( array( "DeviceID"=>"The key field (must uniquely identify) the device to be moved.", "DataCenterID"=>"The name of the Data Center as it exists in openDCIM to move the device to.  If left blank, this will become a DELETE operation.", "Cabinet"=>"The location name of the cabinet, which must exist in openDCIM.  For rows that are delete operations, this can be left blank.", "Position"=>"The position within the specified cabinet to move the device to.  For rows that are delete operations, this can be left blank.  There is no collision checking performed.", "ProcessDate"=>"The date for the operation, which will fill the Installation Date field of the Device record.  If left blank on a row that is a move operation, the current date will be used." ) as $fieldName=>$helpText ) {
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

    $content .= "<div><div><span title=\"" . __("The type of key field that is being used to match devices in openDCIM.  Only one type may be specified per file.") . "\">" . __("KeyField") . "</span></div><div><select name='KeyField'>";
    foreach( array( "Label", "Hostname", "AssetTag", "SerialNo" ) as $option ) {
      $content .= "<option val=\"$option\">$option</option>";
    }

    $content .= "</select></div></div>";

    $content .= "<div><div></div><div><input type='submit' value='" . __("Validate") . "' name='submit'></div></div>";

    $content .= '</form>
        </div>';
   } elseif ( isset($_REQUEST['stage']) && $_REQUEST['stage'] == 'validate' ) {
    // Certain fields we are going to require that the values exist in the db already (if a value is specified)
    //
    // Device
    // Data Center
    // Cabinet
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

    switch( $_REQUEST["KeyField"] ) {
      case "Hostname":
        $idField = "PrimaryIP";
        break;
      case "AssetTag":
        $idField = "AssetTag";
        break;
      case "Label":
        $idField = "Label";
        break;
      case "SerialNo":
        $idField = "SerialNo";
        break;
    }

    // Now go through the values in the sheet to validate the required key fields to see if there are any errors before
    // we do any actual inserts into the database

    $sheet = $objXL->getSheet(0);
    $highestRow = $sheet->getHighestRow();
    $tmpCon = "<h3>" . __("The following values in the bulk move/delete file require entries in openDCIM before you may proceed.") . "</h3>";
    $tmpCon .= "<ul>";

    $values = array();
    $fields = array( "DeviceID", "DataCenterID", "Cabinet", "Position" );
    // Skip the first row, which has the headers in it
    for ( $n = 2; $n <= $highestRow; $n++ ) {
      foreach( $fields as $fname ) {
        if ( $_REQUEST[$fname] != 0 ) {
          $addr = chr( 64 + $_REQUEST[$fname]);
          $row[$fname] = sanitize($sheet->getCell( $addr . $n )->getValue());
        } else {
          $row[$fname] = "";
        }
      }

      $tmpDev = new Device();

      // This could probably be economized in some fashion, but I can just crank this out faster one at a time and worry about efficiency later
      //

      $st = $dbh->prepare( "select count(DeviceID) as TotalMatches, DeviceID from fac_Device where ucase(" . $idField . ")=ucase(:DeviceKey)" );
      $st->execute( array( ":DeviceKey"=>$row["DeviceID"]));
      if ( ! $devRow = $st->fetch()) {
        $info = $dbh->errorInfo();
        error_log( "PDO Error: {$info[2]}");
        $rowError = true;
      }

      if ( $devRow["TotalMatches"] != 1 ) {
        $rowError = true;
        $tmpCon .= "<li>Device: $idField = " . $row["DeviceID"] . " is not unique or not found.";
      } else {
        $tmpDev->DeviceID = $devRow["DeviceID"];
        $tmpDev->GetDevice();
      }

      $DataCenterID = 0;
      $st = $dbh->prepare( "select count(DataCenterID) as TotalMatches, DataCenterID from fac_DataCenter where ucase(Name)=ucase(:Name)" );
      if ( $row["DataCenterID"] != "" ) {
        $st->execute( array( ":Name" => $row["DataCenterID"] ));
        if ( ! $dcRow = $st->fetch()) {
          $info = $dbh->errorInfo();
          error_log( "PDO Error: {$info[2]}");
          $rowError = true;
        }

        if ( $dcRow["TotalMatches"] != 1 ) {
          $rowError = true;
          $tmpCon .= "<li>DataCenterID: " . $row["DataCenterID"] . " is not unique or not found.";
        } else {
          $DataCenterID = $dcRow["DataCenterID"];
        }
      }

      $CabinetID = 0;
      // To check validity of cabinets, we have to know the data center for that specific cabinet.
      $st = $dbh->prepare( "select count(CabinetID) as TotalMatches, CabinetID from fac_Cabinet where ucase(Location)=ucase( :Location ) and DataCenterID=:DataCenter" );
      if ( $DataCenterID>0 ) {
        $st->execute( array( ":Location"=>$row["Cabinet"], ":DataCenter"=>$DataCenterID ));
        if ( ! $cabRow = $st->fetch()) {
          $info = $dbh->errorInfo();
          error_log( "PDO Error: {$info[2]}");
          $rowError = true;
        }

        if ( $cabRow["TotalMatches"] != 1 ) {
          $rowError = true;
          $tmpCon .= "<li>" . __("Cabinet") . ": " . $row["DataCenterID"] . " - " . $row["Cabinet"];
        } else {
          $CabinetID = $cabRow["CabinetID"];
        }
      }

      // Do not check for collision on a delete
      if ( $DataCenterID>0 ) {
        $st = $dbh->prepare( "select DeviceID, Label from fac_Device where ParentDevice=0 and Cabinet=:CabinetID and (Position between :StartPos and :EndPos or Position+Height between :StartPos2 and :EndPos2)" );

        if ( $tmpDev->DeviceID > 0 ) {
          $endPos = $row["Position"] + $tmpDev->Height - 1;

          if ( ! $st->execute( array( ":CabinetID"=>$CabinetID,
            ":StartPos"=>$row["Position"],
            ":EndPos"=>$endPos,
            ":StartPos2"=>$row["Position"],
            ":EndPos2"=>$endPos )) ) {
            $info = $dbh->errorInfo();
            error_log( "PDO Error on Collision Detection: {$info[2]}" );
          }

          if ( $collisionRow = $st->fetch() ) {
            $tmpCon .= "<li>" . __("Collision Detected") . ": " . $row["DataCenterID"] . ":" . $row["Cabinet"] . " - " . $row["Position"] . " :: " . $row["DeviceID"];
            $rowError = true;
          }
        }
      }
    }

    if ( $rowError ) {
      $content .= $tmpCon . "</ul>";
    } else {
      $content = '<form method="POST">';
      $content .= "<h3>" . __( "The file has passed validation.  Press the Process button to import." ) . "</h3>";
      $content .= "<input type=\"hidden\" name=\"stage\" value=\"process\">\n";
      foreach( array( "DeviceID", "DataCenterID", "Cabinet", "Position", "ProcessDate", "KeyField" ) as $mapVar ) {
        $content .= "<input type=\"hidden\" name=\"" . $mapVar . "\" value=\"" . $_REQUEST[$mapVar] . "\">\n";
      }

      $content .= '<div>
                <input type="submit" value="' . __("Process") . '" name="submit">
                </div>
              </form>';
    }
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
    $fields = array( "DeviceID", "DataCenterID", "Cabinet", "Position", "ProcessDate" );

    for ( $n = 2; $n <= $highestRow; $n++ ) {
      $rowError = false;
      $dev = new Device();
 
      // Load up the $row[] array with the values according to the mapping supplied by the user
      foreach( $fields as $fname ) {
        $addr = chr( 64 + $_REQUEST[$fname]);
        $row[$fname] = sanitize($sheet->getCell( $addr . $n )->getValue());
      }

      switch( $_REQUEST["KeyField"] ) {
        case "Hostname":
          $idField = "PrimaryIP";
          break;
        case "AssetTag":
          $idField = "AssetTag";
          break;
        case "Label":
          $idField = "Label";
          break;
        case "SerialNo":
          $idField = "SerialNo";
          break;
      }

      /*
       *
       *  Section for looking up the DeviceID and setting the true DeviceID in the dev variable
       *
       */
      $st = $dbh->prepare( "select count(DeviceID) as TotalMatches, DeviceID from fac_Device where ucase(" . $idField . ")=ucase(:SourceDeviceID)" );
      $st->execute( array( ":SourceDeviceID"=>$row["DeviceID"] ));
      if ( ! $val = $st->fetch() ) {
        $info = $dbh->errorInfo();
        error_log( "PDO Error: {$info[2]}");
        $rowError = true;
      }

      if ( $val["TotalMatches"] == 1 ) {
        $dev->DeviceID = $val["DeviceID"];
        $dev->GetDevice();
      } else {
        $rowError = true;
        $content .= "<li>Device: $idField = " . $row["DeviceID"] . " is not unique or not found.";
      }

      if ( $row["DataCenterID"] == "" ) {
        // This is a DELETE operation
        $dev->DeleteDevice();
        $content .= "<li>Device: $idField = " . $row["DeviceID"] . " deleted.";
      } else {
        // Now start getting the foreign keys as needed and set them in the $dev variable
        $st = $dbh->prepare( "select DataCenterID from fac_DataCenter where ucase(Name)=ucase(:Name)" );
        $st->execute( array( ":Name" => $row["DataCenterID"] ));
        if ( ! $val = $st->fetch()) {
          // We just checked this, so there really shouldn't be an issue unless the db died
          $info = $dbh->errorInfo();
          error_log( "PDO Error: {$info[2]} (Data Center search)");
          $rowError = true;
        } else {
          $dev->DataCenterID = $val["DataCenterID"];
        }

        $st = $dbh->prepare( "select CabinetID from fac_Cabinet where ucase(Location)=ucase(:Location) and DataCenterID=:DataCenterID" );
        $st->execute( array( ":Location" => $row["Cabinet"], ":DataCenterID"=>$dev->DataCenterID ));
        if ( ! $val = $st->fetch()) {
          $info = $dbh->errorInfo();
          error_log( "PDO Error: {$info[2]} (Cabinet search)");
          $rowError = true;
        } else {
          $dev->Cabinet = $val["CabinetID"];
          $dev->Position = $row["Position"];
        }

        if ( ! $rowError && ! $dev->UpdateDevice() ) {
          $rowError = true;
        } else {
          $content .= "<li>Successfully processed move of " . $row["DeviceID"] . " to " . $row["DataCenterID"] . " - " .$row["Cabinet"] . ":" . $row["Position"];
        }
      }

      if ( $rowError ) {
        $content .= "<li><strong>Error processing move on Row $n of the spreadsheet.</strong>";
        $errors = true;
      }
    }

    if ( ! $errors ) {
      $content = __("All records processed successfully.") . "<ul>" . $content . "</ul>";
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
