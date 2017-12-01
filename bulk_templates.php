<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

  if(!$person->BulkOperations){
    header('Location: '.redirect());
    exit;
  }

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Bulk Device Template Importer");

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

    $content = "<h3>" . __("This template importer is to get the bare minimum information entered.   For a more robust template sharing system, use the online repository." ) . "</h3>";
    $content .= "<h3>" . __("Mouse over each field for help text.") . "</h3>";

    $content .= '<form method="POST">
                    <input type="hidden" name="stage" value="process">
                    <div class="table">';

    // Find out how many columns are in the spreadsheet so that we can load them as possible values for the fields
    // and we don't really care how many rows there are at this point.
    $sheet = $objXL->getSheet(0);
    $highestColumn = $sheet->getHighestColumn();

    $headerList = $sheet->rangeToArray('A1:' . $highestColumn . '1' );

    $fieldList = array( "Choose column" );
    foreach( $headerList[0] as $fName ) {
      $fieldList[] = $fName;
    }

    $fieldNum = 1;

    foreach ( array( "Manufacturer"=>"The name of the device manufacturer, which must match an existing record in the database.", "Model"=>"The model name of the device template being added.   The combination of Manufacturer + Model must be unique.", "Height"=>"Height of the device in Rack Units.  Required.", "Weight"=>"The weight of the device in units specified in the Configuration screen (lbs vs kg).  Optional.", "DeviceType"=>"The type of device, which must be one of {Server, Appliance, Storage Array, Switch, Chassis, Patch Panel, Physical Infrastructure, CDU, Sensor}.  No, the DeviceTypes can't be expanded.  See the FAQ for explanation.  Required.", "NominalWatts"=>"The total watts consumed by a typical instance of this make and model device.   A good rule of thumb is 40% of the rating of a single power supply.  Optional.", "NumPower"=>"The number of power connectors (supplies) on the device.   Highly recommended, but optional.", "NumPorts"=>"The number of data connectors on the device.   Highly recommended, but optional.", "PSNames"=>"The names to give the power supply connections - only matters if NumPower field has a value that matches the number of names in the comma separated list.", "PortNames"=>"The names of the data ports - only matters if NumPorts field has a value that matches the number of names in the comma separated list." ) as $fieldName=>$helpText ) {
      $content .= '<div>
                    <div><span title="' . __($helpText) . '">' . __($fieldName) . '</span>: </div><div><select name="' . $fieldName . '">';
      for ( $n = 0; $n < sizeof( $fieldList ); $n++ ) {
        if ( ($n) == $fieldNum )
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
    $fields = array( "Manufacturer", "Model", "Height", "Weight", "DeviceType", "NominalWatts", "NumPower", "NumPorts", "PSNames", "PortNames" );

    $devTypes = array( "Server", "Appliance", "Storage Array", "Switch", "Chassis", "Patch Panel", "Physical Infrastructure", "CDU", "Sensor");

    for ( $n = 2; $n <= $highestRow; $n++ ) { 
      $rowError = false;

      // Load up the $row[] array with the values according to the mapping supplied by the user
      foreach( $fields as $fname ) {
        $addr = chr( 64 + $_REQUEST[$fname]);
        if ( $_REQUEST[$fname] != 0 ) {
          $row[$fname] = sanitize($sheet->getCell( $addr . $n )->getValue());
        }
      }

      // Stop processing once you hit the first blank cell for 'UserID' - some Excel files will return $sheet->getHighestRow() way past the end of any meaningful data
      if ( $row["Manufacturer"] == "" ) {
        break;
      }

      $st = $dbh->prepare( "select ManufacturerID from fac_Manufacturer where ucase(Name)=ucase(:Manufacturer)");
      $st->execute( array( ":Manufacturer"=>$row["Manufacturer"]));
      if ( $val=$st->fetch()) {
        $manID = $val[0];
        
        if ( in_array( strtolower($row["DeviceType"]), array_map('strtolower', $devTypes )) ) {
          $tmpl = new DeviceTemplate();
          $tmpl->ManufacturerID = $manID;
          $tmpl->Model = $row["Model"];
          $tmpl->DeviceType = $row["DeviceType"];
          $tmpl->Height = $row["Height"];
          $tmpl->Weight = $row["Weight"];
          $tmpl->Wattage = $row["NominalWatts"];
          $tmpl->PSCount = $row["NumPower"];
          $tmpl->NumPorts = $row["NumPorts"];

          if ( $tmpl->CreateTemplate() ) {
            $content .= "<li>" . __("Created Template:") . " " . $row["Manufacturer"] . " - " . $row["Model"];

            if ( $tmpl->PSCount > 0 ) {
              $tpp = new TemplatePowerPorts();
              $tpp->TemplateID = $tmpl->TemplateID;

              $ppNames = explode( ",", $row["PSNames"] );
              if ( count( $ppNames ) == $tmpl->PSCount ) {
                for( $p = 0; $p<$tmpl->PSCount; $p++) {
                  $tpp->PortNumber = $p+1;
                  $tpp->Label = $ppNames[$p];
                  $tpp->createPort();
                }                
              }
            }

            if ( $tmpl->NumPorts > 0 ) {
                $tp = new TemplatePorts();
                $tp->TemplateID = $tmpl->TemplateID;

                $pNames = explode( ",", $row["PortNames"]);
                if ( count($pNames) == $tmpl->NumPorts ) {
                  for ( $p = 0; $p < $tmpl->NumPorts; $p++ ) {
                    $tp->PortNumber = $p + 1;
                    $tp->Label = $pNames[$p];
                    $tp->createPort();
                  }
                }
            }
          } else {
            $errors = true;
            $content .= "<li>" . __("Error:  Unable to add template for") . " " . $row["Manufacturer"] . " - " . $row["Model"];
          }
        } else {
          $errors = true;
          $content .= "<li>" . __("Error:  Invalid DeviceType specified") . " - " . $row["DeviceType"];
        }
      } else {
        $errors = true;
        $content .= "<li>" . __("Error:  Manufacturer name ") . $row["Manufacturer"] . __("not in database.");
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
