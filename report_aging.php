<?php
require_once ("db.inc.php");
require_once ("facilities.inc.php");

if(!$person->ReadAccess){
    // No soup for you.
    header('Location: '.redirect());
    exit;
}

define('FPDF_FONTPATH', 'font/');
require ('fpdf.php');

$dept = new Department();
$con = new People();

class DeviceAge extends Device
{
    function GetAge() {
        global $dbh;

        // count devices with unknown age
        $selectSQL = "SELECT COUNT(DeviceID) AS NumDevices,'Unknown age' AS NumYears FROM fac_Device WHERE (CASE WHEN MfgDate>'1969-12-31' THEN MfgDate ELSE InstallDate END)<='1970-01-01'";

        foreach ($dbh->query($selectSQL) as $row) {
            $deptList[$row['NumYears']] = $row['NumDevices'];
        }

        // count devices in each year, years 1 through 5
        for ($year = 1; $year <= 5; $year++) {
            $previous_year = $year - 1;

            $selectSQL = sprintf("SELECT COUNT(DeviceID) AS NumDevices,'%d<=%d years old' AS NumYears FROM fac_Device WHERE (DATEDIFF(NOW(),(CASE WHEN MfgDate>'1969-12-31' THEN MfgDate ELSE InstallDate END))/365)<=%d AND (DATEDIFF(NOW(), (CASE WHEN MfgDate>'1969-12-31' THEN MfgDate ELSE InstallDate END))/365)>%d", $previous_year, $year, $year, $previous_year);

            foreach ($dbh->query($selectSQL) as $row) {
                $deptList[$row['NumYears']] = $row['NumDevices'];
            }
        }

        // count devices older than 5 years
        $selectSQL = "SELECT COUNT(DeviceID) AS NumDevices,'>5 years old' AS NumYears FROM fac_Device WHERE (DATEDIFF(NOW(),MfgDate)/365)>5 AND MfgDate>'1970-01-01' AND InstallDate>'1970-01-01';";

        foreach ($dbh->query($selectSQL) as $row) {
            $deptList[$row['NumYears']] = $row['NumDevices'];
        }

        return $deptList;
    }

    function GetDeviceByAge($year) {
        global $dbh;
        $deviceList = array();
        if ($year <= 5) {
            $previous_year = $year - 1;
            $selectSQL = sprintf("SELECT * FROM fac_Device WHERE (DATEDIFF(NOW(), (CASE WHEN MfgDate>'1969-12-31' THEN MfgDate ELSE InstallDate END))/365)<%d AND (DATEDIFF(NOW(), (CASE WHEN MfgDate>'1969-12-31' THEN MfgDate ELSE InstallDate END))/365)>=%d ORDER BY Owner, MfgDate ASC, Label", $year, $previous_year);
            foreach ($dbh->query($selectSQL) as $deviceRow) {
                $deviceList[$deviceRow['DeviceID']] = Device::RowToObject($deviceRow);
            }
        }
        else {
            $selectSQL = "SELECT * FROM fac_Device WHERE (DATEDIFF(NOW(), (CASE WHEN MfgDate>'1969-12-31' THEN MfgDate ELSE InstallDate END))/365)>5 AND (CASE WHEN MfgDate>'1969-12-31' THEN MfgDate ELSE InstallDate END)>'1970-01-01' ORDER BY Owner, MfgDate ASC, Label";
            foreach ($dbh->query($selectSQL) as $deviceRow) {
                $deviceList[$deviceRow['DeviceID']] = Device::RowToObject($deviceRow);
            }
        }
        return $deviceList;
    }
}
$dev = new DeviceAge();
$cab = new Cabinet();
$dc = new DataCenter();

class PDF extends FPDF
{
    var $outlines = array();
    var $OutlineRoot;
    var $pdfconfig;
    var $pdfDB;

    function PDF() {
        parent::FPDF();
    }

