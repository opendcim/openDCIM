<?php
	require_once( '../db.inc.php' );
	header("Content-type: text/css");
?>


/* Reset All Broswers to Nothing */
@import url('reset.css');

html {
	font-family: helvetica,arial;
	font-size: .833em;
	padding-left: .9em;
	padding-right: .9em;
}
select {padding: .05em;}
fieldset table, table {border: 1px solid grey;}
textarea {white-space: pre;word-wrap: break-word;}
.hide {display: none !important;}
.show {display: block;}
.greybg {background-color: lightGrey;}
.warning {text-align: center; color: red; text-transform: uppercase;}
.right {text-align: right;}
.left {text-align: left;}
.custom-combobox {position: relative;display: inline-block;}

[readonly],[disabled] {
	background-color: #dcdcdc;
	border: 1px dotted grey;
	padding: 1px;
	color: #000000;
	cursor: default;
} 

.arrow_left { position: relative; background: #ffffff; border: 1px solid #000000; } 
.arrow_left:after, .arrow_left:before { right: 100%; border: solid transparent; content: " "; height: 0; width: 0; position: absolute; pointer-events: none; } 
.arrow_left:after { border-color: rgba(255, 255, 255, 0); border-right-color: #ffffff; border-width: 15px; top: 15px; margin-top: -15px; } 
.arrow_left:before { border-color: rgba(0, 0, 0, 0); border-right-color: #000000; border-width: 16px; top: 15px; margin-top: -16px; }

.no-close .ui-dialog-titlebar-close {display: none;}

@keyframes loading{
	from {
		-webkit-transform:rotate(0deg);
		-moz-transform:rotate(0deg);
		-o-transform:rotate(0deg);
	}
	to {
		-webkit-transform:rotate(360deg);
		-moz-transform:rotate(360deg);
		-o-transform:rotate(360deg);
	}
}

@-webkit-keyframes loading{
	from {
		-webkit-transform:rotate(0deg);
		-moz-transform:rotate(0deg);
		-o-transform:rotate(0deg);
	}
	to {
		-webkit-transform:rotate(360deg);
		-moz-transform:rotate(360deg);
		-o-transform:rotate(360deg);
	}
}


.rotate{
	animation: loading 0.8s;
	-webkit-animation: loading 0.8s;

	animation-iteration-count: infinite;
	-webkit-animation-iteration-count: infinite; /*Safari and Chrome*/

	overflow:hidden;
}   


/* css for timepicker */
.ui-timepicker-div .ui-widget-header {margin-bottom: 8px;}
.ui-timepicker-div dl {text-align: left;}
.ui-timepicker-div dl dt {height: 25px; margin-bottom: -25px;}
.ui-timepicker-div dl dd {margin: 0 10px 10px 65px;}
.ui-timepicker-div td {font-size: 90%;}
.ui-tpicker-grid-label {background: none; border: none; margin: 0; padding: 0;}

/*  Header/logo */
#header{
	padding:5px 0;
	background:<?php echo $config->ParameterArray['HeaderColor']; ?> url(../images/<?php echo $config->ParameterArray['PDFLogoFile']; ?>) no-repeat center center;
	height:66px;
	position: relative;
}

/* Configuration Page */
div.cp { position: relative;}
.miniColors-trigger { position: absolute; top: 0; right: 0;}
.config .center input { width: 95%; }
#configtabs { min-width: 670px; }
#configtabs button { margin-left: 0.5em; margin-right: 0.5em;}
#configtabs span { font-style: italic; font-size: -1;}
#configtabs label:after {content:":"; margin-right: 0.5em;}
#configtabs #general div > input {width: 20em; }
#configtabs #general #rackusage input {width: 2em; }
#configtabs #general #rackusage > div > div:nth-child(5) { width: 6em; }
#configtabs #style .cp > input {width: 7em; }
#configtabs #email div > input {width: 20em; }

#configtabs #reporting div:first-child + div > input {width: 20em; }

div#imageselection { display: none;}
#imageselection span { display: block; padding: 0.25em 0 0.5em 0.5em; cursor: pointer; text-decoration: underline; border: 1px solid white;}
#imageselection #preview { position: absolute; top: 0; right: 0; height: 180px; width: 180px; margin: 0.1em 0 0 0; padding: 0; border: 0px solid black;}
#imageselection #filelist { position: absolute; top: 0; left: 1em; height: 210px; width: 175px; overflow-y: scroll; overflow-x: hidden; }

.ui-menu-item ul { max-height: 200px; overflow: auto; }
#tzmenu {display: none;}

#tooltip, #cdutooltip { min-height: 300px; min-width: 550px; }

/* index */
.index .table, .index .table .title {background-color: white;}
.index .table .title {font-weight: bold; font-size: 1.25em;}
.index .table div {padding: 3px;}
.rackrequest div:first-child div {background-color: gray;text-align: center;color: white;font-weight: bold;}
.overdue {background-color:#FFE6F4;}
.soon {background-color:#FFFFAA;}
.clear {background-color:white;}

/* Rack Request Page */
.request fieldset {
		background-color: white;
		border: 1px solid grey;
		padding: 10px;
		margin-bottom: 8px;
}
.request legend {border: 1px <?php echo $config->ParameterArray['HeaderColor']; ?> solid;background-color: white; padding: .15em;}
.errmsg {display:block;font-style:italic;margin-left:2em;}

/* Datacenter Stats */
.dcstats .heading > div{width: 89%;display: inline-block;vertical-align: middle;}
.dcstats .heading > div + div {width: 10%;}
.dcstats .heading > div + div button {display: block;width: 100%;}
.dcstats .table, .dcstats .table .title { background-color: white; }
.dcstats .table .title { font-weight: bold; font-size: 1.25em; }
.dcstats .table .title span { font-size: 0.6em; vertical-align: top;}
.dcstats .table .title span:before { content:"  [ "; }
.dcstats .table .title span:after { content:" ]";}
.dcstats .table div {padding: 3px;}
div#dcstats { display: table;}
div#dcstats > div{ width: 100%;}
div#dcstats .table + .table > div > div + div{white-space: pre; text-align: right;}
.canvas {position: relative; background-repeat: no-repeat;}
.canvas img {position: absolute; top: 0; left: 0;}
.dcstats ~ #tt span {font-size: 1.5em; text-align: center; font-weight: bold;}
.dcstats ~ #tt ul {list-style-type: none;}
.dcstats ~ #tt ul li.red {background: url('../images/rs.png') left center no-repeat; line-height: 20px; padding-left: 20px;}
.dcstats ~ #tt ul li.green {background: url('../images/gs.png') left center no-repeat; line-height: 20px; padding-left: 20px;}
.dcstats ~ #tt ul li.yellow {background: url('../images/ys.png') left center no-repeat; line-height: 20px; padding-left: 20px;}
.dcstats ~ #tt ul li.wtf {background: url('../images/us.png') left center no-repeat; line-height: 20px; padding-left: 20px;}
#maptitle {padding: 8px; font-size: 120%; font-weight: bold;} 
#maptitle .nav {float: right;}

/* Storage Room */
.storage .table, .storage .table #title { background: white; }
.storage .table #title {filter: none;}
.storage .table .title { font-weight: bold; font-size: 1.25em; }
.storage .table div {padding: 3px;}
.storage .table {min-width: 400px; max-width: 600px; margin-top: 2em;}

/* Sidebar Menu */
#sidebar {
	position: relative;
	min-width: 200px;
	display: inline-block;
	vertical-align: top;
}
#sidebar input, #sidebar textarea {
	height: 27px;
	width: 170px;
	margin: 0;
	vertical-align: text-bottom;
	clear: left;
	display: inline-block;
	white-space: nowrap;
	word-wrap: normal;
	padding: 5px;
}
#sidebar textarea {
	-webkit-box-sizing: border-box; /* Safari/Chrome, other WebKit */
	-moz-box-sizing: border-box;    /* Firefox, other Gecko */
	box-sizing: border-box;         /* Opera/IE 8+ */
	border-style: solid none solid solid;
	border-width: 1px 0 1px 1px;
	border-color: black;
	resize: none;
}
#sidebar input + button, #sidebar .text-core + button, button.iebug, #sidebar textarea + button {
	height: 27px;
	padding: 0px;
	margin: 0px;
	display: inline-block;
	vertical-align: top;
	clear: right;
	border-left: 0px solid;
	-webkit-box-sizing: border-box; /* Safari/Chrome, other WebKit */
	-moz-box-sizing: border-box;    /* Firefox, other Gecko */
	box-sizing: border-box;         /* Opera/IE 8+ */
	-moz-border-radius: 0px;
	-moz-box-shadow: 0px;
	-webkit-border-radius: 0px;
	border-radius: 0px;
}
#sidebar form { margin-bottom: 4px; }
#sidebar input.search { height: 15px; padding: 5px; width: 141px; border: 1px solid black; border-right: 0; vertical-align: top;}
#sidebar input + button img, #sidebar .text-arrow + button img {height: 27px;}
#sidebar div.text-core {width: 150px; height: 27px;}
#sidebar div.text-core textarea{ width: 151px; height: 27px;}
#sidebar .advsearch { background: white; display: block; height: 4.5em; position: absolute; top: 0px; width: 350px; z-index: 99; }
#searchadv ~ select { padding: 5px; border: 1px solid black; }
#sidebar .advsearch.hide { display: none; }
#advsrch { color: <?php echo $config->ParameterArray['LinkColor']; ?>; cursor: pointer; }
#advsrch:before {content:"[ ";}
#advsrch:after {content:" ]";}
#searchadv ~ .ui-icon.ui-icon-close { position: absolute; top: 0; right: 0; cursor: pointer;}

