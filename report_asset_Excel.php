<?php
/**
 * This is a library to create an Excel file with the information on all devices
 * and cabinets in all data centers serialized.
 *
 * With this library an Excel file is create which contains a header worksheet,
 * a worksheet with the data center statistics, a worksheet with the serialized
 * device information and a worksheet with the serialized cabinet information.
 *
 * Be aware that the creation of an Excel file requires lots of CPU and memory
 * resources, e.g. depending on the number of objects managed it and your CPU
 * power available it can be likely 3 minutes or more runtime and up to 400MB
 * memory.
 *
 */
require_once 'db.inc.php';
require_once 'facilities.inc.php';

$ReportOutputFolder = "/tmp/";

if(!$person->ReadAccess){
    // No soup for you.
    header('Location: '.redirect());
    exit;
}

global $sessID;

// everyone hates error_log spam
if(session_id()==""){
	session_start();
}
$sessID = session_id();
session_write_close();

if (php_sapi_name()!="cli" && !isset($_GET["stage"])) {
    // This is the top leve/first call to the file, so set up the progress bar, etc.

    JobQueue::startJob( $sessID );

    $title = __("Asset Report (Excel)");

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

  <title><?php echo $title; ?></title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <style type="text/css"></style>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/common.js?v<?php echo filemtime('scripts/common.js');?>"></script>
  <script type="text/javascript" src="scripts/gauge.min.js"></script>

<SCRIPT type="text/javascript" >
var timer;
var gauge;

$(document).ready( function() {
	gauge=new Gauge({
		renderTo: 'power-gauge',
		type: 'canv-gauge',
		title: '% Complete',
		minValue: '0',
		maxValue: '100',
		majorTicks: [ 0,10,20,30,40,50,60,70,80,90,100 ],
		minorTicks: '2',
		strokeTicks: false,
		units: '%',
		valueFormat: { int : 3, dec : 0 },
		glow: false,
		animation: {
			delay: 10,
			duration: 200,
			fn: 'bounce'
			},
		colors: {
			needle: {start: '#000', end: '#000' },
			title: '#00f',
			},
		highlights: [ {from: 0, to: 50, color: '#eaa'}, {from: 50, to: 80, color: '#fffacd'}, {from: 80, to: 100, color: '#0a0'} ],
		});
	gauge.draw().setValue(0);
    timer = setInterval( function() {
        $.ajax({
            type: 'GET',
            url: 'scripts/ajax_progress.php',
            dataType: 'json',
            success: function(data) {
                $("#status").text(data.Status);
				gauge.draw().setValue(data.Percentage);
                if ( data.Percentage >= 100 ) {
                    clearInterval(timer);
                    // Reload with Stage 3 to send the file to the user
                }
            }
        })
    }, 1500 );

    init=$('<iframe/>', {'src':location.href+'?stage=2', height:'100px',width:'100px'}).appendTo('body');
});
</script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<div class="center"><div>
<h3 id="status">Starting</h3>
<div><canvas id="power-gauge" width="200" height="200"></canvas></div>


</div></div>
<?php echo '<a href="reports.php">[ ',__("Return to Reports"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>


<?php
    exit;
}



// TODO: Potentially sorting of rack inventory might need to be done not
// according to the data center ID but to the names

// Error reporting
error_reporting(E_ALL);
ini_set('memory_limit', '840M');
ini_set('max_execution_time', '0');

// Configuration variables.
// Properties of the document and worksheets.
$DProps = array(
    'Doc' => array(
        'version' => 1.0,
        'Subject' => __("Asset Report"),
        'Description' => __("Data Center Statistics on all data centers assets."),
        'Title' => __("Data Center Statistics"),
        'Keywords' => __("datacenter report assets statistic"),
        'PageSize' => $config->ParameterArray['PageSize'],
        'User' => $person->LastName . ", " . $person->FirstName,
        'UserID' => $person->UserID
    ),
    'Front Page' => array(
        'Title' => '&L&B' . __("Notes on DC Statistics") . '&R&A',
        'FillColor' => 'DCE6F1',
        'HeadingFontColor' => '000000',
        'HeaderRange' => 'A1:B2',
        'HeaderHeight' => 60,
        'Border color' => '95B3D7',
        'Logo Name' => __("Logo Name"),
        'Logo Description' => __("Logo Description"),
        'PageSize' => $config->ParameterArray['PageSize'],
        'Orientation' => PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT,
        'Columns' => array(
            array('', '', null, null),
            array('', '', null, null)
        ),
        'remarks' => array(
            __("● This is the combined report on all data center assets. It "
                . "contains the list of all devices and a list of all cabinets."),
            __("● Using Excel\"s pivot capabilities many different analysis and "
                . "statistics can be performed."),
            __("● The terms \"cabinet\" and  \"rack\" are used interchangeable."),
            __("● For user who have only the \"Admin Own Devices\" right the report "
                . "is limited to the cabinets which are owned by this department."),
            __("● The \"DC Statistics\" page is only generated for users who are "
                . "not limited to \"Admin Own Devices\"."),
            __("● For a child device the column \"Parent Device\" shows the parent "
                . "device otherwise it is empty. A child devices is e.g. a "
                . "blade server."),
            __("● The \"Position\" for a child device is the slot position within "
                . "in the parent device."),
            __("● \"Half Depth\" contains a value \"front\" or \"rear\" only if the "
                . "device is of half depth in which case this indicates the "
                . "location."),
            __("● Consecutive free rack space is indicated in worksheet \"DC Inventory"
                . "\" with the string \"__EMPTY \" in column \"Device\". "
                . "\"Position\" gives the start of the range and \"Height\" "
                . "specifies the size of the range of free rack space."),
            __("● A range of consecutive free slots in a chassis is indicated in "
                . "worksheet \"DC Inventory\" with the string \"__EMPTYSLOT \" in "
                . "the column \"Device\". \"Position\" gives the start of the "
                . "range and \"Height\" specifies the number of free slots."),
            __("● All free rack units (RU) within the racks sum up to the total "
                . "amount of free rack units. It is possible that devices of "
                . "different height are mounted at the same position within a "
                . "cabinet. Only the non occupied rack units are free RUs."),
            __("● Cabinets of \"Model\" equal \"RESERVED\" are placeholders on the "
                . "floor space in the DC for racks to come. Their number is "
                . "counted in the column \"No. Reserved Racks\". Nevertheless, "
                . "the rack units are taken into account in all other statistics."),
            "",
            __("This file is confidential and shall not be used without permission of "
                . "the owner."),
            "",
            __("Generate by openDCIM")
        )
    ),
	'DC Stats' => array(
		'Title' => '&L&DC ' . __("Summary Statistics") . '&R&A',
		'FillColor' => 'DCE6F1',
		'HeadingFontColor' => '000000',
		'HeaderRange' => null,
		'HeaderHeight' => 51,
		'Border color' => '95B3D7',
		'Border Style' => array(
			'borders' => array(
				'bottom' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN,
					'color' => array(
						'rgb' => '95B3D7'
					)
				)
			)
		),
		'PageSize' => $config->ParameterArray['PageSize'],
		'Orientation' => PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE,
		// Columns format is <header_title>, <format_spec>, <width>, <special_attr>
		'Columns' => array(
			array(__("DC Room"), '', 21, 'wrap'),
			array(__("Floor\nSpace\n(sqm)"), '', 9, null),
			array(__("No.\nRacks"), '', 10, null),
			array(__("No.\nReserved\nRacks"), '', 10, null),
			array(__("Sum of\nRack\nUnits (RU)"), '', 12, null),
			array(__("Sum of\nUsed\nRack\nUnits (RU)"), '', 12, null),
			array(__("Percentage\nUsed\nRack\nUnits"), 'P', 12, null),
			array(__("Sum of\nEmpty\nRack\nUnits (RU)"), '', 12, null),
			array(__("Percentage\nEmpty\nRack\nUnits"), 'P', 12, null),
			array(__("Sum of\nPower\n(kW)"), 'F', 9, null),
			array(__("Sum of\nDesign\nPower\n(kW)"), '', 9, null)
		),
		'KPIs' => array(
			'Fl_Spc',      // - Fl_Spc      floor space
			'Rk_Num',      // - Rk_Num      no. racks
			'Rk_UtT',      // - Rk_UtT      rack units Total
			'Rk_UtU',      // - Rk_UtU      rack units used
			'Rk_UtE',      // - Rk_UtE      rack units empty
			'Rk_Res',      // - Rk_Res      racks reserved
			'Watts',       // - Watts       power allocated
			'DesignPower'  // - DesignPower design power of DC
		),
		'ColIdx' => array(),
		'ExpStr' => array()
    ),
    'DC Inventory' => array(
        'Title' => '&L&BDC Inventory - Devices&R&A',
        'FillColor' => '162A49',
        'HeadingFontColor' => 'FFFFFF',
        'HeaderRange' => null,
        'HeaderHeight' => null,
        'PageSize' => $config->ParameterArray['PageSize'],
        'Orientation' => PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT,
        'Columns' => array(
            array('DevID', '', null, null),
            array('Zone', '', null, null),
            array('Row', '', null, null),
            array('DC Name', 'T', null, null),
            array('Cabinet', '', 10, null),
            array('Position', '', null, null),
            array('Half Depth', '', null, null),
            array('Height', '', null, null),
            array('Device', '', 19, null),
            array('Parent Device', '', 19, null),
            array('Manufacturer', '', null, null),
            array('Model', 'T', 15, null),
            array('Device Type', '', null, null),
            array('Asset Number', 'T', 11, null),
            array('Serial No.', 'T', 11, null),
            array('Install Date', 'D', 11, null),
            array('Warranty End', 'D', 11, null),
            array('Owner', '', 11, null),
            array('Power (W)', '', 11, null),
            array('Reservation', '', null, null),
            array('Contact', '', null, null),
            array('Tags', '', null, null),
            array('Notes', '', 40, 'wrap')),
        'ColIdx' => array(),
        'ExpStr' => array()
    ),
    'Rack Inventory' => array(
        'Title' => '&L&BDC Inventory - Racks&R&A',
        'FillColor' => '162A49',
        'HeadingFontColor' => 'FFFFFF',
        'HeaderRange' => null,
        'HeaderHeight' => null,
        'PageSize' => $config->ParameterArray['PageSize'],
        'Orientation' => PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT,
        'Columns' => array(
            array('CabID', '', null, null),
            array('Zone', '', null, null),
            array('Row', '', null, null),
            array('DC Name', '', null, null),
            array('Cabinet', '', 10, null),
            array('AssignedTo', '', 11, null),
            array('Tags', '', null, null),
            array('Height', '', null, null),
            array('Model', '', null, null),
            array('Keylock', '', null, null),
            array('MaxKW', '', null, null),
            array('MaxWeight', '', null, null),
            array('Install Date', 'D', 10, null),
            array('Auditor', '', 10, null),
            array('Timestamp', 'D', 18, null),
            array('Front Edge', '', null, null),
            array('Notes', '', 40, 'wrap')),
        'ColIdx' => array(),
        'ExpStr' => array()
    )
);