    function Header() {
        $this->pdfconfig = new Config();
        if ( file_exists(  $this->pdfconfig->ParameterArray['PDFLogoFile'] )) {
            $this->Image(  $this->pdfconfig->ParameterArray['PDFLogoFile'],10,8,100);
        }
        $this->SetFont($this->pdfconfig->ParameterArray['PDFfont'], 'B', 12);
        $this->Cell(120);
        $this->Cell(30, 20, __("Information Technology Services"), 0, 0, 'C');
        $this->Ln(25);
        $this->SetFont($this->pdfconfig->ParameterArray['PDFfont'], '', 10);
        $this->Cell(50, 6, __("Data Center Asset Aging Report"), 0, 1, 'L');
        $this->Cell(50, 6, __("Date") . ': ' . date('d F Y'), 0, 1, 'L');
        $this->Ln(1);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont($this->pdfconfig->ParameterArray['PDFfont'], 'I', 8);
        $this->Cell(0, 10, __("Page") . ' ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function Bookmark($txt, $level = 0, $y = 0) {
        if ($y == - 1) $y = $this->GetY();
        $this->outlines[] = array('t' => $txt, 'l' => $level, 'y' => $y, 'p' => $this->PageNo());
    }

    function _putbookmarks() {
        $nb = count($this->outlines);
        if ($nb == 0) return;
        $lru = array();
        $level = 0;
        foreach ($this->outlines as $i => $o) {
            if ($o['l'] > 0) {
                $parent = $lru[$o['l'] - 1];

                //Set parent and last pointers
                $this->outlines[$i]['parent'] = $parent;
                $this->outlines[$parent]['last'] = $i;
                if ($o['l'] > $level) {

                    //Level increasing: set first pointer
                    $this->outlines[$parent]['first'] = $i;
                }
            }
            else $this->outlines[$i]['parent'] = $nb;
            if ($o['l'] <= $level and $i > 0) {

                //Set prev and next pointers
                $prev = $lru[$o['l']];
                $this->outlines[$prev]['next'] = $i;
                $this->outlines[$i]['prev'] = $prev;
            }
            $lru[$o['l']] = $i;
            $level = $o['l'];
        }

        //Outline items
        $n = $this->n + 1;
        foreach ($this->outlines as $i => $o) {
            $this->_newobj();
            $this->_out('<</Title ' . $this->_textstring($o['t']));
            $this->_out('/Parent ' . ($n + $o['parent']) . ' 0 R');
            if (isset($o['prev'])) $this->_out('/Prev ' . ($n + $o['prev']) . ' 0 R');
            if (isset($o['next'])) $this->_out('/Next ' . ($n + $o['next']) . ' 0 R');
            if (isset($o['first'])) $this->_out('/First ' . ($n + $o['first']) . ' 0 R');
            if (isset($o['last'])) $this->_out('/Last ' . ($n + $o['last']) . ' 0 R');
            $this->_out(sprintf('/Dest [%d 0 R /XYZ 0 %.2f null]', 1 + 2 * $o['p'], ($this->h - $o['y']) * $this->k));
            $this->_out('/Count 0>>');
            $this->_out('endobj');
        }

        //Outline root
        $this->_newobj();
        $this->OutlineRoot = $this->n;
        $this->_out('<</Type /Outlines /First ' . $n . ' 0 R');
        $this->_out('/Last ' . ($n + $lru[0]) . ' 0 R>>');
        $this->_out('endobj');
    }

    function _putresources() {
        parent::_putresources();
        $this->_putbookmarks();
    }

    function _putcatalog() {
        parent::_putcatalog();
        if (count($this->outlines) > 0) {
            $this->_out('/Outlines ' . $this->OutlineRoot . ' 0 R');
            $this->_out('/PageMode /UseOutlines');
        }
    }
}

class PDF_Sector extends PDF
{
    function Sector($xc, $yc, $r, $a, $b, $style = 'FD', $cw = true, $o = 90) {

        //Check for locale-related bug
        if (sprintf('%.1f', 1.0) != '1.0') setlocale(LC_NUMERIC, 'C');
        if ($cw) {
            $d = $b;
            $b = $o - $a;
            $a = $o - $d;
        }
        else {
            $b+= $o;
            $a+= $o;
        }
        $a = ($a % 360) + 360;
        $b = ($b % 360) + 360;
        if ($a > $b) $b+= 360;
        $b = $b / 360 * 2 * M_PI;
        $a = $a / 360 * 2 * M_PI;
        $d = $b - $a;
        if ($d == 0) $d = 2 * M_PI;
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F') $op = 'f';
        elseif ($style == 'FD' or $style == 'DF') $op = 'b';
        else $op = 's';
        if (sin($d / 2)) $MyArc = 4 / 3 * (1 - cos($d / 2)) / sin($d / 2) * $r;

        //first put the center
        $this->_out(sprintf('%.2f %.2f m', ($xc) * $k, ($hp - $yc) * $k));

        //put the first point
        $this->_out(sprintf('%.2f %.2f l', ($xc + $r * cos($a)) * $k, (($hp - ($yc - $r * sin($a))) * $k)));

        //draw the arc
        if ($d < M_PI / 2) {
            $this->_Arc($xc + $r * cos($a) + $MyArc * cos(M_PI / 2 + $a), $yc - $r * sin($a) - $MyArc * sin(M_PI / 2 + $a), $xc + $r * cos($b) + $MyArc * cos($b - M_PI / 2), $yc - $r * sin($b) - $MyArc * sin($b - M_PI / 2), $xc + $r * cos($b), $yc - $r * sin($b));
        }
        else {
            $b = $a + $d / 4;
            $MyArc = 4 / 3 * (1 - cos($d / 8)) / sin($d / 8) * $r;
            $this->_Arc($xc + $r * cos($a) + $MyArc * cos(M_PI / 2 + $a), $yc - $r * sin($a) - $MyArc * sin(M_PI / 2 + $a), $xc + $r * cos($b) + $MyArc * cos($b - M_PI / 2), $yc - $r * sin($b) - $MyArc * sin($b - M_PI / 2), $xc + $r * cos($b), $yc - $r * sin($b));
            $a = $b;
            $b = $a + $d / 4;
            $this->_Arc($xc + $r * cos($a) + $MyArc * cos(M_PI / 2 + $a), $yc - $r * sin($a) - $MyArc * sin(M_PI / 2 + $a), $xc + $r * cos($b) + $MyArc * cos($b - M_PI / 2), $yc - $r * sin($b) - $MyArc * sin($b - M_PI / 2), $xc + $r * cos($b), $yc - $r * sin($b));
            $a = $b;
            $b = $a + $d / 4;
            $this->_Arc($xc + $r * cos($a) + $MyArc * cos(M_PI / 2 + $a), $yc - $r * sin($a) - $MyArc * sin(M_PI / 2 + $a), $xc + $r * cos($b) + $MyArc * cos($b - M_PI / 2), $yc - $r * sin($b) - $MyArc * sin($b - M_PI / 2), $xc + $r * cos($b), $yc - $r * sin($b));
            $a = $b;
            $b = $a + $d / 4;
            $this->_Arc($xc + $r * cos($a) + $MyArc * cos(M_PI / 2 + $a), $yc - $r * sin($a) - $MyArc * sin(M_PI / 2 + $a), $xc + $r * cos($b) + $MyArc * cos($b - M_PI / 2), $yc - $r * sin($b) - $MyArc * sin($b - M_PI / 2), $xc + $r * cos($b), $yc - $r * sin($b));
        }

        //terminate drawing
        $this->_out($op);

        //Return to the original numeric formatting
        setlocale(LC_NUMERIC, NULL);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c', $x1 * $this->k, ($h - $y1) * $this->k, $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
    }
}

class PDF_Diag extends PDF_Sector
{
    var $legends;
    var $wLegend;
    var $sum;
    var $NbVal;