.text-arrow {
	-moz-box-sizing: border-box;
	background: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAkAAAAOAQMAAADHWqTrAAAAA3NCSVQICAjb4U/gAAAABlBMVEX///8yXJnt8Ns4AAAACXBIWXMAAAsSAAALEgHS3X78AAAAHHRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1MzmNZGAwAAABpJREFUCJljYEAF/xsY6hkY7BgYZBgYOFBkADkdAmFDagYFAAAAAElFTkSuQmCC") no-repeat scroll 50% 50% transparent;
	cursor: pointer;
	height: 22px;
	position: absolute;
	right: 0;
	top: 0;
	width: 22px;
	z-index: 2;
}
.text-core { display: inline-block; }

.ui-menu {z-index: 100;}
.ui-autocomplete { max-height: 100px; overflow-y: auto; overflow-x: hidden; padding-right: 20px;}
* html .ui-autocomplete {height: 100px;}
.ui-autocomplete li.ui-menu-item {display: inline-block;}
.ui-menu .ui-menu-item a { line-height: 1 !important; white-space: nowrap !important; overflow: hidden;}

#gandalf {
	height: 100%;
	width: 100%;
	z-index: 99;
	background-color: white;
	position: absolute;
	top: 0;
}
#gandalf div {
	font-family: monospace;
	white-space: pre;
	width: 400px;
	margin-left: auto;
	margin-right: auto;
}