/**
 * Report some runtime statistics if the log file writable and reporting flag set.
 *
 * @author
 *
 */
class ReportStats
{
    /**
     * @var ReportStats instance of this singleton
     */
    private static $_instance = null;

    /**
     * @var identifier of the report string
     */
    private $rid = 0;
    /**
     * @var boolean $report_flag if true enable report on usage of time and memory
     */
    private static $report_flag = false;

    /**
     * @var int $start time value when started
     */
    private $start = 0;

    /**
     * @var int $elpase time elpased since start
     */
    private $elapse = 0;

    /**
     * string $fname filename where the reporting is written to
     */
    private $fname = '/var/tmp/report_assets_Excel.stats';

    /**
     * @var object|null $_fp file handle to the open reporting file or null
     */
    private $_fp = null;

    /**
     * Get the instance of the singleton or create it if not yet created
     *
     * @return ReportStats
     */
    public static function get()
    {
        /**
         * object|null $_fp file handle to the open reporting file or null
         */
        if (self::$_instance === null) {
            self::$_instance = new ReportStats();
        }
        return self::$_instance;
    }

    private function __clone() {}

    private function __construct()
    {
        $this->start = microtime(true);
        $this->rid = time();
    }

    /**
     * Enable the statistics reporting
     */
    public function useStatReporting()
    {
        self::$report_flag = true;
    }

    /**
     * Write to the statistics file consumption on time and memory
     *
     * If $type equals 'Info' the message is written to the statistics file with the
     * delta time between the previous invocation and the total time spend by the
     * program. On $type equal 'Totals' the memory usage and the timings are written.
     * @param string $type
     * @param string $msg
     */
    public function report($type, $msg = '')
    {
        try {
            $this->_fp = fopen($this->fname, 'a');
            if (self::$report_flag and $this->_fp) {
                $total = microtime(true) - $this->start;
                $delta = $total - $this->elapse;
                $this->elapse += $delta;
                $line = null;

                $timestamp = date('Y-m-d H:i:s');
                switch ($type) {
                    case 'Info':
                        $line = $this->rid . ' ' . $msg . ' [Delta/Total (sec): '
                            . sprintf("%.2f", $delta)
                            . '/' . sprintf("%.2f", $total) . ']' . PHP_EOL;
                        break;
                    case 'Totals':
                        $line = $this->rid . ' ' . 'Summary [Delta/Total (sec): '
                            . sprintf("%.2f", $delta) . '/'
                            . sprintf("%.2f", $total) . ']' . PHP_EOL;
                        $mem_usage = memory_get_usage(true) / 1024 / 1024;
                        $line .= ' Current memory usage: ' . $mem_usage . 'MB' . "\n";
                        $mem_peak = memory_get_peak_usage(true) / 1024 / 1024;
                        $line .= ' Peak memory usage: ' . $mem_peak . 'MB' . "\n";
                        break;
                }
                if ($line) fwrite($this->_fp, $timestamp . ' ' . $line);
            }
            fclose($this->_fp);
        } catch (Exception $e) {
            // ignore error if opening the file fails for any reason
        }
    }
}

/**
 * Create an index for the column names of a worksheet
 *
 * The column index has two values, the numerical and the alpha value of the
 * respective column, e.g. 'ColName' => array(3, 'C').
 *
 * @param array $colSpec an array of column specifications for a worksheet
 * @return array an associated array indexed by the column names pointing to the
 *   pair of the numerical and the alpha index of the column
 */
function buildColumnIndex($colSpec)
{
    $idx = 0;
    $colIndex = array();
    foreach ($colSpec as $val) {
        $colIndex[$val[0]] = array($idx, PHPExcel_Cell::stringFromColumnIndex($idx));
        $idx ++;
    }

    return $colIndex;
}

/**
 * Create the list of those columns of a worksheet which needs to be written
 *   explicitly as string value.
 *
 * The returned list consists of all the column names which require explict
 * writing of values with their Excel type. The list looks like e.g.
 * array('ColName3', 'ColName7').
 *
 * @param $colSpec
 * @return array a list of column names requiring explicitly being written as string
 */
function buildExStringColIdx($colSpec)
{
    $colIndex = array();
    foreach ($colSpec as $key => $val) {
        $colIndex[] = $val[0];
    }

    return $colIndex;
}

/**
 * Add an index of column names for each worksheet
 *
 * @param $DProps document properties
 */