    function PieChart($w, $h, $data, $format, $colors = null) {
        $this->SetFont($this->pdfconfig->ParameterArray['PDFfont'], '', 10);
        $this->SetLegends($data, $format);

        $XPage = $this->GetX();
        $YPage = $this->GetY();
        $margin = 2;
        $hLegend = 5;
        $radius = min($w - $margin * 4 - $hLegend - $this->wLegend, $h - $margin * 2);
        $radius = floor($radius / 2);
        $XDiag = $XPage + $margin + $radius;
        $YDiag = $YPage + $margin + $radius;
        if ($colors == null) {
            for ($i = 0; $i < $this->NbVal; $i++) {
                $gray = $i * intval(255 / $this->NbVal);
                $colors[$i] = array($gray, $gray, $gray);
            }
        }

        //Sectors
        $this->SetLineWidth(0.2);
        $angleStart = 0;
        $angleEnd = 0;
        $i = 0;
        foreach ($data as $val) {
            $angle = ($this->sum == 0) ? 0 : round(($val * 360) / doubleval($this->sum));
            if ($angle != 0) {
                $angleEnd = $angleStart + $angle;
                $this->SetFillColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
                $this->Sector($XDiag, $YDiag, $radius, $angleStart, $angleEnd);
                $angleStart+= $angle;
            }
            $i++;
        }
        if ($angleEnd != 360) {
            $this->Sector($XDiag, $YDiag, $radius, $angleStart - $angle, 360);
        }

        //Legends
        $this->SetFont($this->pdfconfig->ParameterArray['PDFfont'], '', 10);
        $x1 = $XPage + 2 * $radius + 4 * $margin;
        $x2 = $x1 + $hLegend + $margin;
        $y1 = $YDiag - $radius + (2 * $radius - $this->NbVal * ($hLegend + $margin)) / 2;
        for ($i = 0; $i < $this->NbVal; $i++) {
            $this->SetFillColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
            $this->Rect($x1, $y1, $hLegend, $hLegend, 'DF');
            $this->SetXY($x2, $y1);
            $this->Cell(0, $hLegend, $this->legends[$i]);
            $y1+= $hLegend + $margin;
        }
    }