/* Mapmaker */
.mapmaker > div{width: 77%;display: inline-block;vertical-align: middle;}
.mapmaker > div + div {width: 19%;}
.mapmaker .table .table {margin-left: auto;}
.mapmaker + .center div{position: relative;width: 100%;}
.mapmaker + .center > div > div.container {position: absolute;top: 0px;left: 0px;}

/* Zonemaker */
.zonemaker > div{width: 100%;display: inline-block;vertical-align: middle;}
.zonemaker .table .table {margin-left: auto;}
.zonemaker + .center div{position: relative;width: 100%;}
.zonemaker + .center > div > div.container {position: absolute;top: 0px;left: 0px;}

/* Basic Page Layout */
.page {position: relative;width: 100%;}
.clear {clear: both;}
p, h2, h3, h1 {margin-top: 1em;margin-bottom: 1em;}
h2 {font-size: 1.5em;text-align: center;}
h3 {font-size: 1.16em;text-align: center;}
h3 + h3 {color: red;font-weight: bold;}
a:link, a:hover, a:visited:hover {color:<?php echo $config->ParameterArray['LinkColor']; ?>;}
a:visited {color: <?php echo $config->ParameterArray['VisitedLinkColor']; ?>;}

div.main {
	display: inline-block;
	vertical-align: top;
	min-height: 500px;
	padding: 5px;
	background-color: <?php echo $config->ParameterArray['BodyColor']; ?>;
	border: 1px dotted #333;
}
div.center > div {display: inline-block;text-align: left;}
.center {text-align: center;min-height: 400px;}
.centermargin {margin-left: auto;margin-right: auto;}

.table {display: table;text-align: left;border-collapse: collapse;}
.caption {caption-side: bottom; text-align: center; display: table-caption !important;}
.title {caption-side: top; text-align: center; display: table-caption !important;}
div.table > div {display: table-row;}
div.table > div > div {display: table-cell;vertical-align: middle; /* padding-bottom: .75em; */}
/* div.table > div > div span {display: block;font-size: 0.75em;} */
.table label{width:130px;}
.whiteborder, .whiteborder div {border: 1px solid white;}
.border, .border div {border: 1px solid gray;}

