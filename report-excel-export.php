<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );
	require_once( "PHPExcel/PHPExcel.php" );
	require_once( "PHPExcel/PHPExcel/Writer/Excel2007.php" );

	if(!$person->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}	
	$sheet = new PHPExcel();
	
	$sheet->getProperties()->setCreator("openDCIM");
	$sheet->getProperties()->setLastModifiedBy("openDCIM");
	$sheet->getProperties()->setTitle("Data Center Inventory Export");
	$sheet->getProperties()->setSubject("Data Center Inventory Export");
	$sheet->getProperties()->setDescription("Export of the openDCIM database based upon user filtered criteria.");
	
	$sheet->setActiveSheetIndex(0);
	$sheet->getActiveSheet()->SetCellValue('A1','Data Center');
	$sheet->getActiveSheet()->SetCellValue('B1','Location');
	$sheet->getActiveSheet()->SetCellValue('C1','Position');
	$sheet->getActiveSheet()->SetCellValue('D1','Device Name');
	$sheet->getActiveSheet()->SetCellValue('E1','Height');
	$sheet->getActiveSheet()->SetCellValue('F1','Device Type');
	$sheet->getActiveSheet()->SetCellValue('G1','Device Model');
	$sheet->getActiveSheet()->SetCellValue('H1','Owner');
	
	$sheet->getActiveSheet()->setTitle("Assets");
	
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header( sprintf( "Content-Disposition: attachment;filename=\"opendcim-%s.xlsx\"", date( "YmdHis" ) ) );
	
	$writer = new PHPExcel_Writer_Excel2007($sheet);
	$writer->save('php://output');
?>