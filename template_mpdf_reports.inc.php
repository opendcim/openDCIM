<?php 
    include "vendor/mpdf/mpdf/mpdf.php";

    $header=(!isset($header))?$config->ParameterArray["OrgName"]:$header;
    $subheader=(!isset($subheader))?"":$subheader;
    $reportHTML=(!isset($reportHTML))?__("No Data to Display"):$reportHTML;

    // $mpdf=new \Mpdf\Mpdf('','',0,'',20,15,48,25,10,10);
    $mpdf=new Mpdf();
    $mpdf->useOnlyCoreFonts = true;    // false is default
    //$mpdf->SetProtection(array('print'));
    $mpdf->SetTitle($header . " " . $subheader);
    $mpdf->SetAuthor($config->ParameterArray["OrgName"]);
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->useActiveForms = true;

    /* Note: typically you would do zebra-striping in the report using an 
       nth-child(even) type of css selector on tr (if you know for sure
       your report doesn't use rowspans) or on tbody (if your report uses
       rowspans) - but mpdf doesn't support css classes for the tbody
       tag, so if we want to do reliable zebra-striping, you have to 
       put code into the report generation to do it and use the 
       tr.altcolor selector.
       see report_panel_schedule.php
    */


    $html = <<<EOT
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

  <style>
    body {
        font-family: sans-serif;
        font-size: 10pt;
    }
    p { margin: 0pt; }
    td { vertical-align: top; }
    .items td {
        border: 0.1mm solid #000000;
    }
    table {
        border-collapse: collapse;
        border-spacing: 0px;
        margin-bottom: 25px;
        page-break-inside:avoid;
    }
    table thead td {
        background-color: #EEEEEE;
        text-align: center;
    }
    .items td.bottom {
        background-color: #FFFFFF;
    }
    .items td.totals {
        text-align: right;
    }
    td.altcolor, tr.altcolor {
        background-color: #E0EBFF;
    }
  </style>
EOT;
    $html .= $reportHead;
    $html .= <<<EOT
  <title>openDCIM Inventory Reporting</title>
</head>
<body>

<!--mpdf
<htmlpageheader name="myheader">
<table width="100%"><tr>
EOT;

    $html .= '<td width="50%"><img src="images/'.$config->ParameterArray['PDFLogoFile'].'">';
    $html .= '<td width="50%" style="text-align: right;"><h4>'.$header.'<br>';
    $html .= __("Date").': '.strftime("%x").'</h4></td>';

    $html .= <<<EOT
</tr></table>
</htmlpageheader>
<htmlpagefooter name="myfooter">
<div style="border-top: 1px solid #000000; font-size: 9pt; text-align: center; padding-top: 3mm; ">
Page {PAGENO} of {nb}
</div>
</htmlpagefooter>
<sethtmlpageheader name="myheader" value="on" show-this-page="1" />
<sethtmlpagefooter name="myfooter" value="on" />
mpdf-->
EOT;

    $html .= '<h2>'.$subheader.'</h2>';
    $html .= $reportHTML;
    $html .= '</body></html>';

    // since mpdf is slow, give it more than 30 seconds to generate report
    set_time_limit(150);
    $mpdf->WriteHTML($html);
    $mpdf->Output();
    //print $html;
?>