function addColumnIndices(&$DProps)
{
    $worksheetNames = array();
    foreach ($DProps as $key => $val) {
        if (($key != 'Doc') and (is_array($val))) {
            $worksheetNames[] = $key;
        }
    }
    $tmpC = new Container();
    $maxLevels = $tmpC->computeMaxLevel();
    $levelSpecs = array();
    foreach (range(1, $maxLevels) as $val) {
        if ($val == $maxLevels) {
            $fmt = 'T';
        } else {
            $fmt = '';
        }
        $levelSpecs[] = array(('Level' . $val), $fmt, null, null);
    }
    foreach ($worksheetNames as $wsName) {
        $colArray = &$DProps[$wsName]['Columns'];
        if (($wsName == 'DC Inventory') or ($wsName == 'Rack Inventory')) {
            array_splice($colArray, 1, 0, $levelSpecs);
        }

        // the list of pairs of numeric and alpha values for each column of a sheet
        $DProps[$wsName]['ColIdx'] = buildColumnIndex($colArray);
        // the list of column names which require writing with explictly type
        // specification
        $DProps[$wsName]['ExpStr'] = buildExStringColIdx($colArray);
    }
}

/**
 * Set properties of Excel document
 *
 * @param PHPExcel $objPHPExcel
 *  workbook to be generated
 * @param string $thisDate
 *  the date on which the workbook is generated
 * @param string $ownerName
 *  the configuration of openDCIM
 * @param array $DProps
 *  the workbook configuration
 */
function setDocumentProperties($objPHPExcel, $thisDate, $ownerName, $DProps)
{
    $creator = basename(__FILE__) . ' version ' . $DProps['Doc']['version']
        . ', '. $ownerName;
    $subject = $DProps['Doc']['Subject'];
    $description = $DProps['Doc']['Description'];
    $title = $DProps['Doc']['Title'];
    $keywords = $DProps['Doc']['Keywords'];
    $objPHPExcel->getProperties()
        ->setCreator($creator)
        ->setSubject("$subject, $thisDate")
        ->setDescription($description)
        ->setTitle($title)
        ->setKeywords($keywords)
        ->setLastModifiedBy($DProps['Doc']['UserID']);
    $objPHPExcel->getDefaultStyle()
        ->getFont()
        ->setName('Arial')
        ->setSize(10);
}

/**
 * Set the properties of a worksheet
 *
 * @param PHPExcel_Worksheet $worksheet
 * @param string $WSKind kind of worksheet
 * @param array $DProps array with the document properties
 * @param string $thisDate timestamp of the execution of the script
 */
function setWorksheetProperties($worksheet, $wsKind, $DProps, $thisDate)
{
    $worksheet->SetTitle($wsKind . ' '. $thisDate);
    $worksheet->getTabColor()->setRGB($DProps[$wsKind]['FillColor']);
    // Set the printout options
    switch ($DProps[$wsKind]['PageSize']) {
        case 'A4':
            $page_size = PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4;
            break;
        case 'A3':
            $page_size = PHPExcel_Worksheet_PageSetup::PAPERSIZE_A3;
            break;
        case 'Letter':
            $page_size = PHPExcel_Worksheet_PageSetup::PAPERSIZE_LETTER;
            break;
        case 'Legal':
            $page_size = PHPExcel_Worksheet_PageSetup::PAPERSIZE_LEGAL;
            break;
        default:
            $page_size = PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4;
            break;
    }
    // Set orientation, fit to page width, paper size and page number to 1
    $worksheet->getPageSetup()
        ->setOrientation($DProps[$wsKind]['Orientation'])
        ->setFitToPage(true)
        ->setFitToWidth(1)
        ->setFitToHeight(0)
        ->setPaperSize($page_size)
        ->setFirstPageNumber(1);
    // Set title of header and page number footer for printout
    $worksheet->getHeaderFooter()
        ->setOddHeader($DProps[$wsKind]['Title'])
        ->setEvenHeader($DProps[$wsKind]['Title'])
        ->setOddFooter('&RPage &P of &N')
        ->setEvenFooter('&RPage &P of &N');
    // Set repeating header for most worksheets on the printout
    switch ($wsKind) {
        case 'Front Page':
            break;
        case 'DC Stats':
        case 'Rack Inventory':
        case 'DC Inventory':
            $worksheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
            break;
    }
}

/**
 * Create a list of the header names from the worksheet column attributes
 *
 * @param array $wsCols
 * @return (string)[] a list of the column names
 */
function getHeaderNames($wsCols)
{
    $colNames = array();
    foreach ($wsCols as $colAttrs) {
        $colNames[] = $colAttrs[0];
     }

    return $colNames;
}

/**
 * @param array $headerDef the array column headers for a worksheet
 * @return string Excel alphnum range specification
 */
function computeHeaderRange($headerDef)
{
    // columns start is zero based, therefore adjustment required
    $headerLen = count($headerDef) - 1;
    $headerRange = 'A1:' . PHPExcel_Cell::stringFromColumnIndex($headerLen) . '1';

    return $headerRange;
}

/**
 * Write the header of a worksheet
 *
 * @param PHPExcel_Worksheet $worksheet
 * @param string $wsKind the kind of worksheet
 * @param array $wsProps the attribues of the worksheet
 */
function writeWSHeader($worksheet, $wsKind, $wsProps)
{
    $hrange = $wsProps['HeaderRange'];

    if (! $hrange) {
        $hrange = computeHeaderRange($wsProps['Columns']);
    }
    $colNames = getHeaderNames($wsProps['Columns']);
    $worksheet->fromArray($colNames, null, 'A1');
    if ($wsProps['HeaderHeight']) {
        $worksheet->getRowDimension('1')->setRowHeight($wsProps['HeaderHeight']);
    }
    $freezeCell = 'A2';
    $repeat_header = true;
    switch ($wsKind) {
    	case 'Front Page':
    	    $freezeCell = 'A3';
    	    $repeat_header = false;
    	    break;
    	case 'DC Stats':
    	    $worksheet->getStyle($hrange)->getAlignment()->setWrapText(true);
    	    break;
    	case 'DC Inventory':
    	case 'Rack Inventory':
    	    break;
    }
    $worksheet->freezePane($freezeCell);
    $worksheet->getStyle($hrange)->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    $worksheet->getStyle($hrange)->getFill()
        ->getStartColor()->setRGB($wsProps['FillColor']);
    $worksheet->getStyle($hrange)->getFont()
        ->getColor()->setRGB($wsProps['HeadingFontColor']);
    $worksheet->getStyle($hrange)->getFont()->setBold(true);
    if ($repeat_header) {
        $worksheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
    }
}

/**
 * Format the columns of the worksheet according to the attribute definition
 *
 * @param PHPExcel_Worksheet $worksheet
 * @param array $columns
 */
function formatWSColumns($worksheet, $columns)
{
    $fmt_code = array(
        'T' => PHPExcel_Style_NumberFormat::FORMAT_TEXT,
        'D' => 'yyyy-mm-dd',
        'N' => PHPExcel_Style_NumberFormat::FORMAT_NUMBER,
        'F' => '0.0',
        'P' => '0.0%',
        '' => null
    );
    $highestRow = $worksheet->getHighestRow();
    $colFmt = $columns[0][1];
    $colidx = 0;
    $start = 0;
    $idx = 0;
    foreach ($columns as $col) {
        if ($col[2]) {
            // set the column width if a specific value is defined

           $colLetter = PHPExcel_Cell::stringFromColumnIndex($colidx);
           $worksheet->getColumnDimension($colLetter)->setWidth($col[2]);

        }
        if ($col[3] and ($col[3] == 'wrap')) {
            // set text wrapping attribute if requested
            $colLetter = PHPExcel_Cell::stringFromColumnIndex($colidx);
            $range = $colLetter . '2:' . $colLetter . $highestRow;
            $worksheet->getStyle($range)->getAlignment()->setWrapText(true);
        }
        if ($colFmt != $col[1]) {
            // assign the format to the range if a format is explicitly required
            $start_col = PHPExcel_Cell::stringFromColumnIndex($start);
            $end_col =  PHPExcel_Cell::stringFromColumnIndex($start+$idx-1);
            $range = $start_col . '2:' . $end_col . $highestRow;
            $colFmtSpec = $fmt_code[$colFmt];
            if ($colFmtSpec) {
                $worksheet->getStyle($range)
                    ->getNumberFormat()->setFormatCode($colFmtSpec);
            }
            $colFmt = $col[1];
            $start = $start + $idx;
            $idx = 1;
        } else {
            // still in the same format range
            $idx += 1;
        }
        $colidx++;
    }
    $start_col = PHPExcel_Cell::stringFromColumnIndex($start);
    $end_col =  PHPExcel_Cell::stringFromColumnIndex($start+$idx-1);
    $range = $start_col . '2:' . $end_col . $highestRow;
    $colFmtSpec = $fmt_code[$colFmt];
    if ($colFmtSpec) {
        // assign up to the end the format if explicitly required
        $worksheet->getStyle($range)->getNumberFormat()->setFormatCode($colFmtSpec);
    }
}