    function BarDiagram($w, $h, $data, $format, $color = null, $maxVal = 0, $nbDiv = 4) {
        $this->SetFont('Courier', '', 10);
        $this->SetLegends($data, $format);

        $XPage = $this->GetX();
        $YPage = $this->GetY();
        $margin = 2;
        $YDiag = $YPage + $margin;
        $hDiag = floor($h - $margin * 2);
        $XDiag = $XPage + $margin * 2 + $this->wLegend;
        $lDiag = floor($w - $margin * 3 - $this->wLegend);
        if ($color == null) $color = array(155, 155, 155);
        if ($maxVal == 0) {
            $maxVal = max($data);
        }
        $valIndRepere = ceil($maxVal / $nbDiv);
        $maxVal = $valIndRepere * $nbDiv;
        $lRepere = floor($lDiag / $nbDiv);
        $lDiag = $lRepere * $nbDiv;
        $unit = $lDiag / $maxVal;
        $hBar = floor($hDiag / ($this->NbVal + 1));
        $hDiag = $hBar * ($this->NbVal + 1);
        $eBaton = floor($hBar * 80 / 100);

        $this->SetLineWidth(0.2);
        $this->Rect($XDiag, $YDiag, $lDiag, $hDiag);

        $this->SetFont('Courier', '', 10);
        $this->SetFillColor($color[0], $color[1], $color[2]);
        $i = 0;
        foreach ($data as $val) {

            //Bar
            $xval = $XDiag;
            $lval = (int)($val * $unit);
            $yval = $YDiag + ($i + 1) * $hBar - $eBaton / 2;
            $hval = $eBaton;
            $this->Rect($xval, $yval, $lval, $hval, 'DF');

            //Legend
            $this->SetXY(0, $yval);
            $this->Cell($xval - $margin, $hval, $this->legends[$i], 0, 0, 'R');
            $i++;
        }

        //Scales
        for ($i = 0; $i <= $nbDiv; $i++) {
            $xpos = $XDiag + $lRepere * $i;
            $this->Line($xpos, $YDiag, $xpos, $YDiag + $hDiag);
            $val = $i * $valIndRepere;
            $xpos = $XDiag + $lRepere * $i - $this->GetStringWidth($val) / 2;
            $ypos = $YDiag + $hDiag - $margin;
            $this->Text($xpos, $ypos, $val);
        }
    }

