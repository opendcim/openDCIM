<?php
require_once('db.inc.php');
require_once('facilities.inc.php');

if(!$person->BulkOperations){
  header('Location: '.redirect());
  exit;
}

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
$subheader=__("Bulk Power Importer");

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

  foreach ( array( "SourceDeviceID"=>"The key value to indicate the existing device the connection originates from.", "SourcePort"=>"The name of the existing port for the origination of the connection.", "TargetDeviceID"=>"The key value to indicate the existing device the connection terminates at.", "TargetPort"=>"The name of the existing port for the termination of the connection.", "Notes"=>"Optional, free form text to add to the Notes field for the connection." ) as $fieldName=>$helpText ) {
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
  $fields = array( "SourceDeviceID", "SourcePort", "TargetDeviceID", "TargetPort", "Notes" );

  for ( $n = 2; $n <= $highestRow; $n++ ) {
    $rowError = false;

    $powPort = new PowerPorts();

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
     *  Section for looking up the SourceDeviceID and setting the true DeviceID in the powPort variable
     *
     */
    $st = $dbh->prepare( "select count(DeviceID) as TotalMatches, DeviceID from fac_Device where ucase(" . $idField . ")=ucase(:SourceDeviceID)" );
    $st->execute( array( ":SourceDeviceID"=>$row["SourceDeviceID"] ));
    if ( ! $val = $st->fetch() ) {
      $info = $dbh->errorInfo();
      error_log( "PDO Error: {$info[2]}");
    }

    if ( $val["TotalMatches"] == 1 ) {
      $powPort->DeviceID = $val["DeviceID"];
    } else {
      $errors = true;
      $content .= "<li>Source Device: $idField = " . $row["SourceDeviceID"] . " is not unique or not found.";
    }

    /*
     *
     *  Section for looking up the TargetDeviceID and setting the true DeviceID in the powPort variable
     *
     */
    $st = $dbh->prepare( "select count(DeviceID) as TotalMatches, DeviceID from fac_Device where ucase(" . $idField . ")=ucase(:TargetDeviceID)" );
    $st->execute( array( ":TargetDeviceID"=>$row["TargetDeviceID"] ));
    if ( ! $val = $st->fetch() ) {
      $info = $dbh->errorInfo();
      error_log( "PDO Error: {$info[2]}");
    }

    if ( $val["TotalMatches"] == 1 ) {
      $powPort->ConnectedDeviceID = $val["DeviceID"];
    } else {
      $errors = true;
      $content .= "<li>Target Device: $idField = " . $row["TargetDeviceID"] . " is not unique or not found.";
    }

    /*
     *
     *  Section for looking up the SourcePort by name and setting the true PortNumber in the powPort variable
     *
     */
    $st = $dbh->prepare( "select count(*) as TotalMatches, Label, PortNumber from fac_PowerPorts where DeviceID=:DeviceID and PortNumber>0 and ucase(Label)=ucase(:SourcePort)" );
    $st->execute( array( ":DeviceID"=>$powPort->DeviceID, ":SourcePort"=>$row["SourcePort"] ));
    if ( ! $val = $st->fetch() ) {
      $info = $dbh->errorInfo();
      error_log( "PDO Error: {$info[2]}");
    }

    if ( $val["TotalMatches"] == 1 ) {
      $powPort->PortNumber = $val["PortNumber"];
      $powPort->Label = $val["Label"];
    } else {
      $errors = true;
      $content .= "<li>Source Port: " . $row["SourcePort"] . " is not unique or not found.";
    }

    /*
     *
     *  Section for looking up the TargetPort by name and setting the true PortNumber in the powPort variable
     *  Limits to positive port numbers so that you can match Patch Panel frontside ports
     *
     */
    $st = $dbh->prepare( "select count(*) as TotalMatches, Label, PortNumber from fac_PowerPorts where DeviceID=:DeviceID and PortNumber>0 and ucase(Label)=ucase(:TargetPort)" );
    $st->execute( array( ":DeviceID"=>$powPort->ConnectedDeviceID, ":TargetPort"=>$row["TargetPort"] ));
    if ( ! $val = $st->fetch() ) {
      $info = $dbh->errorInfo();
      error_log( "PDO Error: {$info[2]}");
    }

    if ( $val["TotalMatches"] == 1 ) {
      $powPort->ConnectedPort = $val["PortNumber"];
    } else {
      $errors = true;
      $content .= "<li>Target Port: " . $row["TargetDeviceID"] . "::" . $row["TargetPort"] . " is not unique or not found.";
    }

    $powPort->Notes = $row["Notes"];

    if ( ! $rowError ) {
      if ( ! $powPort->updatePort() ) {
        $errors = true;
      }
    } else {
      $errors = true;
    }

    if ( $rowError ) {
      $content .= "<li><strong>Error making port connection on Row $n of the spreadsheet.</strong>";
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