/**
 * Compare two devices according to their position within a rack
 *
 * @param Device $a
 * @param Device $b
 * @return boolean
 */
function cmpDevPos($a, $b)
{
    return (intval($a->Position) > intval($b->Position));
}

/**
 * Compute the device class of a device
 *
 * @param DeviceTemplate $devTemplates
 * @param Device $device
 * @return (string|string)[] Manufacturer, Model
 */
function getDeviceTemplateName($devTemplates, $device)
{
    // Compute the device class of a device
    $retval = array('_NoDevModel', '_NoManufDefined');
    $templID = $device->TemplateID;
    if ($templID != 0) {
        // Data validation error
        if(!isset($devTemplates[$templID])){
            $devTemplates[$templID]=new DeviceTemplate();
            $devTemplates[$templID]->TemplateID=$templID;
            $devTemplates[$templID]->ManufacturerID=0;
            $devTemplates[$templID]->Model=__("Template Missing");
        }
        $manufacturer = new Manufacturer();
        $manufacturer->ManufacturerID = $devTemplates[$templID]->ManufacturerID;
        $retcode = $manufacturer->GetManufacturerByID();
        if ($retcode) {
            $manufName = $manufacturer->Name;
        } else {
            $manufName = '_NoManufDefined';
        }
        $retval = array($manufName, $devTemplates[$templID]->Model);
    }

    return $retval;
}

/**
 * Get the owner name of a device
 *
 * @param Device $device
 * @param array $deptList
 * @return (string|null) the owner or null if device doesn't have an owner
 */
function getOwnerName($device, $deptList, $emptyVal = null)
{
    $retval = $emptyVal;
    if ($device->Owner != 0) {
        $retval = $deptList[$device->Owner]->Name;
    }

    return $retval;
}

/**
 * Return the auditor's name.
 *
 * @param Cabinet $cab
 * @param string|null $emptyVal
 * @return string|null
 */
function getAuditorName($cab, $emptyVal = null)
{
    $auditorName = $emptyVal;
    $cab_audit = new CabinetAudit();
    $cab_audit->CabinetID = $cab->CabinetID;
    $cab_audit->GetLastAudit();
    if ($cab_audit->UserID) {
        $tmpUser=new People();
        $tmpUser->UserID = $cab_audit->UserID;
        $tmpUser->GetUserRights();
		$auditorName = $tmpUser->LastName . ", " . $tmpUser->FirstName;
    }

    return $auditorName;
}

/**
 * Return the timestamp of the cabinet's last audit.
 *
 * @param Cabinet $cab
 * @param string|null $emptyVal
 * @return string|null
 */
function getAuditTimestamp($cab, $emptyVal = null)
{
    $auditTimestamp = $emptyVal;
    $cab_audit = new CabinetAudit();
    $cab_audit->CabinetID = $cab->CabinetID;
    $cab_audit->GetLastAudit();
    if ($cab_audit->AuditStamp) {
        $auditTimestamp = $cab_audit->AuditStamp;
    }

    return $auditTimestamp;
}

/**
 * Merge the tags of a Device or Cabinet into one string separated by ', '
 *
 * @param Device|Cabinet $obj
 * @return string
 */
function getTagsString($obj, $emptyVal = null)
{
    $tagNames = $emptyVal;
    $tag_list = $obj->GetTags();
    if (count($tag_list) > 0) {
        $tagNames = implode(', ', $tag_list);
    }

    return $tagNames;
}

/**
 * Return the name of the zone for a cabinet.
 *
 * @param Cabinet $cab
 * @param string|null $emptyVal
 * @return string
 */
function getZoneName($cab, $emptyVal = null)
{
    $zoneName = $emptyVal;
    if ($cab->ZoneID) {
        $zone = new Zone();
        $zone->ZoneID = $cab->ZoneID;
        $zone->GetZone();
        $zoneName = $zone->Description;
    }

    return $zoneName;
}

/**
 * Return the row name of a cabinet.
 * @param Cabinet $cab
 * @param (string|null) $emptyVal
 * @return string|null
 */
function getRowName($cab, $emptyVal = null)
{
    $rowName = $emptyVal;
    if ($cab->CabRowID) {
        $cabrow = new CabRow();
        $cabrow->CabRowID = $cab->CabRowID;
        $cabrow->GetCabRow();
        $rowName = $cabrow->Name;
    }

    return $rowName;
}

/**
 * Return if the position of the device if it is half depth, either 'front' or
 *  'rear' otherwise 'null'.
 * @param Device $dev
 * @return string|null
 */
function getDeviceDepthPos($dev)
{
    $retval = null;
    if ($dev) {
        if ($dev->HalfDepth) {
            if ($dev->BackSide) {
                $retval = 'rear';
            } else {
                $retval = 'front';
            }
        }
    }

    return $retval;
}

/**
 * Get the name of the contact
 *
 * @param array $contactList
 * @param int $contactID
 * @return string
 */
function getContactName($contactList, $contactID)
{
    $contactName = null;
    if ($contactID) {
        $contactName = implode(', ', array(@$contactList[$contactID]->LastName,
            $contactList[$contactID]->FirstName));
    }

    return $contactName;
}

/** Alternative function to array_merge_recursive, taken from the PHP manual
 *   page, author: andyidol at gmail dot com
 *
 * The original array_merge_recursive failed on a sequence of data center names
 * which could be interpreted as numbers. The last entry then was converted to
 * a '0' index instead of a string such as '1029'.
 *
 * @param array $Arr1
 * @param array $Arr2
 * @return array
 */
function MergeArrays($Arr1, $Arr2)
{
    foreach($Arr2 as $key => $Value)
    {
        if(array_key_exists($key, $Arr1) && is_array($Value)) {
            $Arr1[$key] = MergeArrays($Arr1[$key], $Arr2[$key]);
        }
        else {
            $Arr1[$key] = $Value;
        }
    }

  return $Arr1;
}

/**
 * Assign the statistics values of a data center to the overall statistics
 *  $dcStats
 *
 * @param array $dcStats
 * @param DataCenter $dc
 * @param array $Stats
 */
function assignStatsVal(&$dcStats, $dc, $Stats)
{
    $arr = array();
    $tmp = &$arr;
    $dcContainerList = $dc->getContainerList();
    $dcContainerList[] = $dc->Name;
    foreach ($dcContainerList as $level) {
        $tmp[$level] = array();
        $tmp = &$tmp[$level];
    }
    $tmp = $Stats;
 //   $dcStats = array_merge_recursive($dcStats, $arr);
    $dcStats = MergeArrays($dcStats, $arr);
}

/**
 * Return empty entry specification, kind depends on $sheetColumns
 *
 * @param array $sheetColumns the columns of the worksheet
 * @param array $dcContainerList list of containers of data center
 * @return array an array which contains all the device attributes set to null
 */