/* Search Results */
.search .center {text-align: left;}
.search .main ol, .search .main ul{list-style-type: none;margin-left: 1em;}
.search ol {margin-top: .35em;}
.search ol li {margin-bottom: .35em;}
.search ol ul li {margin-left: 1em;margin-bottom: 0em;}
.search ol ul li div, .search ol li.datacenter div {display: inline;}
.search ol ul li div, .search ol li.cabinet div {display: inline;}
.search ol ul li div img, .search ol li.cabinet div img {vertical-align: middle;height: 1em;margin-right: .25em;}
.search .main .bullet { background: url("minus.gif") no-repeat scroll left center transparent; cursor: pointer; padding-left: 15px;}
.search .hidecontents li.cabinet > ol { display: none; }

/* User Rights */
.rights > div:nth-last-child(2) div {text-align: center;padding-top: .75em;padding-bottom: .75em;}
div.table > div + div + div + div > div + div label {float: none;}

/* Contact Editor */
#deletedialog {display: none;}
#deletedialog p {font-weight: bold;}
#deletedialog li {margin-left: 1em; list-style: disc outside none;}
#deletedialog div {width: 45%; display: inline-block; vertical-align: top;}
#deletedialog .middle {width: 9%;}

/* Inventory Reports */
.reports fieldset {margin-right: 20px;}
#reports > div {display: inline-block;vertical-align: top;}
#reports > div a {display: block;}

/* PDU Info */
.pdu .center > div + div > .table > div > div{padding: 3px;}

/* Power Panels */
div.center > div + div {vertical-align: top;padding-left: 1em;}
div.center > div + div div.table {background-color: white;}
div.center div table {
	background-color: white;
	border-collapse: collapse;
	margin-left: auto;
	margin-right: auto;
	max-width: 400px;
}
div.center div table table{min-width: 150px;}
div.center div table, div.center div tr, div.center div td {border: 1px solid gray;}
.cabinet tr > td:first-child, .panelmgr .polenumber {padding: 0.25em 0.5em;text-align: center;}
.panelmgr .polelabel {
	min-width: 150px;
	max-width: 400px;
	padding: 0.25em 0.5em 0.25em 1em;
	vertical-align: middle;
}
.polelabel a {display: block;margin-bottom: 0.35em;}
.polelabel a span {display: block;margin-left: 1.5em;}
td#oddeven {padding: 0px;text-align: left;width: 150px;}
.caption h3 {margin-bottom: 0px;font-size: 1.25em;}
#powerinfo {margin-top: 0em;}
#powerinfo .table {background-color: white;}
#powerinfo .caption {border: 0px !important;}
div.error {margin-top: 2em;margin-bottom: 2em;border: 1px dotted gray;}
.error legend {color: red;font-weight: bold;}
.error > div > div {width: 200px;vertical-align: top !important;}
.error > div > div + div {font-style: italic;}
#pdutest {display: none;}


/* Department Administration */
#groupadmin {
	overflow: hidden;
	min-width: 580px;
	min-height: 150px;
	display: none;
	margin-top: 20px;
	border: 1px solid gray;
}
#deptgroup {background-color: <?php echo $config->ParameterArray['BodyColor']; ?>;}
#deptgroup > div {padding:5px 10px;width:580px;min-height:300px;}
#deptgroup > div h3 {margin-top: 0;margin-bottom: 5px;}
#deptgroup > div h3 button {margin-left:10px;vertical-align:middle;}
#deptgroup h3 + div {margin-left: 42.5px;}
#deptgroup select {width: 440px;}
#displaynone {display: none !important;}

/* color indication */
.colorbox  {
    #float: none;
    width: 1em;
    display: inline-block;
    height: 1.2em;
    margin: 0px;
    padding: 0px;
    border: solid rgba(0,0,0,.5) 1px;
}

/* Rack Content */
#infopanel {
		position: relative;
		display: inline-block;
		min-width: 200px;
}
#infopanel fieldset, .reports fieldset {
		background-color: white;
		border: 1px solid grey;
		padding: 10px;
		margin-bottom: 8px;
}
#infopanel fieldset button, #infopanel fieldset input[type=submit], #infopanel fieldset input[type=button],.reports fieldset button, .reports fieldset input[type=submit], .reports fieldset input[type=button] {width: 100%;}
#infopanel legend, .device legend, .reports legend {border: 1px <?php echo $config->ParameterArray['HeaderColor']; ?> solid;background-color: white;}
#infopanel fieldset > p > span + span {
	display: inline-block;
	vertical-align: middle;
	height: 1.1em;
	line-height: 1.2em;
	width: 150px;
	overflow: hidden;
}
div.cabinet {
	display: inline-block;
	vertical-align: top;
	min-width: 200px;
	max-width: 250px;
	margin-right: 20px;
}

