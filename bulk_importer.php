<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

  if(!$person->BulkOperations){
    header('Location: '.redirect());
    exit;
  }

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Bulk Device Importer");

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

    foreach ( array( "DataCenterID"=>"The exact name of the target data center for import.", "Cabinet"=>"The name (Location) of the target cabinet.", "Position"=>"The position in the cabinet for the device.  0 is valid for zero-U devices.  No collision checking is performed.", "Label"=>"The value to place in the Label field.", "Height"=>"The height of the device, 0 is a valid value.", "Manufacturer"=>"The name of the Manufacturer.  This is combined with the Model field to create the 'Device Class'.", "Model"=>"The model name, as specified in the existing Device Template, which will be combined with the Manufacturer to choose the 'Device Class'.", "Hostname"=>"An optional IP address or hostname for the device.", "SerialNo"=>"An optional value to place in the Serial Number field of the device.", "AssetTag"=>"An optional Asset or Property number to assign to the device.", "HalfDepth"=>"Optional, specify 1 or Y to indicate this device only occupies half the depth of the cabinet.", "BackSide"=>"Optional, specify 1 or Y to indicate that this device is mounted from the rear of the cabinet.", "Hypervisor"=>"Optional, specify 'ESX', 'ProxMox', or blank (for default behavior of 'None').", "InstallDate"=>"If blank, current date is used, otherwise this mandatory field can contain any ISO valid date format.", "Status"=>"Optional, default will be set to 'Reserved' if not specified.  Value must exist as a Device Status in the database.", "Owner"=>"Optional, and may be blank.  This is the name of the Department that owns the device.", "PrimaryContact"=>"Optional, and may be blank.  The exact name of the Primary Contact for this device in LastName, FirstName format.", "CustomTags"=>"A comma separated list of tags to apply to the device.  Tags do not have to already exist within openDCIM." ) as $fieldName=>$helpText ) {
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

    $content .= "<div><div></div><div><input type='submit' value='" . __("Validate") . "' name='submit'></div></div>";

    $content .= '</form>
        </div>';
  } elseif ( isset($_REQUEST['stage']) && $_REQUEST['stage'] == 'validate' ) {
    
    // Certain fields we are going to require that the values exist in the db already (if a value is specified)
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
          if ( $_REQUEST[$fname] != 0 ) {
            $addr = chr( 64 + $_REQUEST[$fname]);
            $row[$fname] = sanitize($sheet->getCell( $addr . $n )->getValue());
          } else {
            $row[$fname] = "";
          }
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
        if ( $row["Owner"]  != "" ) {
          $values["Owner"][] = $row["Owner"];
        }
        if ( $row["PrimaryContact"] != "" ) {
          $values["PrimaryContact"][] = $row["PrimaryContact"];
        }
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
          if ( ! $row = $st->fetch()) {
            $valid = false;
            $tmpCon .= "<li>" . __("Cabinet") . ": " . $row["DataCenterID"] . " - " . $row["Cabinet"];
          }
        }

        // Check the Manufacturer Names for validity
        $st = $dbh->prepare( "select ManufacturerID from fac_Manufacturer where ucase(Name)=ucase(:Name)" );
        foreach ( $values["Manufacturer"] as $val ) {
          if ( $val != "" ) {
            $st->execute( array( ":Name" => $val ) );
            if ( ! $st->fetch() ) {
              $valid = false;
              $tmpCon .= "<li>" . __("Manufacturer") . ": " . $val;
            }
          }
        }

        // Check the Model for validity, which is like cabinets - it requires the Manufacturer paired with the Model to check.
        $st = $dbh->prepare( "select * from fac_DeviceTemplate where ucase(Model)=ucase(:Model) and ManufacturerID in (select ManufacturerID from fac_Manufacturer where ucase(Name)=ucase(:Manufacturer))" );        
        foreach( $values["Model"] as $row ) {
          $st->execute( array( ":Model"=>$row["Model"], ":Manufacturer"=>$row["Manufacturer"] ));
          if ( ! $st->fetch() ) {
            $valid = false;
            $tmpCon .= "<li>" . __("Model") . ": " . $row["Manufacturer"] . " - " . $row["Model"];
          }
        }

        // Check for the Department to be valid
        if ( isset($values["Owner"] )) {
          $st = $dbh->prepare( "select DeptID from fac_Department where ucase(Name)=ucase( :Name )" );
          foreach( $values["Owner"] as $val ) {
            $st->execute( array( ":Name"=>$val ));
            if ( ! $st->fetch() ) {
              $valid = false;
              $tmpCon .= "<li>" . __("Department") . ": " . $val;
            }
          }
        }

        // Finally, check on the Primary Contact
        if ( isset($values["PrimaryContact"] )) {
          $st = $dbh->prepare( "select PersonID from fac_People where ucase(concat(LastName, ', ', FirstName))=ucase(:Contact)" );
          foreach( $values["PrimaryContact"] as $val ) {
            if ( $val != "" ) {
              $st->execute( array( ":Contact" => $val ));
              if ( ! $st->fetch()) {
                $valid = false;
                $tmpCon .= "<li>" . __("Primary Contact") . ":" . $val;
              }
            }
          }
        }

        // Now quickly run back through all of the rows and check for collisions

        $st = $dbh->prepare( "select DeviceID, Label from fac_Device where ParentDevice=0 and Cabinet in (select CabinetID from fac_Cabinet where DataCenterID in (select DataCenterID from fac_DataCenter where ucase(Name)=ucase(:DataCenterID)) and ucase(Location)=ucase(:Cabinet)) and (Position between :StartPos and :EndPos or Position+Height-1 between :StartPos2 and :EndPos2)" );

        $cFields = array( "DataCenterID", "Cabinet", "Position", "Height", "Label" );
        for ( $n = 2; $n <= $highestRow; $n++ ) {
          foreach( $cFields as $fname ) {
            if ( $_REQUEST[$fname] != 0 ) {
              $addr = chr( 64 + $_REQUEST[$fname]);
              $row[$fname] = sanitize($sheet->getCell( $addr . $n )->getValue());
            } else {
              $row[$fname] = "";
            }
          }

          // Any floating point value refers to a card going into a server.  Since the server
          // being added could be a row in this field, we simply don't detect collisions
          // and will show an error during processing if it comes to that.
          $pos = explode( ".", $row["Position"]);

          // Floating point entries once split will have 2 or more members in the $pos array
          if ( sizeof( $pos ) < 2 ) {
            if ( $row["Height"] > 0 ) {
              $endPos = $row["Position"] + $row["Height"] - 1;

              if ( ! $st->execute( array( ":DataCenterID"=>$row["DataCenterID"],
                ":Cabinet"=>$row["Cabinet"],
                ":StartPos"=>$row["Position"],
                ":EndPos"=>$endPos,
                ":StartPos2"=>$row["Position"],
                ":EndPos2"=>$endPos )) ) {
                $info = $dbh->errorInfo();
                error_log( "PDO Error on Collision Detection: {$info[2]}" );
              }

              if ( $collisionRow = $st->fetch() ) {
                $tmpCon .= "<li>" . __("Collision Detected") . ": " . $row["DataCenterID"] . ":" . $row["Cabinet"] . " - " . $row["Position"] . " :: " . $row["Label"];
                $valid = false;
              }
            }
          }
        }

        if ( ! $valid ) {
          $content .= $tmpCon . "</ul>";
        } else {
          $content = '<form method="POST">';
          $content .= "<h3>" . __( "The file has passed validation.  Press the Process button to import." ) . "</h3>";
          $content .= "<input type=\"hidden\" name=\"stage\" value=\"process\">\n";
          foreach( array( "DataCenterID", "Cabinet", "Position", "Label", "Height", "Manufacturer", "Model", "Hostname", "SerialNo", "AssetTag", "Hypervisor", "BackSide", "HalfDepth", "Status", "InstallDate", "Owner", "PrimaryContact", "CustomTags" ) as $mapVar ) {
            $content .= "<input type=\"hidden\" name=\"" . $mapVar . "\" value=\"" . $_REQUEST[$mapVar] . "\">\n";
          }

          $content .= '<div>
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

    // Start off with the assumption that we have zero processing errors
    $errors = false;

    $sheet = $objXL->getSheet(0);
    $highestRow = $sheet->getHighestRow();

    // Also make sure we start with an empty string to display
    $content = "";
    $fields = array( "DataCenterID", "Cabinet", "Position", "Label", "Height", "Manufacturer", "Model", "Hostname", "SerialNo", "AssetTag", "Hypervisor", "BackSide", "HalfDepth", "Status", "Owner", "InstallDate", "PrimaryContact", "CustomTags" );

    for ( $n = 2; $n <= $highestRow; $n++ ) {
      // Instantiate a fresh Device object for each insert
      $dev = new Device();

      // Load up the $row[] array with the values according to the mapping supplied by the user
      foreach( $fields as $fname ) {
        if ( $_REQUEST[$fname] != 0 ) {
          $addr = chr( 64 + $_REQUEST[$fname]);
          $row[$fname] = sanitize($sheet->getCell( $addr . $n )->getValue());
        } else {
          $row[$fname] = "";
        }
      }

      // Now start getting the foreign keys as needed and set them in the $dev variable
      $st = $dbh->prepare( "select DataCenterID from fac_DataCenter where ucase(Name)=ucase(:Name)" );
      $st->execute( array( ":Name" => $row["DataCenterID"] ));
      if ( ! $val = $st->fetch()) {
        // We just checked this, so there really shouldn't be an issue unless the db died
        $info = $dbh->errorInfo();
        error_log( "PDO Error: {$info[2]} (Data Center search)");
      }
      $dev->DataCenterID = $val["DataCenterID"];

      $st = $dbh->prepare( "select CabinetID from fac_Cabinet where ucase(Location)=ucase( :Location ) and DataCenterID=:DataCenterID" );
      $st->execute( array( ":Location"=>$row["Cabinet"], ":DataCenterID"=>$dev->DataCenterID ));
      if ( ! $val = $st->fetch()) {
        $info = $dbh->errorInfo();
        error_log( "PDO Error: {$info[2]} (Cabinet search)");
      }
      $dev->Cabinet = $val["CabinetID"];

      $pos = explode( ".", $row["Position"] );
      if ( sizeof( $pos ) > 1 ) {
        // This is a child (card) so we need to find the parent
        $pDev = new Device();
        $pDev->Cabinet = $dev->Cabinet;
        $pDev->ParentDevice = 0;
        $pDev->Position = $pos[0];
        $pList = $pDev->Search();
        if ( sizeof( $pList ) != 1 ) {
          $content .= "<li>" . __("Parent device does not exist at specified location." );
          $errors = true;
        } else {
          $dev->ParentDevice = $pList[0]->DeviceID;
          $dev->Position = $pos[1];
        }
      } else {
        $dev->Position = $row["Position"];
      }

      $dev->Label = $row["Label"];
      $dev->Height = $row["Height"];

      $st = $dbh->prepare( "select * from fac_DeviceTemplate where ucase(Model)=ucase(:Model) and ManufacturerID in (select ManufacturerID from fac_Manufacturer where ucase(Name)=ucase(:Manufacturer))" );
      $st->execute( array( ":Model" => $row["Model"], ":Manufacturer"=>$row["Manufacturer"] ));
      if ( ! $val = $st->fetch()) {
        $info = $dbh->errorInfo();
        error_log( "PDO Error: {$info[2]} (Template search)");
      }
      $dev->TemplateID = $val["TemplateID"];
      $dev->Ports = $val["NumPorts"];
      $dev->NominalWatts = $val["Wattage"];
      $dev->DeviceType = $val["DeviceType"];
      $dev->PowerSupplyCount = $val["PSCount"];
      $dev->ChassisSlots = $val["ChassisSlots"];
      $dev->RearChassisSlots = $val["RearChassisSlots"];
      $dev->Weight = $val["Weight"];

      $dev->PrimaryIP = $row["Hostname"];
      $dev->SerialNo = $row["SerialNo"];
      $dev->AssetTag = $row["AssetTag"];
      $dev->BackSide = ($row["BackSide"] == 1 || strtoupper($row["BackSide"] == "Y"))?1:0;
      $dev->HalfDepth = ($row["HalfDepth"] == 1 || strtoupper($row["HalfDepth"] == "Y"))?1:0;
      $dev->Hypervisor = (in_array( $row["Hypervisor"], array( "ESX", "ProxMox")))?$row["Hypervisor"]:"None";
      if ( $row["InstallDate"] != "" ) {
        $dev->InstallDate = date( "Y-m-d", strtotime( $row["InstallDate"]));
      } else {
        $dev->InstallDate = date( "Y-m-d" );
      }
      $dev->Status = in_array($row["Status"], DeviceStatus::getStatusNames() )?$row["Status"]:"Reserved";

      if ( $row["Owner"] != "" ) {
        $st = $dbh->prepare( "select DeptID from fac_Department where ucase(Name)=ucase(:Name)" );
        $st->execute( array( ":Name" => $row["Owner"] ));
        if ( ! $val = $st->fetch()) {
          $info = $dbh->errorInfo();
          error_log( "PDO Error: {$info[2]} (Department search)");
        }

        $dev->Owner = $val["DeptID"];
      }

      if ( $row["PrimaryContact"] != "" ) {
        $st = $dbh->prepare( "select PersonID from fac_People where ucase(concat(LastName, ', ', FirstName))=ucase(:Contact)" );
        $st->execute( array( ":Contact" => $row["PrimaryContact"] ));
        if ( ! $val = $st->fetch()) {
          $info = $dbh->errorInfo();
          error_log( "PDO Error: {$info[2]} (Primary Contact search)");
        }

        $dev->PrimaryContact = $val["PersonID"];
      }

      if ( ! $errors && ! $dev->CreateDevice() ) {
        $errors = true;
        $content .= "<li><strong>" . __("Error adding device on Row $n of the spreadsheet.") . "</strong>";
      } else {
        if ( $row["CustomTags"] != "" ) {
          $tagList = array_map( 'trim', explode( ",", $row["CustomTags"] ));
          $dev->SetTags( $tagList );
        }
        $content .= "<li>Added device " . $dev->Label . "(" . $dev->DeviceID . ")";
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