function makeEmptySpec($sheetColumns, $dcContainerList)
{
    $emptySpec = array();
    foreach ($sheetColumns as $col) {
        $emptySpec[$col[0]] = null;
    }
    $idx = 1;
    foreach ($dcContainerList as $containerName) {
        $emptySpec[('Level'.$idx)] = $containerName;
        $idx++;
    }

    return $emptySpec;
}

/**
 * Capture the device values for child devices
 *
 * @param array $sheetColumns
 * @param array $invData
 * @param Device $parentDev
 *      parent device of the child
 * @param array $DCName
 * @param Cabinet $cab
 * @param array $devTemplates
 * @param array $deptList a list of departments (Department) indexed by the DeptID
 * @param array $contactList list of all contacts (Contact) indexed by ContactID
 * @param array $dcContainerList list of containers the data center is positioned in
 * @return (integer|array)[] sum of wattage of child devices, array of devices
 *  in the inventory
 */
function computeDeviceChildren($sheetColumns, $invData, $parentDev, $DCName,
    $cab, $devTemplates, $deptList, $contactList, $dcContainerList)
{
    // capture the device values for child devices
    $wattageTotal = 0;
    $children = $parentDev->GetDeviceChildren();
    if (is_array($children)) {
        usort($children, 'cmpDevPos');
        $chassisNumSlots = $parentDev->ChassisSlots;
        $idx = 1;
        $zoneName = getZoneName($cab);
        $rowName = getRowName($cab);
        foreach ($children as $child) {
            if ($idx < $child->Position) {
                $devSpec = makeEmptySpec($sheetColumns, $dcContainerList);
                $devSpec['Zone'] = $zoneName;
                $devSpec['Row'] = $rowName;
                $devSpec['DC Name'] = $DCName;
                $devSpec['Cabinet'] = $cab->Location;
                $devSpec['Position'] = $idx;
                $devSpec['Height'] = $child->Position - $idx;
                $devSpec['Device'] = '__EMPTYSLOT';
                $devSpec['Parent Device'] = $parentDev->Label;
                $invData[] = $devSpec;
                $idx = $child->Position;
            }
            $reserved = $child->Reservation ? 'reserved' : null;
            list($manufacturer, $model) = getDeviceTemplateName($devTemplates,
                $child);
            $devSpec = makeEmptySpec($sheetColumns, $dcContainerList);
            $devSpec['DevID'] = $child->DeviceID;
            $devSpec['Zone'] = $zoneName;
            $devSpec['Row'] = $rowName;
            $devSpec['DC Name'] = $DCName;
            $devSpec['Cabinet'] = $cab->Location;
            $devSpec['Position'] = $child->Position;
            $devSpec['Height'] = $child->Height;
            $devSpec['Device'] = $child->Label;
            $devSpec['Parent Device'] = $parentDev->Label;
            $devSpec['Manufacturer'] = $manufacturer;
            $devSpec['Model'] = $model;
            $devSpec['Device Type'] = $child->DeviceType;
            $devSpec['Asset Number'] = $child->AssetTag;
            $devSpec['Serial No.'] = $child->SerialNo;
            $devSpec['Install Date'] = $child->InstallDate;
            $devSpec['Warranty End'] = $child->WarrantyExpire;
            $devSpec['Owner'] = getOwnerName($child, $deptList);
            $devSpec['Power (W)'] = $child->NominalWatts;
            $devSpec['Reservation'] = $reserved;
            $devSpec['Contact'] = getContactName($contactList, $child->PrimaryContact);
            $devSpec['Tags'] = getTagsString($child);
            $devSpec['Notes'] = html_entity_decode(strip_tags($child->Notes),
                ENT_COMPAT, 'UTF-8');
            $wattageTotal += $child->NominalWatts;
            $invData[] = $devSpec;
            $idx += $child->Height;
        }
        if ($idx <= $chassisNumSlots) {
            $freeSlots = $chassisNumSlots - $idx + 1;
            $devSpec = makeEmptySpec($sheetColumns, $dcContainerList);
            $devSpec['Zone'] = $zoneName;
            $devSpec['Row'] = $rowName;
            $devSpec['DC Name'] = $DCName;
            $devSpec['Cabinet'] = $cab->Location;
            $devSpec['Position'] = $idx;
            $devSpec['Height'] = $freeSlots;
            $devSpec['Device'] = '__EMPTYSLOT';
            $devSpec['Parent Device'] = $parentDev->Label;
            $invData[] = $devSpec;
        }
    }

    return array($wattageTotal, $invData);
}

/**
 * Collect rack information and add it to the cab_data inventory on all data
 *  centers
 *
 * @param array $invCab
 * @param Cabinet $cab
 * @param $cabinetColumns
 * @param DataCenter $dc
 * @param array $dcContainerList
 */
function addRackStat(&$invCab, $cab, $cabinetColumns, $dc, $dcContainerList)
{
    $rack = makeEmptySpec($cabinetColumns, $dcContainerList);
    if ($cab->AssignedTo != 0) {
        $dept = new Department();
        $dept->DeptID = $cab->AssignedTo;
        $dept->GetDeptByID();
        $deptName = $dept->Name;
    } else {
        $deptName = null;
    }
    $rack['CabID'] = $cab->CabinetID;
    $rack['Zone'] = getZoneName($cab);
    $rack['Row'] = getRowName($cab);
    $rack['DC Name'] = $dc->Name;
    $rack['Cabinet'] = $cab->Location;
    $rack['AssignedTo'] = $deptName;
    $rack['Tags'] = getTagsString($cab);
    $rack['Height'] = $cab->CabinetHeight;
    $rack['Model'] = $cab->Model;
    $rack['Keylock'] = $cab->Keylock;
    $rack['MaxKW'] = $cab->MaxKW;
    $rack['MaxWeight'] = $cab->MaxWeight;
    $rack['Install Date'] = $cab->InstallationDate;
    $rack['Auditor'] = getAuditorName($cab);
    $rack['Front Edge'] = $cab->FrontEdge;
    $rack['Timestamp'] = getAuditTimestamp($cab);
    $rack['Notes'] = html_entity_decode(strip_tags($cab->Notes), ENT_COMPAT,
        'UTF-8');
    $invCab[$dc->DataCenterID][$cab->Location] = $rack;
}

/**
 * Compute the full inventory on devices in the data centers and return the data
 *   center summary statistics
 *
 * @param PHPExcel_Worksheet $worksheet
 * @param array $DProps properties defined for the Excel document
 * @return (array|array|array|boolean)[]
 *      statistics array, device inventory, cabinet inventory
 */
