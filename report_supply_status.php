<?php
/*		Supply Status Report
		Will print out all supply types, list the Min/Max/Current quantities along with current locations
*/

	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	if(!$person->ReadAccess){
	    // No soup for you.
	    header('Location: '.redirect());
	    exit;
	}

	/* Version 2.0 of this report uses PhpSpreadsheet instead of mPDF */

	$xl = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

	$xl->getProperties()->setCreator($config->ParameterArray["OrgName"]);
	$xl->getProperties()->setLastModifiedBy("openDCIM");
	$xl->getProperties()->setTitle($config->ParameterArray["OrgName"] . " " . __("Supply Status Report"));
	$xl->getProperties()->setSubject(__("Supply Status Report"));

	$xl->setActiveSheetIndex(0);
	$currSheet = $xl->getActiveSheet();
	$currSheet->setTitle(__("Supply Status"));

	// Header row
	$currSheet->setCellValue('A1', __("Part Number"));
	$currSheet->setCellValue('B1', __("Part Name"));
	$currSheet->setCellValue('C1', __("Min Qty"));
	$currSheet->setCellValue('D1', __("Max Qty"));
	$currSheet->setCellValue('E1', __("On Hand"));
	$currSheet->setCellValue('F1', __("Location"));
	$currSheet->setCellValue('G1', __("Location Count"));

	// Bold header row
	$currSheet->getStyle('A1:G1')->getFont()->setBold(true);
	$currSheet->getStyle('A1:G1')->getFill()
		->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
		->getStartColor()->setRGB('EEEEEE');

	$sup = new Supplies();
	$bin = new SupplyBin();
	$bc = new BinContents();

	$SupplyList = $sup->GetSuppliesList();

	$row = 2;

	foreach ( $SupplyList as $Supply ) {
		$onHand = Supplies::GetSupplyCount($Supply->SupplyID);

		$currSheet->setCellValue("A{$row}", $Supply->PartNum);
		$currSheet->setCellValue("B{$row}", $Supply->PartName);
		$currSheet->setCellValue("C{$row}", $Supply->MinQty);
		$currSheet->setCellValue("D{$row}", $Supply->MaxQty);
		$currSheet->setCellValue("E{$row}", $onHand);

		// Highlight row if on hand is below minimum
		if ($onHand < $Supply->MinQty) {
			$currSheet->getStyle("A{$row}:G{$row}")->getFill()
				->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
				->getStartColor()->setRGB('F28A8C');
		}

		$bc->SupplyID = $Supply->SupplyID;
		$binList = $bc->FindSupplies();

		foreach ( $binList as $sb ) {
			$bin->BinID = $sb->BinID;
			$bin->GetBin();

			$currSheet->setCellValue("F{$row}", $bin->Location);
			$currSheet->setCellValue("G{$row}", $sb->Count);
			$row++;
		}

		if (empty($binList)) {
			$row++;
		}
	}

	foreach( range( 'A', 'G' ) as $col ) {
		$currSheet->getColumnDimension($col)->setAutoSize( true );
	}
	$currSheet->calculateColumnWidths();

	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header( sprintf( "Content-Disposition: attachment;filename=\"opendcim-supply-status-%s.xlsx\"", date( "YmdHis" ) ) );

	$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($xl);
	$writer->save('php://output');
	exit;
?>