.cabinet td + td {vertical-align: middle;padding: 0.25em 0.5em;width: 90%;}
.cabinet th{font-size: 1.5em;padding: 0.25em;text-align: center;}
#zerou a{display: block;}

.cabnavigator .nav { text-align: center; }
.cabnavigator .nav li { margin-top: 0.1em; border: 1px solid darkGray;}
.cabnavigator .nav a:hover li { border-color: black; }

.cabnavigator.tooltip {
	min-height: 30px;
	min-width: 30px;
	z-index: 99;
	position: absolute;
	white-space: nowrap;
}
.cabnavigator.tooltip div {
	border: 0 none;
	line-height: 1.25em;
	margin: 5px;
	padding: 3px;
}
.cabinet .error { background-color: <?php echo $config->ParameterArray['CriticalColor']; ?>; }

/* Cabinet Properties */

#infopanel table#cabprop {
    margin: 0px 0px 2px 0px;
	border-collapse: separate;
    border: 0px none;
	border-spacing: 3px;
	width: 100%;
	min-width: 200px; max-width: 350px;
}
table#cabprop tr {
    width: 100%;
    padding: 2px;
    margin: 1px 0px 1px 1px;
}
#infopanel fieldset table#cabprop td.left {
    padding: 3px 2px 3px 2px;
    font-weight: bold;
    border: 0px none;
    border-bottom: 1px solid gray;
    margin: 2px 2px 2px 0px;
}
#infopanel fieldset table#cabprop td.right {
    /* width: 50%; */
    text-align: left;
    padding: 3px 2px 3px 4px;
    border: 0px none;
    border-bottom: 1px solid gray;
    border-right: 2px solid gray;
    margin: 2px 0px 2px 2px;
    /* max-width: 280px; */
}
table#cabprop span.text-label {
     -webkit-border-radius: 2px;
     border-radius: 2px;
     box-sizing: border-box;
     border: 1px solid #9daccc;
     background: #e2e6f0;
     color: #000;
     padding: 0px 3px 0px 3px;
     margin: 0 2px 0px 0;
     font: 11px "lucida grande",tahoma,verdana,arial,sans-serif;
}

/* devices.php  Device Detail */
.device fieldset {
	display: inline-block;
	vertical-align: top;
	margin-bottom: 1.5em;
	margin-top: 1em;
	background-color: white;
	border: 1px dotted gray;
	padding: 0.25em;
}
.device div.left, .device div.right {
	max-width: 485px;
	margin-bottom: 1.5em;
	display: inline-block;
	vertical-align: top;
	text-align: left;
}
.device .table {width: 100%;}
.device .table.style > div:nth-child(2n+1) > div {border-top: 1px solid grey;vertical-align: top;}
.device .table.style > div:nth-child(2n+1) > div:first-child {background-color: lightGray;border-left: 1px solid grey;}
.device .table > div > div {min-width: 100px;}
.device .caption {margin-top: 2em;}
.device .table .table .table, .right .table + .table {background-color: white;width: 100%;}
.device .table .table .table > div > div {padding: 3px;}
.right .table + .table {margin-top: 1em;}

.device .table.patchpanel div[id^="pp"] { border-left: 2px solid black; min-width: 10px;}
.device .table.patchpanel > div:first-child div[id^="pp"],
.device .table.patchpanel > div:first-child div[id^="mt"] {border-top: 1px solid black; }
.device .table.patchpanel > div:last-child div[id^="pp"],
.device .table.patchpanel > div:last-child div[id^="mt"] {border-bottom: 1px solid black; }
.device .table.patchpanel div[id^="pp"]:NOT([id="pp"]) { cursor: pointer; text-decoration: underline; }
.device .table.patchpanel div[id^="mt"] { border-right: 2px solid black; }
.device .table.patchpanel div[id^="pp"],
.device .table.patchpanel div[id^="mt"] { background-color: rgba(211, 211, 211, 0.5);}
.device .table.patchpanel > div > div {min-width: auto;}
.device .table.patchpanel > div:first-child select, .device .table.switch > div:first-child select { position: absolute; background-color: transparent; border: 0px none; width: auto;}
.device .table.patchpanel > div:first-child select::-moz-focus-inner, 
.device .table.patchpanel > div:first-child select:focus::-moz-focus-inner, 
.device .table.switch > div:first-child select::-moz-focus-inner, 
.device .table.switch > div:first-child select:focus::-moz-focus-inner {border: none;}