function computeSheetBodyDCInventory($DProps)
{
    global $person;
    global $sessID;
    $dc = new DataCenter();
    $cab = new Cabinet();
    $device = new Device();
    $invData= array();
    $invCab = array();
    $sheetColumns = $DProps['DC Inventory']['Columns'];
    $cabinetColumns = $DProps['Rack Inventory']['Columns'];
    $devTemplates = DeviceTemplate::getTemplateListIndexedbyID();
    $deptList = Department::GetDepartmentListIndexedbyID();
    $contactList = $person->GetUserList('indexed');

    $limitedUser = false;
    $dcList = $dc->GetDCList();
    $Stats = array();

    // A little code to update the counter

    $percentDone = 0;
    $sectionMaxPercent = 40;
    $incrementalPercent = 1 / sizeof( $dcList ) * $sectionMaxPercent;

    foreach ($dcList as $dc) {
        $dcContainerList = $dc->getContainerList();
        $dcStats = array();
        $cab->DataCenterID = $dc->DataCenterID;
        $dcStats['Fl_Spc'] = $dc->SquareFootage;
        $dcStats['DesignPower'] = $dc->MaxkW;
        $dcStats['Watts'] = 0;
        $dcStats['Rk_Num'] = 0;
        $dcStats['Rk_UtT'] = 0;
        $dcStats['Rk_UtU'] = 0;
        $dcStats['Rk_UtE'] = 0;
        $dcStats['Rk_Res'] = 0;

        $cabList = $cab->ListCabinetsByDC();
        if (count($cabList) == 0) {
            // empty data center room
            $devSpec = makeEmptySpec($sheetColumns, $dcContainerList);
            $devSpec['DC Name'] = $dc->Name;
            $invData[] = $devSpec;
        } else {
            foreach ($cabList as $cab) {
                if (((! $person->ReadAccess) and ($cab->AssignedTo == 0))
                    or (($cab->AssignedTo > 0)
                        and (! $person->canRead($cab->AssignedTo)))) {
                    // User is not allowed to see anything in here
                    $limitedUser = true;
                    continue;
                }
                $zoneName = getZoneName($cab);
                $rowName = getRowName($cab);
                addRackStat($invCab, $cab, $cabinetColumns, $dc, $dcContainerList);
                $cab_height = $cab->CabinetHeight;
                if (mb_strtoupper($cab->Model) == 'RESERVED') {
                    $dcStats['Rk_Res']++;
                } else {
                    $dcStats['Rk_Num']++;
                }
                $dcStats['Rk_UtT'] += $cab_height;
                $device->Cabinet = $cab->CabinetID;
                $device_list = $device->ViewDevicesByCabinet();
                // empty cabinet
                if ((count($device_list) == 0) && ($cab->CabinetHeight > 0)) {
                    $dcStats['Rk_UtE'] += $cab_height;
                    $devSpec = makeEmptySpec($sheetColumns, $dcContainerList);
                    $devSpec['Zone'] = $zoneName;
                    $devSpec['Row'] = $rowName;
                    $devSpec['DC Name'] = $dc->Name;
                    $devSpec['Cabinet'] = $cab->Location;
                    $devSpec['Position'] = 1;
                    $devSpec['Height'] = $cab->CabinetHeight;
                    $devSpec['Device'] = '__EMPTY';
                    $invData[] = $devSpec;
                } else {
                    usort($device_list, 'cmpDevPos');
                    $low_idx = 1;
                    foreach ($device_list as $dev) {
                        if ($low_idx < $dev->Position) {
                            // range of empty slots
                            if ($dev->Position <= $cab_height) {
                                $height = $dev->Position - $low_idx;
                            } else {
                                $height = $cab_height - $low_idx + 1;
                            }
                            if ($height > 0) {
                                $dcStats['Rk_UtE'] += $height;
                                $devSpec = makeEmptySpec($sheetColumns,
                                $dcContainerList);
                                $devSpec['Zone'] = $zoneName;
                                $devSpec['Row'] = $rowName;
                                $devSpec['DC Name'] = $dc->Name;
                                $devSpec['Cabinet'] = $cab->Location;
                                $devSpec['Position'] = $low_idx;
                                $devSpec['Height'] = $height;
                                $devSpec['Device'] = '__EMPTY';
                                $invData[] = $devSpec;
                            }
                            $low_idx = $dev->Position;
                        }
                        // device in cabinet
                        $reserved = $dev->Reservation ? 'reserved' : null;
                        list($manufacturer, $model) = getDeviceTemplateName(
                            $devTemplates, $dev);
                        $devSpec = makeEmptySpec($sheetColumns, $dcContainerList);
                        $devSpec['DevID'] = $dev->DeviceID;
                        $devSpec['Zone'] = $zoneName;
                        $devSpec['Row'] = $rowName;
                        $devSpec['DC Name'] = $dc->Name;
                        $devSpec['Cabinet'] = $cab->Location;
                        $devSpec['Position'] = $dev->Position;
                        $devSpec['Half Depth'] = getDeviceDepthPos($dev);
                        $devSpec['Height'] = $dev->Height;
                        $devSpec['Device'] = $dev->Label;
                        $devSpec['Parent Device'] = null;
                        $devSpec['Manufacturer'] = $manufacturer;
                        $devSpec['Model'] = $model;
                        $devSpec['Device Type'] = $dev->DeviceType;
                        $devSpec['Asset Number'] = $dev->AssetTag;
                        $devSpec['Serial No.'] = $dev->SerialNo;
                        $devSpec['Install Date'] = $dev->InstallDate;
                        $devSpec['Warranty End'] = $dev->WarrantyExpire;
                        $devSpec['Owner'] = getOwnerName($dev, $deptList);
                        $devSpec['Power (W)'] = $dev->NominalWatts;
                        $devSpec['Reservation'] = $reserved;
                        $devSpec['Contact'] = getContactName($contactList, $dev->PrimaryContact);
                        $devSpec['Tags'] = getTagsString($dev);
                        $devSpec['Notes'] = html_entity_decode(strip_tags($dev->Notes),
                                ENT_COMPAT, 'UTF-8');
                        $invData[] = $devSpec;
                        $dcStats['Watts'] += $dev->NominalWatts;
                        // devices can be installed at the same position and
                        // could be of different height; count only the free
                        // rack units which are not covered by any device
                        if ($low_idx == $dev->Position) {
                            $low_idx += $dev->Height;
                            $dcStats['Rk_UtU'] += $dev->Height;
                        } else {
                            $rest_height = ($dev->Position + $dev->Height - $low_idx);
                            $rest_height = ($rest_height > 0 ? $rest_height : 0);
                            $low_idx += $rest_height;
                            $dcStats['Rk_UtU'] += $rest_height;
                        }
                        if ($dev->DeviceType == 'Chassis') {
                            list($watts, $invData) = computeDeviceChildren(
                                $sheetColumns, $invData, $dev, $dc->Name, $cab,
                                $devTemplates, $deptList, $contactList,
                                $dcContainerList);
                            $dcStats['Watts'] += $watts;
                        }
                    }
                    if ($low_idx <= $cab->CabinetHeight) {
                        // empty range at the top of the cabinet, $low_idx is
                        // the potentially free location
                        $height = $cab->CabinetHeight - $low_idx + 1;
                        $dcStats['Rk_UtE'] += $height;
                        $devSpec = makeEmptySpec($sheetColumns,
                        $dcContainerList);
                        $devSpec['Zone'] = $zoneName;
                        $devSpec['Row'] = $rowName;
                        $devSpec['DC Name'] = $dc->Name;
                        $devSpec['Cabinet'] = $cab->Location;
                        $devSpec['Position'] = $low_idx;
                        $devSpec['Height'] = $height;
                        $devSpec['Device'] = '__EMPTY';
                        $invData[] = $devSpec;
                    }
                }
            }
        }
        assignStatsVal($Stats, $dc, $dcStats);

        $percentDone += $incrementalPercent;
        if ( php_sapi_name()!="cli" ) {
            JobQueue::updatePercentage( $sessID, $percentDone );
        }
    }

    return array($Stats, $invData, $invCab, $limitedUser);
}

/**
 * Summarize the statistics for as many levels there are. The last level has the
 *   values of the DC.
 *
 * @param array $DC_Stats
 * @param array $KPIS
 */
function computeDCStatsSummary(&$DCStats, $KPIS)
{
    $initFlag = true;
    foreach (array_keys($DCStats) as $key) {
        if (! in_array($key, $KPIS)) {
            // there is a level with information, initialize the summary statistics
            if ($initFlag) {
                $initFlag = false;
                foreach ($KPIS as $kpi)
                    $DCStats[$kpi] = 0;
            }
            computeDCStatsSummary($DCStats[$key], $KPIS);
            foreach ($KPIS as $kpi) {
                $DCStats[$kpi] += $DCStats[$key][$kpi];
            }
        }
    }
}

/**
 * Write the rack inventory to the worksheet
 *
 * @param PHPExcel_Worksheet $worksheet
 * @param array $sheetProps
 * @param array $Rack_Inv
 */
