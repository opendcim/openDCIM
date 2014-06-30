<?php
    require_once "db.inc.php";
    require_once "facilities.inc.php";
    # if format is set, graph options should be set and ready to be rendered
    if(isset($_REQUEST['format'])) {
        # find the directory that dcim is being hosted out of (used when we build
        # the url for each node
        $baseURI = dirname($_SERVER['REQUEST_URI']);
        $graphname = "Network Map for ";
        $graphstr = "";
        $devList=array();
        # a *long* list of colors recognized by graphviz. could be pruned some.
        $colorList = array(
            "aliceblue", "antiquewhite", "antiquewhite1", "antiquewhite2",
            "antiquewhite3", "antiquewhite4", "aqua", "aquamarine",
            "aquamarine1", "aquamarine2", "aquamarine3", "aquamarine4",
            "azure", "azure1", "azure2", "azure3", "azure4", "beige", "bisque",
            "bisque1", "bisque2", "bisque3", "bisque4", "black",
            "blanchedalmond", "blue", "blue1", "blue2", "blue3", "blue4",
            "blueviolet", "brown", "brown1", "brown2", "brown3", "brown4",
            "burlywood", "burlywood1", "burlywood2", "burlywood3", "burlywood4",
            "cadetblue", "cadetblue1", "cadetblue2", "cadetblue3", "cadetblue4",
            "chartreuse", "chartreuse1", "chartreuse2", "chartreuse3",
            "chartreuse4", "chocolate", "chocolate1", "chocolate2",
            "chocolate3", "chocolate4", "coral", "coral1", "coral2", "coral3",
            "coral4", "cornflowerblue", "cornsilk", "cornsilk1", "cornsilk2",
            "cornsilk3", "cornsilk4", "crimson", "cyan", "cyan1", "cyan2",
            "cyan3", "cyan4", "darkblue", "darkcyan", "darkgoldenrod",
            "darkgoldenrod1", "darkgoldenrod2", "darkgoldenrod3",
            "darkgoldenrod4", "darkgray", "darkgreen", "darkgrey", "darkkhaki",
            "darkmagenta", "darkolivegreen", "darkolivegreen1",
            "darkolivegreen2", "darkolivegreen3", "darkolivegreen4",
            "darkorange", "darkorange1", "darkorange2", "darkorange3",
            "darkorange4", "darkorchid", "darkorchid1", "darkorchid2",
            "darkorchid3", "darkorchid4", "darkred", "darksalmon",
            "darkseagreen", "darkseagreen1", "darkseagreen2", "darkseagreen3",
            "darkseagreen4", "darkslateblue", "darkslategray",
            "darkslategray1", "darkslategray2", "darkslategray3",
            "darkslategray4", "darkslategrey", "darkturquoise", "darkviolet",
            "deeppink", "deeppink1", "deeppink2", "deeppink3", "deeppink4",
            "deepskyblue", "deepskyblue1", "deepskyblue2", "deepskyblue3",
            "deepskyblue4", "dimgray", "dimgrey", "dodgerblue", "dodgerblue1",
            "dodgerblue2", "dodgerblue3", "dodgerblue4", "firebrick",
            "firebrick1", "firebrick2", "firebrick3", "firebrick4",
            "floralwhite", "forestgreen", "fuchsia", "gainsboro", "ghostwhite",
            "gold", "gold1", "gold2", "gold3", "gold4", "goldenrod",
            "goldenrod1", "goldenrod2", "goldenrod3", "goldenrod4", "gray",
            "gray0", "gray1", "gray10", "gray100", "gray11", "gray12", "gray13",
            "gray14", "gray15", "gray16", "gray17", "gray18", "gray19", "gray2",
            "gray20", "gray21", "gray22", "gray23", "gray24", "gray25",
            "gray26", "gray27", "gray28", "gray29", "gray3", "gray30", "gray31",
            "gray32", "gray33", "gray34", "gray35", "gray36", "gray37",
            "gray38", "gray39", "gray4", "gray40", "gray41", "gray42", "gray43",
            "gray44", "gray45", "gray46", "gray47", "gray48", "gray49", "gray5",
            "gray50", "gray51", "gray52", "gray53", "gray54", "gray55",
            "gray56", "gray57", "gray58", "gray59", "gray6", "gray60", "gray61",
            "gray62", "gray63", "gray64", "gray65", "gray66", "gray67",
            "gray68", "gray69", "gray7", "gray70", "gray71", "gray72", "gray73",
            "gray74", "gray75", "gray76", "gray77", "gray78", "gray79", "gray8",
            "gray80", "gray81", "gray82", "gray83", "gray84", "gray85",
            "gray86", "gray87", "gray88", "gray89", "gray9", "gray90", "gray91",
            "gray92", "gray93", "gray94", "gray95", "gray96", "gray97",
            "gray98", "gray99", "green", "green1", "green2", "green3", "green4",
            "greenyellow", "grey", "grey0", "grey1", "grey10", "grey100",
            "grey11", "grey12", "grey13", "grey14", "grey15", "grey16",
            "grey17", "grey18", "grey19", "grey2", "grey20", "grey21", "grey22",
            "grey23", "grey24", "grey25", "grey26", "grey27", "grey28",
            "grey29", "grey3", "grey30", "grey31", "grey32", "grey33", "grey34",
            "grey35", "grey36", "grey37", "grey38", "grey39", "grey4", "grey40",
            "grey41", "grey42", "grey43", "grey44", "grey45", "grey46",
            "grey47", "grey48", "grey49", "grey5", "grey50", "grey51", "grey52",
            "grey53", "grey54", "grey55", "grey56", "grey57", "grey58",
            "grey59", "grey6", "grey60", "grey61", "grey62", "grey63", "grey64",
            "grey65", "grey66", "grey67", "grey68", "grey69", "grey7", "grey70",
            "grey71", "grey72", "grey73", "grey74", "grey75", "grey76",
            "grey77", "grey78", "grey79", "grey8", "grey80", "grey81", "grey82",
            "grey83", "grey84", "grey85", "grey86", "grey87", "grey88",
            "grey89", "grey9", "grey90", "grey91", "grey92", "grey93", "grey94",
            "grey95", "grey96", "grey97", "grey98", "grey99", "honeydew",
            "honeydew1", "honeydew2", "honeydew3", "honeydew4", "hotpink",
            "hotpink1", "hotpink2", "hotpink3", "hotpink4", "indianred",
            "indianred1", "indianred2", "indianred3", "indianred4", "indigo",
            "invis", "ivory", "ivory1", "ivory2", "ivory3", "ivory4", "khaki",
            "khaki1", "khaki2", "khaki3", "khaki4", "lavender", "lavenderblush",
            "lavenderblush1", "lavenderblush2", "lavenderblush3",
            "lavenderblush4", "lawngreen", "lemonchiffon", "lemonchiffon1",
            "lemonchiffon2", "lemonchiffon3", "lemonchiffon4", "lightblue",
            "lightblue1", "lightblue2", "lightblue3", "lightblue4", "lightcoral",
            "lightcyan", "lightcyan1", "lightcyan2", "lightcyan3", "lightcyan4",
            "lightgoldenrod", "lightgoldenrod1", "lightgoldenrod2",
            "lightgoldenrod3", "lightgoldenrod4", "lightgoldenrodyellow",
            "lightgray", "lightgreen", "lightgrey", "lightpink", "lightpink1",
            "lightpink2", "lightpink3", "lightpink4", "lightsalmon",
            "lightsalmon1", "lightsalmon2", "lightsalmon3", "lightsalmon4",
            "lightseagreen", "lightskyblue", "lightskyblue1", "lightskyblue2",
            "lightskyblue3", "lightskyblue4", "lightslateblue",
            "lightslategray", "lightslategrey", "lightsteelblue",
            "lightsteelblue1", "lightsteelblue2", "lightsteelblue3",
            "lightsteelblue4", "lightyellow", "lightyellow1", "lightyellow2",
            "lightyellow3", "lightyellow4", "lime", "limegreen", "linen",
            "magenta", "magenta1", "magenta2", "magenta3", "magenta4", "maroon",
            "maroon1", "maroon2", "maroon3", "maroon4", "mediumaquamarine",
            "mediumblue", "mediumorchid", "mediumorchid1", "mediumorchid2",
            "mediumorchid3", "mediumorchid4", "mediumpurple", "mediumpurple1",
            "mediumpurple2", "mediumpurple3", "mediumpurple4", "mediumseagreen",
            "mediumslateblue", "mediumspringgreen", "mediumturquoise",
            "mediumvioletred", "midnightblue", "mintcream", "mistyrose",
            "mistyrose1", "mistyrose2", "mistyrose3", "mistyrose4", "moccasin",
            "navajowhite", "navajowhite1", "navajowhite2", "navajowhite3",
            "navajowhite4", "navy", "navyblue", "oldlace", "olive", "olivedrab",
            "olivedrab1", "olivedrab2", "olivedrab3", "olivedrab4", "orange",
            "orange1", "orange2", "orange3", "orange4", "orangered",
            "orangered1", "orangered2", "orangered3", "orangered4", "orchid",
            "orchid1", "orchid2", "orchid3", "orchid4", "palegoldenrod",
            "palegreen", "palegreen1", "palegreen2", "palegreen3", "palegreen4",
            "paleturquoise", "paleturquoise1", "paleturquoise2",
            "paleturquoise3", "paleturquoise4", "palevioletred",
            "palevioletred1", "palevioletred2", "palevioletred3",
            "palevioletred4", "papayawhip", "peachpuff", "peachpuff1",
            "peachpuff2", "peachpuff3", "peachpuff4", "peru", "pink", "pink1",
            "pink2", "pink3", "pink4", "plum", "plum1", "plum2", "plum3",
            "plum4", "powderblue", "purple", "purple1", "purple2", "purple3",
            "purple4", "red", "red1", "red2", "red3", "red4", "rosybrown",
            "rosybrown1", "rosybrown2", "rosybrown3", "rosybrown4", "royalblue",
            "royalblue1", "royalblue2", "royalblue3", "royalblue4",
            "saddlebrown", "salmon", "salmon1", "salmon2", "salmon3", "salmon4",
            "sandybrown", "seagreen", "seagreen1", "seagreen2", "seagreen3",
            "seagreen4", "seashell", "seashell1", "seashell2", "seashell3",
            "seashell4", "sienna", "sienna1", "sienna2", "sienna3", "sienna4",
            "silver", "skyblue", "skyblue1", "skyblue2", "skyblue3", "skyblue4",
            "slateblue", "slateblue1", "slateblue2", "slateblue3", "slateblue4",
            "slategray", "slategray1", "slategray2", "slategray3", "slategray4",
            "slategrey", "snow", "snow1", "snow2", "snow3", "snow4",
            "springgreen", "springgreen1", "springgreen2", "springgreen3",
            "springgreen4", "steelblue", "steelblue1", "steelblue2",
            "steelblue3", "steelblue4", "tan", "tan1", "tan2", "tan3", "tan4",
            "teal", "thistle", "thistle1", "thistle2", "thistle3", "thistle4",
            "tomato", "tomato1", "tomato2", "tomato3", "tomato4", "turquoise",
            "turquoise1", "turquoise2", "turquoise3", "turquoise4", "violet",
            "violetred", "violetred1", "violetred2", "violetred3", "violetred4",
            "wheat", "wheat1", "wheat2", "wheat3", "wheat4", "white",
            "whitesmoke", "yellow", "yellow1", "yellow2", "yellow3", "yellow4",
            "yellowgreen"
        );
        $safeDeviceColors = array(
            "cadetblue2", "deepskyblue4", "palegreen", "forestgreen",
            "lightpink", "red", "navajowhite", "darkorange", "plum", "purple",
            "khaki", "sienna", "black"
        );
        $deviceTypes = array(
                'Server','Appliance','Storage Array','Switch','Chassis',
                'Patch Panel','Physical Infrastructure'
        );
        # handle the request variables and build the device lists.
        if(isset($_REQUEST['containerid'])){
            $containerid=isset($_POST['containerid'])?$_POST['containerid']:$_GET['containerid'];
            $container = new Container();
            $container->ContainerID = $containerid;
            $container->GetContainer();
            $graphname .= "Container " . $container->Name;
            $dcList=$container->GetChildDCList();
            foreach($dcList as $dc) {
                $cabinet = new Cabinet();
                $cabinet->DataCenterID = $dc->DataCenterID;
                foreach($cabinet->ListCabinetsByDC(false, false) as $cab){
                    $device = new Device();
                    $device->Cabinet = $cab->CabinetID;
                    foreach($device->ViewDevicesByCabinet(true) as $dev) {
                        $devList[$dev->DeviceID]=$dev;
                    }
                }
            }
        } elseif(isset($_REQUEST['datacenterid'])){
            $dcid=isset($_POST['datacenterid'])?$_POST['datacenterid']:$_GET['datacenterid'];
            $datacenter = new DataCenter();
            $datacenter->DataCenterID = $dcid;
            $datacenter->GetDataCenter();
            $graphname .= "Data Center ".$datacenter->Name;
            $cabinet = new Cabinet();
            $cabinet->DataCenterID = $dcid;
            foreach($cabinet->ListCabinetsByDC(false, false) as $cab) {
                $device = new Device();
                $device->Cabinet = $cab->CabinetID;
                foreach($device->ViewDevicesByCabinet(true) as $dev) {
                    $devList[$dev->DeviceID]=$dev;
                }
            }
        } elseif(isset($_REQUEST['zoneid'])){
            $zoneid=isset($_POST['zoneid'])?$_POST['zoneid']:$_GET['zoneid'];
            $zone = new Zone();
            $zone->ZoneID = $zoneid;
            $zone->GetZone();
            $datacenter = new DataCenter();
            $datacenter->DataCenterID = $zone->DataCenterID;
            $datacenter->GetDataCenter();
            $graphname .= "Zone ".$zone->Description
                    . " in Data Center " . $datacenter->Name;
            $cabinet = new Cabinet();
            $cabinet->DataCenterID = $zone->DataCenterID;
            $cabinet->ZoneID = $zone->ZoneID;
            foreach($cabinet->ListCabinetsByDC(true, true) as $cab) {
                $device = new Device();
                $device->Cabinet = $cab->CabinetID;
                foreach($device->ViewDevicesByCabinet(true) as $dev) {
                    $devList[$dev->DeviceID]=$dev;
                }
            }
        } elseif(isset($_REQUEST['cabrowid'])){
            $cabrowid=isset($_POST['cabrowid'])?$_POST['cabrowid']:$_GET['cabrowid'];
            $cabrow = new CabRow();
            $cabrow->CabRowID = $cabrowid;
            $cabrow->GetCabRow();
            $cabinet = new Cabinet();
            $cabinet->CabRowID = $cabrow->CabRowID;
            $cabinetList = $cabinet->GetCabinetsByRow();
            if(isset($cabinetList)) {
                $datacenter = new DataCenter();
                $datacenter->DataCenterID = $cabinetList[0]->DataCenterID;
                $datacenter->GetDataCenter();
                $graphname .= "Row " . $cabrow->Name 
                        . " in Data Center " . $datacenter->Name;
                foreach($cabinetList as $cab) {
                    $device = new Device();
                    $device->Cabinet = $cab->CabinetID;
                    foreach($device->ViewDevicesByCabinet(true) as $dev) {
                        $devList[$dev->DeviceID]=$dev;
                    }
                }
            }
        } elseif(isset($_REQUEST['cabid'])){
            $cabid=isset($_POST['cabid'])?$_POST['cabid']:$_GET['cabid'];
            $cabinet = new Cabinet();
            $cabinet->CabinetID = $cabid;
            $cabinet->GetCabinet();
            $datacenter = new DataCenter();
            $datacenter->DataCenterID = $cabinet->DataCenterID;
            $datacenter->GetDataCenter();
            $graphname .= "Cabinet " . $cabinet->Location 
                    . " in Data Center " . $datacenter->Name;
            $device = new Device();
            $device->Cabinet = $cabid;
            foreach($device->ViewDevicesByCabinet(true) as $dev) {
                $devList[$dev->DeviceID]=$dev;
            }

        }
        # start building the graphfile.
        $graphstr .= "graph openDCIM {

label = \"".$graphname."\";
rankdir = LR;
fontsize = 30;
labelloc = t;
overlap = scale;

\tedge [ color=\"#0000a0\" ];
\tnode [ shape=box, headport=n, tailport=n ];

";

        # Generate a list of ports from the device lists.
        $portList=array();
        foreach($devList as $devid => $dev) {
            $ports=DevicePorts::getPortList($dev->DeviceID);
            foreach($ports as $port) {
                if(isset($port->ConnectedDeviceID)) {
                    $portList[]=$port;
                    # if the connected device isn't in out list of devices, add it so we 
                    # at least get nice names for the devices outside the selected scope
                    if(!isset($devList[$port->ConnectedDeviceID])) {
                        $tdev = new Device();
                        $tdev->DeviceID = $port->ConnectedDeviceID;
                        $tdev->GetDevice();
                        $devList[$port->ConnectedDeviceID] = $tdev;
                    }
                }
            }
        }
        # create a lookup table for colors on the fly. This helps make sure that
        # the random colors we select (for colors we can't match) stay consistent
        $myCableColorList = array();
        # keep a list of nodes we've added to the graph already. multiples
        # *shouldn't* cause issues with graphviz, but the final dot will be
        # smaller
        $nodessent = array();
        # build the following datastructure:
        # array[devicepair][portpair][deviceid] = label
        # and add all the devices as nodes on the graph
        $devportmapping = array();
        foreach($portList as $port){
            # sorted device pair to be used as a key. this keeps connections
            # between two hosts together and lets us match them up better later
            $tkeypair = array($port->DeviceID, $port->ConnectedDeviceID);
            # add the device to the dotfile, if it wasn't sent already
            if(!isset($nodessent[$tkeypair[0]])) {
                $dt = $devList[$tkeypair[0]]->DeviceType;
                if(in_array($dt, $deviceTypes)){
                        $color = $safeDeviceColors[array_search($dt, $deviceTypes)];
                }else{
                        $color = $safeDeviceColors[array_rand($safeDeviceColors)];
                }
                $graphstr .= "\t".$tkeypair[0]." [shape=box,URL=\"".$baseURI
                        .'/devices.php?deviceid='.$tkeypair[0]."\",label=\""
                        .$devList[$tkeypair[0]]->Label."\",color=".$color."];\n";
                $nodessent[$tkeypair[0]] = true;
            }
            if(!isset($nodessent[$tkeypair[1]])) {
                $dt = $devList[$tkeypair[1]]->DeviceType;
                if(in_array($dt, $deviceTypes)){
                        $color = $safeDeviceColors[array_search($dt, $deviceTypes)];
                }else{
                        $color = $safeDeviceColors[array_rand($safeDeviceColors)];
                }
                $graphstr .= "\t".$tkeypair[1]." [shape=box,URL=\"".$baseURI
                        .'/devices.php?deviceid='.$tkeypair[1]."\",label=\""
                        .$devList[$tkeypair[1]]->Label."\",color=".$color."];\n";
                $nodessent[$tkeypair[1]] = true;
            }
            sort($tkeypair);
            $tkey = $tkeypair[0].":".$tkeypair[1];
            if(!isset($devportmapping[$tkey])){
                $devportmapping[$tkey] = array();
            }
            # create a port key, 1st device gets first port number.
            # allows for matching up port numbers.
            if($port->DeviceID == $tkeypair[0]) {
                $pkey = $port->PortNumber.":".$port->ConnectedPort;
            } else {
                $pkey = $port->ConnectedPort.":".$port->PortNumber;
            }
            if(!isset($devportmapping[$tkey][$pkey])) {
               $devportmapping[$tkey][$pkey] = array();
            }
            # grab the current devices label for the port, allows cleaner labeling of connections
            $devportmapping[$tkey][$pkey][$port->DeviceID] = $port->Label;
            # set the cable color. tries to use the color from the db, if we can identify it.
            # otherwise uses a random color from the list above.
            if(!isset($myCableColorList[$port->ColorID])) {
                $cc = new ColorCoding();
                $cc->ColorID = $port->ColorID;
                $cc->GetCode();
                $color = strtolower($cc->Name);
                if(!in_array($color, $colorList)) {
                    $color = $colorList[array_rand($colorList)];
                }
            } else {
                $color = $myCableColorList[$port->ColorID];
            }
            $myCableColorList[$port->ColorID] = $color;
            $devportmapping[$tkey][$pkey]['color'] = $color;
            # store the media id. we only have 3 "styles" of cables we can use
            # we'll use this later to decide the style we should use. most useful
            # with media enforcement, otherwise the style might fluctuate between views
            $devportmapping[$tkey][$pkey]['mediaid'] = $port->MediaID;
        }
        foreach ($devportmapping as $tkey => $value){
            $tkeypair = explode(":", $tkey, 2);
            foreach($value as $pkey => $devid) {
                    $pkeys = explode(":", $pkey);
                    # choose the style of connection. just went with a modulus to keep things simple.
                    $styleList = array('bold', 'dashed', 'solid');
                    $style = ",style=".$styleList[$devid['mediaid']%3];
                    # if we can't find a label for either side, the side without is considered outside scope
                    # or outside the filter specified within your view.
                    if(!isset($devid[$tkeypair[0]])) {
                        $devid[$tkeypair[0]] = "outside scope";
                        $style = ",style=dotted";
                    }
                    if(!isset($devid[$tkeypair[1]])) {
                        $devid[$tkeypair[1]] = "outside scope";
                        $style = ",style=dotted";
                    }
                    # add the connection to the dotfile.
                    $graphstr .= "\t".$tkeypair[0]." -- ".$tkeypair[1]
                            ." [label=\"".$devid[$tkeypair[0]]."--"
                            .$devid[$tkeypair[1]]."\",color=".$devid['color']
                            .$style."];\n";
            }
        }
        $graphstr .= "}";

        # safe format types. newer versions of graphviz also support pdf. maybe
        # we should add it when the ability is more prevalent.
        $formatTypes = array(
                'gif', 'jpg', 'png', 'svg', 'dot'
        );
        if(!in_array($_REQUEST['format'], $formatTypes)) {
            exit;
        }

        $dotfile = tempnam('/tmp/', 'graph_');
        file_put_contents($dotfile, $graphstr);
        if($_REQUEST['format'] == 'svg') {
            header("Content-Type: image/svg+xml");
            passthru("dot -Tsvg -o/dev/stdout ".$dotfile, $retval);
        } elseif($_REQUEST['format'] == 'png') {
            header("Content-Type: image/png");
            passthru("dot -Tpng -o/dev/stdout ".$dotfile, $retval);
        } elseif($_REQUEST['format'] == 'jpg') {
            header("Content-Type: image/jpeg");
            passthru("dot -Tjpg -o/dev/stdout ".$dotfile, $retval);
        } elseif($_REQUEST['format'] == 'gif') {
            header("Content-Type: image/gif");
            passthru("dot -Tgif -o/dev/stdout ".$dotfile, $retval);
        } elseif($_REQUEST['format'] == 'dot') {
            header("Content-Type: text/plain");
            echo $graphstr;
        }
        unlink($dotfile);
        exit;
    } else {
        $body="";
        if(isset($_REQUEST['containmenttype'])) {
            # start filtering
            $containmentType = isset($_POST['containmenttype'])?$_POST['containmenttype']:$_GET['containmenttype'];
            if($containmentType != "") {
                $body = "<form method='post'>";
                if($containmentType == 0){
                    # filtering by container
                    $tstr = "container";
                    $cList = new Container();    
                    $cList = $cList->GetContainerList();
                    $body .= "<select name=containerid id=containerid>";
                    foreach($cList as $c){
                        $body .= "<option value=".$c->ContainerID.">".$c->Name."</option>";
                    }
                } elseif($containmentType == 1){
                    # filtering by dc
                    $tstr = "datacenter";
                    $dcList = new DataCenter();    
                    $dcList = $dcList->GetDCList();
                    $body .= "<select name=datacenterid id=datacenterid>";
                    foreach($dcList as $dc){
                        $body .= "<option value=".$dc->DataCenterID.">".$dc->Name."</option>";
                    }
                } elseif($containmentType == 2){
                    #filtering by zone (perhaps this should also mention dc it is in)
                    $tstr = "zone";
                    $zList = new Zone();    
                    $zList = $zList->GetZoneList();
                    $body .= "<select name=zoneid id=zoneid>";
                    foreach($zList as $zone){
                        $body .= "<option value=".$zone->ZoneID.">".$zone->Description."</option>";
                    }
                } elseif($containmentType == 3){
                    # filter by cabrow
                    $tstr = "cabrow";
                    $crList = new CabRow();    
                    $crList = $crList->GetCabRowList();
                    $body .= "<select name=cabrowid id=cabrowid>";
                    foreach($crList as $cabrow){
                        $body .= "<option value=".$cabrow->CabRowID.">".$cabrow->Name."</option>";
                    }
                } elseif($containmentType == 4){
                    # filter by cabinet
                    $tstr = "cab";
                    $cList = new Cabinet();    
                    $cList = $cList->ListCabinets();
                    $body .= "<select name=cabid id=cabid>";
                    foreach($cList as $cabinet){
                        $body .= "<option value=".$cabinet->CabinetID.">".$cabinet->Location."</option>";
                    }
                }
                $body .= "</select>"
                        ."<select name=format id=format onchange='this.form.submit()'>"
                        ."<option value=''>Select format</option>"
                        ."<option value='svg'>SVG</option>"
                        ."<option value='png'>PNG</option>"
                        ."<option value='jpg'>JPG</option>"
                        ."<option value='gif'>GIF</option>"
                        ."<option value='dot'>DOT</option>"
                        ."</select>"
                        ."</form>";
            }
        }
        if(isset($_REQUEST['ajax'])){
            echo $body;
            exit;
        }
    }
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <style type="text/css"></style>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>

  <script type="text/javascript">
        $(document).ready(function(){
                $('#containmenttype').change(function(){
                        $.post('', {containmenttype: $(this).val(), ajax: ''}, function(data){
                                $('#datacontainer').html(data);
                        });
                });
        });
  </script>
</head>
<body>
        <div id="header"></div>
        <div class="page">
<?php
        include('sidebar.inc.php');
echo '          <div class="main">
                        <h2>',$config->ParameterArray['OrgName'],'</h2>
                        <h3>',__("Network Map Viewer"),'</h3>
                        <label for="containmenttype">',__("Filter type:"),'</label>
                        <select name="containmenttype" id="containmenttype">
                                <option value="">',__("Select filter type"),'</option>
                                <option value="0">',__("Container"),'</option>
                                <option value="1">',__("Data Center"),'</option>
                                <option value="2">',__("Zone"),'</option>
                                <option value="3">',__("Cabinet Row"),'</option>
                                <option value="4">',__("Cabinet"),'</option>
                        </select>';?>
                        <br><br>
                        <div id="datacontainer">
<?php echo $body; ?>
                        </div>
                </div><!-- END div.main -->
        </div><!-- END div.page -->
</body>
</html>
