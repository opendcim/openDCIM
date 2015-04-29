<?php
/* Loading custom user fonts for the FPDF library
Place corresponding php-files of fonts in the font directory */

  // For example, set of cyrillic (cp1251) TrueType fonts
	/* DejaVuSans */
	$pdf->AddFont('DejaVuSans','','DejaVuSans.php');
	$pdf->AddFont('DejaVuSans','B','DejaVuSans-Bold.php');
	$pdf->AddFont('DejaVuSans','I','DejaVuSans-BoldOblique.php');
	$pdf->AddFont('DejaVuSans','BI','DejaVuSans-BoldOblique.php');

	/* OpenSans-Bold */
	$pdf->AddFont('OpenSans-Bold','','OpenSans-Bold.php');
	$pdf->AddFont('OpenSans-Bold','B','OpenSans-ExtraBold.php');
	$pdf->AddFont('OpenSans-Bold','I','OpenSans-BoldItalic.php');
	$pdf->AddFont('OpenSans-Bold','BI','OpenSans-ExtraBoldItalic.php');

	/* OpenSans-Cond */
	$pdf->AddFont('OpenSans-Cond','','OpenSans-CondLight.php');
	$pdf->AddFont('OpenSans-Cond','B','OpenSans-CondBold.php');
	$pdf->AddFont('OpenSans-Cond','I','OpenSans-CondLightItalic.php');
	$pdf->AddFont('OpenSans-Cond','BI','OpenSans-CondBold.php');
?>