function writeRackInventoryContent($worksheet, $sheetProps, $Rack_Inv)
{
    global $sessID;


    // A little code to update the counter

    $percentDone = 50;
    $sectionMaxPercent = 40;
    $incrementalPercent = 1 / sizeof( $Rack_Inv) * $sectionMaxPercent;

    $row = 2;
    ksort($Rack_Inv);
    foreach ($Rack_Inv as $DCRoom => $rack) {
        $dc_racks = $Rack_Inv[$DCRoom];
        ksort($dc_racks);

        $subSectionMax = $incrementalPercent;
        $subIncrement = 1 / sizeof($dc_racks) * $subSectionMax;

        foreach ($dc_racks as $name => $rack) {
            $worksheet->fromArray($rack, null, 'A' . $row);
            foreach ($sheetProps['ExpStr'] as $colName) {
                $colIdxAlpha = $sheetProps['ColIdx'][$colName][1];
                $worksheet->setCellValueExplicit(
                    $colIdxAlpha . $row, $rack[$colName],
                    PHPExcel_Cell_DataType::TYPE_STRING);
            }
            $row++;
            $percentDone += $subIncrement;
            if ( php_sapi_name()!="cli" ) {
                JobQueue::updatePercentage( $sessID, $percentDone );
            }
        }
    }

    // Round up the math
    if ( php_sapi_name()!="cli" ) {
        JobQueue::updatePercentage( $sessID, 90 );
    }
}

/**
 * Compose a statistics line value array
 *
 * @param string $itemName the kind of statistics line
 * @param array $values
 * @return array statistics values of one line
 */
function statsLine($itemName, $values)
{
    return array(
        $itemName,
        $values['Fl_Spc'],
        $values['Rk_Num'],
        $values['Rk_Res'],
        $values['Rk_UtT'],
        $values['Rk_UtU'],
        $values['Rk_UtT'] != 0 ? ($values['Rk_UtU'] / $values['Rk_UtT']) : null,
        $values['Rk_UtE'],
        $values['Rk_UtT'] != 0 ? ($values['Rk_UtE'] / $values['Rk_UtT']) : null,
        $values['Watts'] / 1000,
        $values['DesignPower']
    );
}

/**
 * Write one line of the data center statistics
 *
 * @param PHPExcel_Worksheet $worksheet
 * @param string $style
 * @param array $wsProps
 * @param integer $rownum
 * @param array $DCStatsSum
 */
function writeDCStatsLine($worksheet, $style, $wsProps, $rownum, $DCStatsSum)
{
    $worksheet->fromArray($DCStatsSum, null, 'A' . $rownum);
    $lastCol = count($DCStatsSum) - 1;
    $range = 'A' . $rownum . ':' . PHPExcel_Cell::stringFromColumnIndex($lastCol)
        . $rownum;
    switch ($style) {
        case 'Total':
            $worksheet->getStyle($range)
                ->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $worksheet->getStyle($range)->getFill()->getStartColor()
                ->setRGB($wsProps['FillColor']);
            $worksheet->getStyle($range)
                ->getFont()
                ->getColor()
                ->setRGB($wsProps['HeadingFontColor']);
        case 'SummaryLine':
            $worksheet->getStyle($range)->getFont()->setBold(true);
            $worksheet->getStyle($range)
                ->applyFromArray($wsProps['Border Style']);
    }
}

/**
 * Write the DC Stats worksheet content
 *
 * Recursively write the statistics on the four levels of data center structuring.
 * $DCStats is a recursive data structure. Either it contains the statistics values
 * for the current value or an index to the next level, e.g.
 *   . $DCStats['Rk_Num'] is the sum of the number of racks on this level
 *   or
 *   . $DCStats[<DC>] is the array with the statistics value for the data center
 *     DC
 *
 * @param array $wsProps
 * @param PHPExcel_Worksheet $worksheet
 * @param array DCStats
 * @param int $level level of statistic data
 * @param int $row row number
 * @param $rowTitle the title the row statistics will get
 */
function writeDCStatsContent($wsProps, $worksheet, &$DCStats, $level, &$row,
    $rowTitle)
{
    $level++;
    switch ($level) {
    	case 1:
    	    $style = 'Total';
    	    $prefix = '';
    	    break;
    	case 2:
    	    $style = 'SummaryLine';
    	    $prefix = '';

    	    break;
    	default:
    	    $prefix = str_pad('', ($level*5));
    	    $style = null;
    }
    $rowTitleStr = $prefix . $rowTitle;
 //   $sumFlag = true;
 //   if (($level > 1) and $sumFlag) {
    if ($level > 1) {
        writeDCStatsLine($worksheet, $style, $wsProps, ++$row,
            statsLine($rowTitleStr, $DCStats));
        // $sumFlag = false;
    }
    foreach ($DCStats as $StatKey => $val) {
        if (is_array($val)) {
            writeDCStatsContent($wsProps, $worksheet, $DCStats[$StatKey],
                $level, $row, $StatKey);
        }
    }
    if ($level == 1) {
        writeDCStatsLine($worksheet, $style, $wsProps, ++$row,
            statsLine($rowTitleStr, $DCStats));
    }
}

/**
 * Write sheet 'DC Stats Summary <timestamp>'
 *
 * @param array $DProps
 * @param PHPExcel $objPHPExcel
 * @param array DCStats
 * @param string $thisDate
 */
function writeDCStatsSummary($DProps, $objPHPExcel, $DCStats, $thisDate)
{
    $wsKind = 'DC Stats';
    computeDCStatsSummary($DCStats, $DProps['DC Stats']['KPIs']);

    $worksheet = $objPHPExcel->createSheet(0);
    setWorksheetProperties($worksheet, $wsKind, $DProps, $thisDate);
    $worksheet->getPageSetup()->setFirstPageNumber(1);
    writeWSHeader($worksheet, $wsKind, $DProps[$wsKind]);
    $rowNumber = 1;
    writeDCStatsContent($DProps[$wsKind], $worksheet, $DCStats, 0, $rowNumber, 'Total');
    formatWSColumns($worksheet, $DProps[$wsKind]['Columns']);
}

/**
 * Write the front page of the Excel spreadsheet
 *
 * @param PHPExcel_Worksheet $worksheet
 * @param array $config
 * @param array $DProps
 */
function writeFrontPageContent($worksheet, $config, $DProps)
{
    $worksheet->SetTitle('Front Page');
    // add logo
    $objDrawing = new PHPExcel_Worksheet_Drawing();
    $objDrawing->setWorksheet($worksheet);
    $objDrawing->setName($DProps['Front Page']['Logo Name']);
    $objDrawing->setDescription($DProps['Front Page']['Logo Description']);
    $apath = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
    $objDrawing->setPath($apath . $config->ParameterArray['PDFLogoFile']);
    $objDrawing->setCoordinates('A1');
    $objDrawing->setOffsetX(5);
    $objDrawing->setOffsetY(5);
    // set the header of the print out
    $header_range = $DProps['Front Page']['HeaderRange'];
    $fillcolor = $config->ParameterArray['HeaderColor'];
    $fillcolor = (strpos($fillcolor, '#') == 0) ? substr($fillcolor, 1) : $fillcolor;
    $worksheet->getStyle($header_range)
        ->getFill()
        ->getStartColor()
        ->setRGB($fillcolor);

    $org_font_size = 20;
    $worksheet->setCellValue('A2', $config->ParameterArray['OrgName']);
    $worksheet->getStyle('A2')
        ->getFont()
        ->setSize($org_font_size);
    $worksheet->getStyle('A2')
        ->getFont()
        ->setBold(true);
    $worksheet->getRowDimension('2')->setRowHeight($org_font_size + 2);
    $worksheet->setCellValue('A4', 'Report generated by \''
        . $DProps['Doc']['User']
        . '\' on ' . date('Y-m-d H:i:s'));

    $worksheet->setCellValue('A7', 'Notes');
    $worksheet->getStyle('A7')
        ->getFont()
        ->setSize(14);
    $worksheet->getStyle('A7')
        ->getFont()
        ->setBold(true);

    $max_remarks = count($DProps['Front Page']['remarks']);
    $offset = 8;
    for ($idx = 0; $idx < $max_remarks; $idx ++) {
        $row = $offset + $idx;
        $worksheet->setCellValueExplicit('B' . ($row),
            $DProps['Front Page']['remarks'][$idx],
            PHPExcel_Cell_DataType::TYPE_STRING);
    }
    $worksheet->getStyle('B' . $offset . ':B' . ($offset + $max_remarks - 1))
        ->getAlignment()
        ->setWrapText(true);
    $worksheet->getColumnDimension('B')->setWidth(120);
    $worksheet->getTabColor()->setRGB($fillcolor);
}