    function SetLegends($data, $format) {
        $this->legends = array();
        $this->wLegend = 0;
        $this->sum = array_sum($data);
        $this->NbVal = count($data);
        foreach ($data as $l => $val) {
            $p = sprintf('%.2f', (($this->sum > 0) ? $val / $this->sum * 100 : 0)) . '%';
            $legend = str_replace(array('%l', '%v', '%p'), array($l, $val, $p), $format);
            $this->legends[] = $legend;
            $this->wLegend = max($this->GetStringWidth($legend), $this->wLegend);
        }
    }
}

// get a breakdown of device ages
$agingList = $dev->GetAge();

// find devices older than five years
$oldestlist = $dev->GetDeviceByAge(6);

// iterate for years 0-1, 1-2, 2-3, 3-4, 4-5
for ($year = 1; $year <= 5; $year++) {
    $age_list[$year] = $dev->GetDeviceByAge($year);
}

//
//
//  Begin Report Generation
//
//

$pdf = new PDF_Diag();
include_once ("loadfonts.php");
$pdf->AliasNbPages();

// first page - the pie chart

$pdf->AddPage();
// pick some colors: grey for unknown, then step from green to red to indicate increasing age
$colors[0] = array(175, 175, 175);
$colors[1] = array(0, 255, 0);
$colors[2] = array(204, 255, 102);
$colors[3] = array(255, 255, 100);
$colors[4] = array(255, 204, 102);
$colors[5] = array(204, 102, 0);
$colors[6] = array(255, 0, 0);
$colors[7] = array(0, 0, 255);

$pdf->SetFont($config->ParameterArray['PDFfont'], 'B', 16);

$pdf->Cell(0, 18, __("Device Ages by Count"), '', 1, 'C', 0);
$pdf->SetXY(10, 70);
$pdf->PieChart(200, 80, $agingList, '%l : %v devices (%p)', $colors);


//
// cycle through years 1-3 and produce table reports
//
for ($year = 1; $year <= 5; $year++) {
    $start_year = $year - 1;

    $pdf->AddPage();
    $pdf->SetFillColor(224, 235, 255);
    $pdf->SetFont($config->ParameterArray['PDFfont'], 'B', 16);
    $pdf->Cell(0, 15, __("Devices between $start_year and $year years old"), '', 1, 'C', 0);
    $pdf->SetFont($config->ParameterArray['PDFfont'], '', 10);
    $headerTags = array(__("Label"), __("Age"), __("Owner"), __("Primary Contact"));
    $cellWidths = array(45, 30, 50, 45);
    $maxval = count($headerTags);
    for ($col = 0; $col < $maxval; $col++) $pdf->Cell($cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 0);
    $pdf->Ln();
    $fill = 1;
    foreach ($age_list[$year] as $devRow) {
        $dept->DeptID = $devRow->Owner;
        $dept->GetDeptByID();
        $con->PersonID = $devRow->PrimaryContact;
        $con->GetPerson();
        if ($devRow->MfgDate > "1970-01-01") $date1 = new DateTime($devRow->MfgDate);
        else $date1 = new DateTime($devRow->InstallDate);

        $date2 = new DateTime('now');
        $interval = $date1->diff($date2);
        $years = $interval->format('%y y %m m %d d');

        $cellHeight = 6;

        $pdf->Cell($cellWidths[0], $cellHeight, $devRow->Label, 1, 0, 'L', $fill);
        $pdf->Cell($cellWidths[1], $cellHeight, $years, 1, 0, 'L', $fill);
        $pdf->Cell($cellWidths[2], $cellHeight, $dept->Name, 1, 0, 'L', $fill);
        $pdf->Cell($cellWidths[3], $cellHeight, $con->FirstName . ' ' . $con->LastName, 1, 1, 'L', $fill);

        $fill = !$fill;
    }
}

// last page, listing ancient devices

$pdf->AddPage();
$pdf->SetFont($config->ParameterArray['PDFfont'], 'B', 16);
$pdf->Cell(0, 15, __("Devices more than 5 years old"), '', 1, 'C', 0);
$pdf->SetFont($config->ParameterArray['PDFfont'], '', 10);
$headerTags = array(__("Label"), __("Age"), __("Owner"), __("Primary Contact"));
$cellWidths = array(45, 30, 50, 45);
$maxval = count($headerTags);
for ($col = 0; $col < $maxval; $col++) $pdf->Cell($cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 0);
$pdf->Ln();
$fill = 1;
if (count($oldestlist) > 0) {
    foreach ($oldestlist as $devRow) {
        $dept->DeptID = $devRow->Owner;
        $dept->GetDeptByID();
        $con->PersonID = $devRow->PrimaryContact;
        $con->GetPerson();
        $date1 = new DateTime($devRow->MfgDate);
        $date2 = new DateTime('now');
        $interval = $date1->diff($date2);
        $years = $interval->format('%y y %m m %d d');

        $pdf->Cell($cellWidths[0], 6, $devRow->Label, 'LBRT', 0, 'L', $fill);
        $pdf->Cell($cellWidths[1], 6, $years, 'LBRT', 0, 'L', $fill);
        $pdf->Cell($cellWidths[2], 6, $dept->Name, 'LBRT', 0, 'L', $fill);
        $pdf->Cell($cellWidths[3], 6, $con->FirstName . ' ' . $con->LastName, 'LBRT', 1, 'L', $fill);

        $fill = !$fill;
    }
}

$pdf->Output();
?>