#pandn.table .custom-combobox input, #pandn.table .custom-combobox a {border-top: 2px; border-bottom: 2px; border-style: inset;  width: auto; height: 18px;}
#pandn.table .custom-combobox input {background-image: none; border-left: 2px; border-right: 0px; padding-left: 4px; font-size: inherit;} 
#pandn.table .custom-combobox a {margin: 0; vertical-align: top; width: 18px; border-left: 0px; border-right: 2px; position: absolute; top: 0; right: 0;} 
.device #tags { width: 95%; min-width: 250px;}

#firstport.hide { display: none; }

.switch.table > div > div { min-width: 0px; }
.switch.table > div > div:first-child { min-width: 15px; }
/* can't explain where the 2px is coming from */
.switch.table input, .patchpanel.table input { height: 18px; }
.switch.table input, .switch.table select, 
.patchpanel.table input, .patchpanel.table select { width: 99%; padding: 0; background-color: transparent;}
.switch.table div[id^=n] input { width:98%; }

.switch .status { background-image: url("../images/portstatus.png");}
.switch .down { background-position: left; }
.switch .up { background-position: right; }

.chassis .table input{text-align:center;}
.chassis .table > div > div{text-align:center;}
.chassis .table + .table > div > div{text-align:left;}
.chassis .table > div:first-child > div, .chassis label{font-weight:bold;padding-bottom:0.5em;}
.chassis .table + .table > div > div{min-width:0px;padding-right:0.75em;padding-bottom:0.25em;}
.chassis .table + .table > div > div:first-child, .chassis .table + .table > div > div:nth-child(2){text-align: center;}

.positionselector {font-size: .7em; background-color: white;}
.positionselector > div > div > div {width: 1em; height: 1em; padding-left: .5em; padding-right: .5em; text-align: right;}
.positionselector > div > div + div > div {width: 3em; padding-right: 1em; padding-right: 1em;}
.notavail {background-color: black; border-color: black !important;}
/* borders were too thick looking */
.positionselector > div > div > div{ border-top: 0px; border-left: 0px;}
.positionselector > div > div + div > div{ border-top: 0px; border-right: 0px;}
.positionselector > div { border-width: 1px;}
.positionselector, .positionselector > div > div {border-width: 0px;}
#positionselector .positionselector > div > div {min-width: 0;}
#positionselector {padding: 10px; position: absolute; left: -1000px; background-color: white; border: 1px solid black; z-index: 99;}

#editbtn { display: block; margin-bottom: 5px;}
#preview { width: 340px; min-height: 130px; background-color: white; border: 1px solid grey; padding: 5px;}
#preview img { display: block; border: 0px; max-width: 330px;}
.jHtmlArea iframe { background-color: white; border: 1px inset; min-height: 100px;}