/**
 * Write the content of the "DC Inventory" worksheet
 *
 * @param PHPExcel_Worksheet $worksheet
 * @param array $sheetProps properties of the worksheet
 * @param array $invData array with the inventory data
 */
function writeDCInvContent($worksheet, $sheetProps, $invData)
{
    $colIdx = $sheetProps['ColIdx'];
    // first line is the header for the worksheet
    $worksheet->fromArray($invData, null, 'A2');
    ReportStats::get()->report('Info', 'Number of Inventory entries '.count($invData));
    $highestRow = count($invData);

    foreach ($sheetProps['ExpStr'] as $colName) {
        $colLetter = $colIdx[$colName][1];
        for ($row = 0; $row < $highestRow; $row++) {
            $worksheet->setCellValueExplicit($colLetter . ($row+2),
                $invData[$row][$colName], PHPExcel_Cell_DataType::TYPE_STRING);
        }
    }
    // unset($invData);
}

/**
 * Write sheet 'DC Inventory <timestamp>'
 *
 * @param array $DProps
 * @param PHPExcel $objPHPExcel
 * @param string $thisDate
 * @return (array|array|boolean)[] DC statistics and rack inventory
 */
function writeDCInventory($DProps, $objPHPExcel, $thisDate)
{
    global $sessID;

    $wsKind = 'DC Inventory';
    $worksheet = $objPHPExcel->getActiveSheet();
    $objPHPExcel->setActiveSheetIndex(0);

    if ( php_sapi_name()!="cli" ) {
        JobQueue::updateStatus( $sessID, __("Computing DC Inventory" ));
    }

    setWorksheetProperties($worksheet, $wsKind, $DProps, $thisDate);
    writeWSHeader($worksheet, $wsKind, $DProps[$wsKind]);
    ReportStats::get()->report('Info', $wsKind . ' - Header set');
    list($DCStats, $invData, $Rack_Inv, $limitedUser) =
        computeSheetBodyDCInventory($DProps);
    ReportStats::get()->report('Info', $wsKind . ' - computed body');

    if ( php_sapi_name()!="cli" ) {
        JobQueue::updateStatus( $sessID, __("Writing Inventory to Spreadsheet" ));
        JobQueue::updatePercentage( $sessID, 50 );
    }

    writeDCInvContent($worksheet, $DProps[$wsKind], $invData);
    ReportStats::get()->report('Info', $wsKind . ' - write body');

    if ( php_sapi_name()!="cli" ) {
        JobQueue::updateStatus( $sessID, __("Formatting Spreadsheet" ));
    }

    formatWSColumns($worksheet, $DProps[$wsKind]['Columns']);
    $worksheet->setAutoFilter($worksheet->calculateWorksheetDimension());

    return array($DCStats, $Rack_Inv, $limitedUser);
}

/**
 * Write sheet 'Rack Inventory <timestamp>'
 *
 * @param array $DProps
 * @param PHPExcel $objPHPExcel
 * @param array $Rack_Inv
 * @param string $thisDate
 */
function writeRackInventory($DProps, $objPHPExcel, $Rack_Inv, $thisDate)
{
    $wsKind = 'Rack Inventory';
    $worksheet = $objPHPExcel->createSheet();
    setWorksheetProperties($worksheet, $wsKind, $DProps, $thisDate);
    writeWSHeader($worksheet, $wsKind, $DProps[$wsKind]);
    writeRackInventoryContent($worksheet, $DProps[$wsKind], $Rack_Inv);
    formatWSColumns($worksheet, $DProps[$wsKind]['Columns']);
    $worksheet->setAutoFilter($worksheet->calculateWorksheetDimension());
}

/**
 * Write sheet 'Rack Inventory <timestamp>'
 *
 * @param array $DProps
 * @param array $config
 * @param PHPExcel $objPHPExcel
 * @param $thisDate the date string of the current execution
 */
function writeFrontPage($DProps, $config, $objPHPExcel, $thisDate)
{
    // make this the first worksheet
    $wsKind = 'Front Page';
    $worksheet = $objPHPExcel->createSheet(0);
    $objPHPExcel->setActiveSheetIndex(0);
    setWorksheetProperties($worksheet, $wsKind, $DProps, $thisDate);
    writeWSHeader($worksheet, $wsKind, $DProps[$wsKind]);
    writeFrontPageContent($worksheet, $config, $DProps);
}

/**
 * Generate new workbook 'DC_Statistics_<timestamp>.xlsx'
 *
 * @param array $DProps
 * @param PHPExcel $objPHPExcel
 * @param string $thisDate
 */
function writeExcelReport(&$DProps, $objPHPExcel, $thisDate)
{
    ReportStats::get()->report('Info', 'User: ' . $DProps['Doc']['User']
        . ' Version: ' . $DProps['Doc']['version']);

    // Crude status reporting

    $config = new Config();
    $config->Config();
    addColumnIndices($DProps);

    // Generate new workbook 'DC_Statistics_<timestamp>.xlsx'
    setDocumentProperties($objPHPExcel, $thisDate,
        $config->ParameterArray['OrgName'], $DProps);

    list($DCStats, $Rack_Inv, $limitedUser) = writeDCInventory($DProps,
        $objPHPExcel, $thisDate);
    ReportStats::get()->report('Info', 'DC Inventory');

    if (! $limitedUser) {
        writeDCStatsSummary($DProps, $objPHPExcel, $DCStats, $thisDate);
        ReportStats::get()->report('Info', 'DC Stats');
    }

    writeRackInventory($DProps, $objPHPExcel, $Rack_Inv, $thisDate);
    ReportStats::get()->report('Info', 'Rack Inventory');

    writeFrontPage($DProps, $config, $objPHPExcel, $thisDate);
    ReportStats::get()->report('Info', 'Front Page');

    if ( php_sapi_name()!="cli" ) {
        JobQueue::updateStatus( session_id(), "Preparing to transmit file" );
    }
}

/*
 * Caching with Memory Serialized using PHPExcel 1.7.8 actually showed not
 * only a reduction on the required memory footprint but provides a speedup of
 * 5% (40.18sec vs 47.23sec). Memory reduction was factor 2.2 (182.5MB vs 396.5MB).
 */
$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;
$retcode = PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

// REMARK: Comment in if a reporting on the resource usage is needed
// ReportStats::get()->useStatReporting();

$thisDate = date('Y-m-d');

$objPHPExcel = new PHPExcel();
writeExcelReport($DProps, $objPHPExcel, $thisDate);

// send out document, save Excel 2007 file

$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
if (PHP_SAPI != 'cli') {
    header('Content-type: application/application/vnd.openxmlformats-officedocument.'
        . 'spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=DC_Statistics_" . $thisDate
     . ".xlsx");
    header('Cache-Control: max-age=0');

    // write file to the browser
    $objWriter->save('php://output');
} else {
    $fname = $ReportOutputFolder."openDCIM-Asset_Report-".date("Y-m-d").".xlsx";
    $objWriter->save( $fname );
}

ReportStats::get()->report('Totals');
$objPHPExcel->disconnectWorksheets();
unset($objPHPExcel);

if ( php_sapi_name()!="cli" ) {
    JobQueue::updatePercentage( $sessID, 100 );
}
