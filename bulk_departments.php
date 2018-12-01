<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

  if(!$person->BulkOperations){
    header('Location: '.redirect());
    exit;
  }

require_once 'vendor/box/spout/src/Spout/Autoloader/autoload.php';
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Bulk Department Importer");

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

    $targetInfo = pathinfo($targetFile);
    $targetType = $targetInfo['extension'];

    move_uploaded_file( $_FILES['inputfile']['tmp_name'], $targetFile );

    try {
      if ( $targetType == "xlsx" ) {
        $objReader = ReaderFactory::create(Type::XLSX);
      } elseif ( $targetType == "csv" ) {
        $objReader = ReaderFactory::create(Type::CSV);
      } else {
        die("Error identifying file type.");
      }
      $objReader->open($targetFile);
      $objReader->close();
    } catch (Exception $e) {
      die("Error opening file: ".$e->getMessage());
    }

    $_SESSION['inputfile'] = $targetFile;
    $_SESSION['inputtype'] = $targetType;

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
    $targetType = $_SESSION['inputtype'];
    try {
      if ( $targetType == "xlsx" ) {
        $objReader = ReaderFactory::create(Type::XLSX);
      } elseif ( $targetType == "csv" ) {
        $objReader = ReaderFactory::create(Type::CSV);
      } else {
        die("Error identifying file type.");
      }
      $objReader->open($targetFile);
    } catch (Exception $e) {
      die("Error opening file: ".$e->getMessage());
    }

    // We're good, so now get the top row so that we can map it out to fields

    $content = "<h3>" . __("Pick the appropriate column header (line 1) for each field name listed below.  Any records matching an existing Department Name will be rejected." ) . "</h3>";
    $content .= "<h3>" . __("Mouse over each field for help text.") . "</h3>";

    $content .= '<form method="POST">
                    <input type="hidden" name="stage" value="process">
                    <div class="table">';

    // Find out how many columns are in the spreadsheet so that we can load them as possible values for the fields
    // and we don't really care how many rows there are at this point.
    $rowCount = 1;
    foreach( $objReader->getSheetIterator() as $sheet ) {
      foreach( $sheet->getRowIterator() as $row ) {
        if ( $rowCount == 1 ) {
          $headerList = $row;
          break;
        }
      }
      break;    
    }

    $fieldList = array( "None" );
    foreach( $headerList as $fName ) {
      $fieldList[] = $fName;
    }

    $fieldNum = 1;

    foreach ( array( "DepartmentName"=>"The name of the department (or customer) that you want to identify as owners of devices and cabinets.", "ExecutiveSponsor"=>"The name of the person, typically within the Department/Customer organization, that is the top level contact.", "AccountManager"=>"The name of the person, typically within the hosting data center organization, that is the main liaison with the Department/Customer.", "DepartmentColor"=>"A valid hex-code color that will be applied to devices owned by this Department.   If left blank, no special coloring will be applied.", "Classification"=>"A valid classification name, as defined in the Configuration screen of this openDCIM installation." ) as $fieldName=>$helpText ) {
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
    $targetType = $_SESSION['inputtype'];

    try {
      if ( $targetType == "xlsx" ) {
        $objReader = ReaderFactory::create(Type::XLSX);
      } elseif ( $targetType == "csv" ) {
        $objReader = ReaderFactory::create(Type::CSV);
      } else {
        die("Error identifying file type.");
      }
      $objReader->open($targetFile);
    } catch (Exception $e) {
      die("Error opening file: ".$e->getMessage());
    }

    // Start off with the assumption that we have zero processing errors
    $errors = false;

    $sheet = $objXL->getSheet(0);
    $highestRow = $sheet->getHighestRow();

    // Also make sure we start with an empty string to display
    $content = "";
    $fields = array( "DepartmentName", "ExecutiveSponsor", "AccountManager", "DepartmentColor", "Classification" );

    $iDept = new Department();
    $trueArray = array( "1", "Y", "YES" );

    $rowError = false;

    // Load up the $row[] array with the values according to the mapping supplied by the user
    $rowNum = 1;

    foreach( $objReader->getSheetIterator() as $sheet ) {
      foreach( $sheet->getRowIterator() as $sheetRow ) {
        // Skip the header row
        if ( $rowNum == 1 ) {
          $rowNum++;
          continue;
        }

        $rowError = false;

        // Load up the $row[] array with the values according to the mapping supplied by the user
        foreach( $fields as $fname ) {
          if ( $_REQUEST[$fname] != 0 ) {
            $row[$fname] = sanitize($sheetRow[$_REQUEST[$fname]-1]);
          }
        }

        // Stop processing once you hit the first blank cell for 'UserID' - some Excel files will return $sheet->getHighestRow() way past the end of any meaningful data
        if ( $row["DepartmentName"] == "" ) {
          break;
        }


        $iDept->Name = $row["DepartmentName"];
        if ( $hit = $iDept->GetDeptByName() ) {
          $rowError = true;
          $content .= "<li>" . __("Department name already exists in database:" . " " . $iDept->Name );
        } else {
          $iDept->Name = $row["DepartmentName"];
          $iDept->ExecSponsor=$row["ExecutiveSponsor"];
          $iDept->SDM=$row["AccountManager"];
          
          if ((ctype_xdigit( $row["DepartmentColor"]) && strlen($row["DepartmentColor"]) == 6 ) || $row["DepartmentColor"] == "" ) {
             $iDept->DeptColor=$row["DepartmentColor"];
          } else {
            $rowError = true;
            $content .= "<li>".__("Invalid hex color code entered:") . " " . $row["DepartmentColor"];
          }

          if ( ! in_array( $row["Classification"], $config->ParameterArray["ClassList"])) {
              $rowError = true;
              $content .= "<li>".__("Classification is not a valid entry in your site configuration:") . " " . $row["Classification"];
          } else {
            $iDept->Classification=$row["Classification"];
          }
        }


        if ( ! $hit && ! $rowError ) {
          $iDept->CreateDepartment();

          $content .= "<li>".__("Created new Department:") . " " . $iDept->Name;
        }

        if ( $rowError ) {
            $errors = true;
        }
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