/* Button code primarily from http://somadesign.ca */
/* Button */
.button, input[type=button], input[type=submit], button {
	text-decoration: none;
	border-color:#888;
	border-color:rgba(0, 0, 0, 0.56);
	cursor: pointer;
	outline: none;
	color:#111;
	display:inline-block;
	vertical-align:top;
	position:relative;
	font-size:12px;
	text-align:center;
	background-color:#aaa;
	background-image:url(gradient.png);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.75), rgba(255,255,255,0));
	background-image: -o-linear-gradient(top, rgba(255,255,255,.75), rgba(255,255,255,0));
	background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(rgba(255,255,255,.75)), to(rgba(255,255,255,0)));
	background-image: linear-gradient(top, rgba(255,255,255,.75), rgba(255,255,255,0));
	background-repeat:repeat-x;
	text-shadow:1px 1px 0 rgba(255,255,255,.67); 
	line-height:2;
	height:2em;
	-moz-box-shadow:1px 1px 0 rgba(255,255,255,.5) inset, -1px -1px 0 rgba(255,255,255,.5) inset;
	-webkit-box-shadow:1px 1px 0 rgba(255,255,255,.5) inset, -1px -1px 0 rgba(255,255,255,.5) inset;
	box-shadow:1px 1px 0 rgba(255,255,255,.5) inset, -1px -1px 0 rgba(255,255,255,.5) inset;
	-webkit-transition: background .185s linear;
	-moz-transition: all .185s linear;
	-o-transition: all .185s linear;
	transition: all .185s linear;
	/** Make the text unselectable **/
	-moz-user-select: none;
	-webkit-user-select: none;
}
.button, .button:after, button, button:after, input[type=submit], input[type=button], ul.nav li {
	-moz-border-radius:4px;
	-webkit-border-radius:4px;
	border-radius:4px;
	border-width:1px;
	border-style:solid;
}
.button:after, button:after {
	display:block;
	position:absolute;
	width:100%;
	height:100%;
	border-color: transparent transparent #ccc;
	border-color: transparent transparent rgba(255, 255, 255, 0.67);
	bottom:-2px;
	left:-1px;
}
.button:hover, .button:focus, button:hover, button:focus, input[type=button]:hover, input[type=button]:focus, input[type=submit]:hover, input[type=submit]:focus {
	background-color:#a8c0cb;
}
.button:active, button:active, input[type=submit]:active, input[type=button]:active {
	line-height:2.2;
	-moz-box-shadow:0 .33em 1em rgba(0,0,0,.67) inset,1px 1px 0 rgba(255,255,255,.25) inset,-1px -1px 0 rgba(255,255,255,.25) inset;
	-webkit-box-shadow:0 .33em 2em rgba(0,0,0,.67) inset,1px 1px 0 rgba(255,255,255,.25) inset,-1px -1px 0 rgba(255,255,255,.25) inset;
	box-shadow:0 .33em 2em rgba(0,0,0,.67) inset,1px 1px 0 rgba(255,255,255,.25) inset,-1px -1px 0 rgba(255,255,255,.25) inset;
	-webkit-transition: line-height .1s linear;
	-moz-transition: all .1s linear;
	-o-transition: all .1s linear;
	transition: all .1s linear;
}
.button.bg, .button.bg:hover, .button.bg:focus, ul.nav li {
	background-image:url(gradient.png);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.75), rgba(255,255,255,0));
	background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(rgba(255,255,255,.75)), to(rgba(255,255,255,0)));
}

