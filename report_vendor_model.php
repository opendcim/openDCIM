<?php
/*	Template file for creating Excel based reports
	
	Basically just the setup of the front page for consistency
*/

	require_once "db.inc.php";
	require_once "facilities.inc.php";
	require_once "vendor/autoload.php";

	$person = People::Current();

    // Get the parameters for the report of none have been specified

    if (!isset($_REQUEST['action'])){
?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html>
    <head>
      <meta http-equiv="X-UA-Compatible" content="IE=Edge">
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <title>openDCIM Vendor/Model Reporting</title>
      <link rel="stylesheet" href="css/inventory.php" type="text/css">
      <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
      <script type="text/javascript" src="scripts/jquery.min.js"></script>
      <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>

    </head>
    <body>
    <?php include( 'header.inc.php' ); ?>
<?php
    include( 'sidebar.inc.php' );

    $m = new Manufacturer();
    $manList = $m->GetManufacturerList();
        
 ?>
    </div>
    <div class="main">
    <h2>openDCIM</h2>
    <h3>Vendor Model Report</h3>
    <form method="post">
    <div class="table">
    <div>
        <div></div>

<?php
    print "<div>" . __("Please select the values to limit the report scope as indicated below.") . "</div></div>";

    print "<div><div>" . __("Manufacturer") . "</div><div>";
    print "<select name=\"manufacturerid\"><option value=0>All</option>";

    foreach ( $manList as $man ) {
        printf( "<option value=%d>%s</option>\n", $man->ManufacturerID, $man->Name );
    }

    print "</select></div></div>";

    print "<div><div>" . __("Device Type") . "</div><div>";
    print "<select name=\"devicetype\"><option value=\"all\">All</option>";

    foreach ( array( "Server", "Appliance", "Storage Array", "Switch", "Chassis", "Patch Panel", "Physical Infrastructure", "CDU", "Sensor" ) as $devType ) {
        printf( "<option value=\"%s\">%s</option>\n", $devType, $devType );
    }

    print "</select></div></div>";
?>

    <div>
        <div></div>
        <div><input type="submit" name="action" value="Submit"></div>
    </div>
    </form>
<?php
    } else {
        // Check to see if someone tried to pass the URL through but forgot the important stuff

        if ( ! isset( $_REQUEST['manufacturerid'] ) || ! isset( $_REQUEST['devicetype'])) {
            // Redirect to the same page, but strip off the invalid parameters
            echo "<meta http-equiv='refresh' content='0; url=report_vendor_model.php'>";
            exit;
        }

        $man = new Manufacturer();
        $manID = intval( $_REQUEST['manufacturerid'] );
        $dType = in_array( $_REQUEST['devicetype'], array( "Server", "Appliance", "Storage Array", "Switch", "Chassis", "Patch Panel", "Physical Infrastructure", "CDU", "Sensor", "All"))?$_REQUEST['devicetype']:"All";

        if ( $manID > 0 ) {
            $man->ManufacturerID = $manID;
            $man->GetManufacturerByID();
            $mfgName = $man->Name;
        } else {
            $mfgName = "All";
        }

        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;
        $retcode = PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

        $workBook = new PHPExcel();

    	$workBook->getProperties()->setCreator("openDCIM");
    	$workBook->getProperties()->setLastModifiedBy("openDCIM");
    	$workBook->getProperties()->setTitle("Data Center Inventory Export");
    	$workBook->getProperties()->setSubject("Vendor Model Export");
    	$workBook->getProperties()->setDescription("Export of the openDCIM database based upon user filtered criteria.");
    	
    	// Start off with the TPS Cover Page

    	$workBook->setActiveSheetIndex(0);
    	$sheet = $workBook->getActiveSheet();

        $sheet->SetTitle('Front Page');
        // add logo
        $objDrawing = new PHPExcel_Worksheet_Drawing();
        $objDrawing->setWorksheet($sheet);
        $objDrawing->setName("Logo");
        $objDrawing->setDescription("Logo");
        $apath = __DIR__ . DIRECTORY_SEPARATOR ;
        $objDrawing->setPath($apath . $config->ParameterArray['PDFLogoFile']);
        $objDrawing->setCoordinates('A1');
        $objDrawing->setOffsetX(5);
        $objDrawing->setOffsetY(5);

        $logoHeight = getimagesize( $apath . $config->ParameterArray['PDFLogoFile']);
        $sheet->getRowDimension('1')->setRowHeight($logoHeight[1]);

        // set the header of the print out
        $header_range = "A1:B2";
        $fillcolor = $config->ParameterArray['HeaderColor'];
        $fillcolor = (strpos($fillcolor, '#') == 0) ? substr($fillcolor, 1) : $fillcolor;
        $sheet->getStyle($header_range)
            ->getFill()
            ->getStartColor()
            ->setRGB($fillcolor);

        $org_font_size = 20;
        $sheet->setCellValue('A2', $config->ParameterArray['OrgName']);
        $sheet->getStyle('A2')
            ->getFont()
            ->setSize($org_font_size);
        $sheet->getStyle('A2')
            ->getFont()
            ->setBold(true);
        $sheet->getRowDimension('2')->setRowHeight($org_font_size + 2);
        $sheet->setCellValue('A4', 'Report generated by \''
            . $person->UserID
            . '\' on ' . date('Y-m-d H:i:s'));

        // Add text about the report itself
        $sheet->setCellValue('A7', 'Notes');
        $sheet->getStyle('A7')
            ->getFont()
            ->setSize(14);
        $sheet->getStyle('A7')
            ->getFont()
            ->setBold(true);

        $remarks = array( __("This is the Vendor(Manufacturer)/Model report from openDCIM."),
        		__("Each manufacturer is listed in a separate worksheet, with devices listed lexicographically by Label."),
        		__("The criteria given for this report is:"),
                __("Manufacturer:") . $mfgName,
                __("Device Type:") . $dType );

        $max_remarks = count($remarks);
        $offset = 8;
        for ($idx = 0; $idx < $max_remarks; $idx ++) {
            $row = $offset + $idx;
            $sheet->setCellValueExplicit('B' . ($row),
                $remarks[$idx],
                PHPExcel_Cell_DataType::TYPE_STRING);
        }
        $sheet->getStyle('B' . $offset . ':B' . ($offset + $max_remarks - 1))
            ->getAlignment()
            ->setWrapText(true);
        $sheet->getColumnDimension('B')->setWidth(120);
        $sheet->getTabColor()->setRGB($fillcolor);

        // Now the real data for the report


        $dev = new Device();
        $cab = new Cabinet();
        $dc = new DataCenter();
        $dep = new Department();

        $dcList = $dc->Search( true );
        $cabList = $cab->Search( true );
        $depList = $dep->Search( true );

        if ( $manID == 0 ) {
            $manList = $man->GetManufacturerList();
        } else {
            $man->ManufacturerID = $manID;
            $man->GetManufacturerByID();
            $manList = array( $man );
        }

        // Build out the column mapping
        $columnList = array( "Label"=>"A", "Model"=>"B", "DeviceType"=>"C", "DataCenter"=>"D", "Cabinet"=>"E", "Position"=>"F", "Height"=>"G", "Department"=>"H", "SerialNo"=>"I", "AssetTag"=>"J", "Status"=>"K", "InstallDate"=>"L", "Hypervisor"=>"M" );
        $DCAList = DeviceCustomAttribute::GetDeviceCustomAttributeList();

        // In case we tweak the number of base columns before the Custom Attributes, this keeps us from having to update the math
        $columnNum = count($columnList);
        foreach( $DCAList as $dca ) {
            $colName = getNameFromNumber(++$columnNum);
            $labelName = $dca->Label;
            $columnList[$labelName] = $colName;
        }

        foreach( $manList as $mfg ) {
            $dt = new DeviceTemplate();
            $dt->ManufacturerID = $mfg->ManufacturerID;
            $templateList = $dt->Search();

            if ( sizeof($templateList) > 0 ) {
            	$sheet = $workBook->createSheet();
            	$sheet->setTitle( $mfg->Name );

                foreach( $columnList as $fieldName=>$columnName ) {
                    $cellAddr = $columnName."1";
      
                    $sheet->setCellValue( $cellAddr, $fieldName );
                }
                
                $currRow = 2;


                foreach ( $templateList as $t ) {
                    $dev = new Device();
                    $dev->TemplateID = $t->TemplateID;

                    if ( $dType != "All" ) {
                        $dev->DeviceType = $dType;
                    }

                    $devList = $dev->Search();

                    foreach ( $devList as $d ) {
                        foreach( $d as $prop=>$val ) {
                            if ( array_key_exists( $prop, $columnList )) {
                                $sheet->setCellValue( $columnList[$prop].$currRow, $val );
                            }
                        }

                        $sheet->setCellValue( $columnList["Model"].$currRow, $t->Model );

                        if ( $d->Cabinet == -1 ) {
                            $sheet->setCellValue( $columnList["Cabinet"].$currRow, "Storage" );
                            $sheet->setCellValue( $columnList["DataCenter"].$currRow, $dcList[$d->Position]->Name);
                        } else {
                            $thisCab = $cabList[$d->Cabinet];
                            $sheet->setCellValue( $columnList["Cabinet"].$currRow, $thisCab->Location);
                            $sheet->setCellValue( $columnList["DataCenter"].$currRow, $dcList[$thisCab->DataCenterID]->Name);
                        }
                        $sheet->setCellValue( $columnList["Department"].$currRow, $depList[$d->Owner]->Name );

                        $currRow++;
                    }
                }

                foreach( $columnList as $i => $v ) {
                    $sheet->getColumnDimension($v)->setAutoSize(true);
                }

                if ( $currRow == 2 ) {
                    // Nothing got added, so delete this sheet
                    $workBook->removeSheetByIndex(
                        $workBook->getIndex(
                            $workBook->getSheetByName($mfg->Name)));
                }
            }
        }

    	// Now finalize it and send to the client



        $workBook->setActiveSheetIndex(0);

    	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    	header( sprintf( "Content-Disposition: attachment;filename=\"opendcim-%s.xlsx\"", date( "YmdHis" ) ) );
    	
    	$writer = new PHPExcel_Writer_Excel2007($workBook);
    	$writer->save('php://output');
    }
?>