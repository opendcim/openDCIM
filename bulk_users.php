<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

  if(!$person->BulkOperations){
    header('Location: '.redirect());
    exit;
  }

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Bulk User Authorization Importer");

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

    $content = "<h3>" . __("Pick the appropriate column header (line 1) for each field name listed below.  Any UserID already in the system will be updated." ) . "</h3>";
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

    foreach ( array( "LastName"=>"The last (family) name of the user account being imported.", "FirstName"=>"The first (given) name of the user account being imported.", "UserID"=>"The UserID, as returned by the authentication mechanism (apache, LDAP, etc), of the user account being imported.   Required unique.", "Email"=>"Email address of the user account being imported.   Required.", "Phone1"=>"A phone number for contacting the person.", "Phone2"=>"A phone number for contacting the person.", "Phone3"=>"A phone number for contacting the person.", "AdminOwnDevices"=>"Y/Yes/1 means true that the user has the ability to read/write/delete any devices owned by a department they are a member of.", "ReadAccess"=>"Y/Yes/1 means true that the user has global read access in the system.", "WriteAccess"=>"Y/Yes/1 means true that the user has global write/enter/modify access in the system.", "DeleteAccess"=>"Y/Yes/1 means true that the user has global delete access within the system.", "ContactAdmin"=>"Y/Yes/1 means true that the user has rights to enter and modify the user accounts of others in the system.", "RackRequest"=>"Y/Yes/1 means true that the user is allowed to enter Rack Requests in the system.", "RackAdmin"=>"Y/Yes/1 means true that the user is allowed to process/complete rack requests in the system.", "SiteAdmin"=>"Y/Yes/1 means true that the user has the ability to perform upgrades to the openDCIM installation, and to modify infrastructure such as cabinets, data centers, and power distribution.", "BulkOperations"=>"Y/Yes/1 means true that the user has the ability to access the Bulk Importer functions within openDCIM.", "DepartmentMembership"=>"Optional, comma separated list of departments to add the UserID to.   Will not remove from any departments absent in the list during an update." ) as $fieldName=>$helpText ) {
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
    $fields = array( "LastName", "FirstName", "UserID", "Email", "Phone1", "Phone2", "Phone3", "AdminOwnDevices", "ReadAccess", "WriteAccess", "DeleteAccess", "ContactAdmin", "RackRequest", "RackAdmin", "SiteAdmin", "BulkOperations", "DepartmentMembership" );

    $iPerson = new People();
    $trueArray = array( "1", "Y", "YES" );

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
      if ( $row["UserID"] == "" ) {
        break;
      }


      $iPerson->UserID = $row["UserID"];
      $hit = $iPerson->GetPersonByUserID();
      $iPerson->LastName = $row["LastName"];
      $iPerson->FirstName = $row["FirstName"];
      $iPerson->Email = $row["Email"];
      $iPerson->Phone1 = $row["Phone1"];
      $iPerson->Phone2 = $row["Phone2"];
      $iPerson->Phone3 = $row["Phone3"];
      $iPerson->AdminOwnDevices = in_array( strtoupper($row["AdminOwnDevices"]), $trueArray );
      $iPerson->ReadAccess = in_array( strtoupper($row["ReadAccess"]), $trueArray );
      $iPerson->WriteAccess = in_array( strtoupper($row["WriteAccess"]), $trueArray );
      $iPerson->DeleteAccess = in_array( strtoupper($row["DeleteAccess"]), $trueArray );
      $iPerson->ContactAdmin = in_array( strtoupper($row["ContactAdmin"]), $trueArray );
      $iPerson->RackRequest = in_array( strtoupper($row["RackRequest"]), $trueArray );
      $iPerson->RackAdmin = in_array( strtoupper($row["RackAdmin"]), $trueArray );
      $iPerson->SiteAdmin = in_array( strtoupper($row["SiteAdmin"]), $trueArray );
      $iPerson->BulkOperations = in_array( strtoupper($row["BulkOperations"]), $trueArray );

      if ( ! $hit ) {
        $iPerson->CreatePerson();

        $content .= "<li>".__("Created new person account:") . " " . $iPerson->UserID;
      } else {
        $iPerson->UpdatePerson();

        $content .= "<li>".__("Updated existing person account:") . " " .$iPerson->UserID;
      }

      if ( $row["DepartmentMembership"] > "" ) {
        $deptList = explode( ',', $row["DepartmentMembership"]);
        $st = $dbh->prepare( "select DeptID from fac_Department where ucase(Name)=ucase( :Name )" );
        $dpt = $dbh->prepare( "insert into fac_DeptContacts set ContactID=:ContactID, DeptID=:DeptID on duplicate key update DeptID=:DeptID");
        foreach( $deptList as $deptName ) {
          error_log( "Processing department " . $deptName );
          // Check for the Department to be valid
          $st->execute( array( ":Name"=>$deptName ));
          if ( ! $val = $st->fetch() ) {
            $valid = false;
          } else {
            error_log( "Adding ContactID:".$iPerson->PersonID." to Department " . $val[0]);
            $dpt->execute( array( ":ContactID"=>$iPerson->PersonID, ":DeptID"=>$val[0] ));
          }
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