/* Put this inside a @media qualifier so Netscape 4 ignores it */
@media screen, print { 
	/* Set printouts to landscape */
	@page {size: landscape}

	/* Turn off list bullets */
	ul.mktree  li { list-style: none; } 
	/* Control how "spaced out" the tree is */
	ul.mktree, ul.mktree ul , ul.mktree li { margin-left:5px; padding:0px; }
	/* Provide space for our own "bullet" inside the LI */
	ul.mktree  li           .bullet { padding-left: 15px; }
	/* Show "bullets" in the links, depending on the class of the LI that the link's in */
	ul.mktree  li.liOpen    .bullet { cursor: pointer; background: url(minus.gif)  center left no-repeat; }
	ul.mktree  li.liClosed  .bullet { cursor: pointer; background: url(plus.gif)   center left no-repeat; }
	ul.mktree  li.liBullet  .bullet { cursor: default; background: url(bullet.gif) center left no-repeat; }
	/* Sublists are visible or not based on class of parent LI */
	ul.mktree  li.liOpen    ul { display: block; }
	ul.mktree  li.liClosed  ul { display: none; }
	/* Format menu items differently depending on what level of the tree they are in */
	ul.mktree  li { font-family: arial, helvetica; font-size: 11pt; font-weight: bold; }
	ul.mktree  a.DC { color: #000088; font-weight: bold; }
	ul.mktree  a.CONTAINER { color: #005500; }
	ul.mktree  a.ZONE { color: #330066; }
	ul.mktree  a.CABROW { color: #AA3300; }
	ul.mktree  a.RACK { color: #660000; }
	ul.mktree  a { text-decoration: none; }
	ul.mktree  a:hover { color: red; }
	ul.mktree  li ul li { font-family: arial, helvetica; font-size: 11pt; font-weight: normal;}
}
.meter-wrap{position: relative;background-color: lightgrey;}
.meter-wrap, .meter-value, .meter-text {width: 155px; height: 1em;}
.meter-text {
    position: absolute;
    top:0; left:0;
    padding-top: 0px;
    color: #000;
    text-align: center;
    width: 100%;
}
/* Supplies */
.supply .table > div:first-child > div {padding-bottom:0.5em;font-weight: bold;}
.supply .table > div > div {padding-right: 0.25em;}
.supply .table > div > div:first-child {width: 22px;}
.supply .table .quantity {text-align: center;}
.supply .table { margin-bottom: 2em; width: 100%;}
.supply .table select { width: 100%; }
.supply .table:first-child { margin-left: 25px; width: auto;}
.supply .table:first-child > div > div:first-child {width: auto;}
.supply #location {width: 97%;}


/* Installer */
.installer ul li, ul.nav li{
	display: block;
	padding: 1.5em;
	background-color: lightGray;
	border: 0px solid lightGray;
}
.installer ul li{border: 1px dashed darkGray;}
.installer #sidebar a, .nav a {text-decoration: none;}
.installer #sidebar a:hover li.active, .nav a:hover li.active {background-color: white;border-color: lightGray;}
.installer .active, .nav .active {background-color: white;border: 1px solid darkGray;}
.installer a.active span:first-child, .nav a.active span:first-child {background-position: -144px 0;}
.installer div.table > div > div + div {width: 300px;}
.installer .rights > div:nth-last-child(2) div {padding-top: 0;padding-bottom: 2em;text-align: left;}
.installer .center input {width: 97%;}
.installer #configtabs div.table > div > div + div {width: auto;}
.installer .center #configtabs ~ div input {width: auto;}
div.page.installer {min-width: 1100px;}
div.page.installer .main{max-width: 850px;}

.installer .ui-multiselect ul li { padding: 0.5em 0.5em 0.5em 20px; height: auto; line-height: inherit;}


/* Menu */
ul.nav li {padding: .5em;}
.nav a:visited {color: #000000;}
#sidebar .nav li a { border: 1px solid lightGray; margin: 0;}
#sidebar .nav .ui-state-focus {
	background: white;
	border-color: black;
	border-width: 1px;
	border-style: solid;
	margin: 0;
}

/* Search Export */
div.center div table#export { margin: auto; max-width: none; }

/* Paths */
/* Paths form */
fieldset.crit_busc {border: 1px solid grey; padding:0.5em; background-color: #EEEEEE;}
fieldset.crit_busc legend {background-color: white; padding:0.5em; border: 1px solid grey;}
table#crit_busc {border: 0px; background: transparent; padding:0.5em;}
table#crit_busc tr {border: 0px; background: transparent; padding:0.5em;}
table#crit_busc td {border: 0px; background: transparent; padding:0.5em;}

table#parcheos {border: 3px outset; text-align: center; text-valign: center; max-width: 800px; margin-left: auto; margin-right: auto;}
table#parcheos tr {border: 0px;}	
table#parcheos td {padding: 0px; border: 0px; vertical-align: top;}

#parcheos .f-right {background: url("../images/a2f.png") no-repeat #FFF; width:25px;}
#parcheos .f-left {background: url("../images/a1f.png") no-repeat #FFF; width:25px;}
#parcheos .r-right {background: url("../images/a2r.png") no-repeat #FFF; width:25px;}
#parcheos .r-left {background: url("../images/a1r.png") no-repeat #FFF; width:25px;}

#parcheos .base-f, #parcheos .base-r {background: url("../images/b0f.png") no-repeat top left #FFF; height: 5px; padding: 0px; border: 0px;}

#parcheos .connection-f-1 {background: url("../images/b1f.png") no-repeat #FFF;}
#parcheos .connection-f-2 {background: url("../images/b2f.png") no-repeat #FFF; width:25px;}
#parcheos .connection-f-3 {background: url("../images/b3f.png") no-repeat #FFF; height:30px;}
#parcheos .connection-f-4 {background: url("../images/b4f.png") no-repeat top right #FFF; height:30px;}
#parcheos .connection-r-1 {background: url("../images/b1r.png") no-repeat #FFF;}
#parcheos .connection-r-2 {background: url("../images/b2r.png") no-repeat #FFF; width:25px;}
#parcheos .connection-r-3 {background: url("../images/b3r.png") no-repeat #FFF; height:30px;}
#parcheos .connection-r-4 {background: url("../images/b4r.png") no-repeat top right #FFF; height:30px;}

table#parcheos table tr + tr > td + td{background-color:yellow;}
table#parcheos table {margin: 0px; border: 0px; border-collapse: collapse; text-align: left; vertical-align: middle; min-width: 50px; white-space: nowrap;}
table#parcheos table tr th {background-color: #DDDDDD; padding: 2px; border: 1px solid grey; text-align: left; border-collapse: collapse;}
table#parcheos table tr td {padding: 2px; border: 1px solid grey; text-align: left; border-collapse: collapse;}
table#parcheos tr td:first-child + td table {margin-left: auto;}

p.errormsg {padding: 20px; background-color: #DDDDDD; font-size: 120%; font-weight: bold; color: red;}

