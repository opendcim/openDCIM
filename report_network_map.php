<?php
    require_once "db.inc.php";
    require_once "facilities.inc.php";

    $subheader=__("Network Map Viewer");

    $dotCommand = $config->ParameterArray["dot"];
    # if format is set, graph options should be set and ready to be rendered
    if(isset($_REQUEST['format'])) {
        # find the directory that dcim is being hosted out of (used when we build
        # the url for each node
        $baseURI = $config->ParameterArray["InstallURL"];
        # way to randomize the colors/styles
        # 0 == randomize colors if color can't be identified from db
        # 1 == Randomize colors, keeping same color for each colorid
        # 2 == Randomize colors, keeping same color for each mediaid
        # 3 == Randomize colors, keeping same color for each mediaid/colorid pair
        $colorType = isset($_REQUEST['colortype'])?$_REQUEST['colortype']:0;
        # mediaid to filter on. 0 for all.
        $mediaID = isset($_REQUEST['mediaid'])?$_REQUEST['mediaid']:-1;
        $graphname = "Network Map for ";
        $graphstr = "";
        $devList=array();
        # a list of colors recognized by graphviz. not all of them.
        $colorList = array(
            "aquamarine", "bisque", "black", "blue", "blueviolet", "brown",
            "burlywood", "cadetblue", "chartreuse", "chocolate", "coral",
            "cornflowerblue", "crimson", "cyan", "darkblue", "darkcyan",
            "darkgoldenrod", "darkgreen", "darkkhaki", "darkmagenta",
            "darkolivegreen", "darkorange", "darkorchid", "darkred", "darksalmon",
            "darkseagreen", "darkslateblue", "darkslategray", "darkturquoise",
            "darkviolet", "deeppink", "deepskyblue", "dimgray", "dodgerblue",
            "fuchsia", "gold", "goldenrod", "gray", "green", "hotpink",
            "indianred", "indigo", "khaki", "lavender", "lawngreen", "lightblue",
            "lightcoral", "lightgoldenrod", "lightpink", "lightseagreen",
            "lightskyblue", "lightslateblue", "lightslategray", "lightsteelblue",
            "lime", "magenta", "maroon", "mediumaquamarine", "mediumblue",
            "mediumorchid", "mediumpurple", "mediumseagreen", "mediumslateblue",
            "mediumspringgreen", "mediumturquoise", "mediumvioletred", "navy",
            "olive", "orange", "orangered", "orchid", "palegreen", "palevioletred",
            "peachpuff", "peru", "plum", "purple", "red", "rosybrown", "royalblue",
            "saddlebrown", "salmon", "seagreen", "silver", "skyblue", "slateblue",
            "springgreen", "steelblue", "tomato", "turquoise", "violet",
            "violetred", "yellow", "yellowgreen"
        );
        # decent default color palette for identifying device types
        $safeDeviceColors = array(
            "cadetblue2", "deepskyblue4", "palegreen", "forestgreen",
            "lightpink", "red", "navajowhite", "darkorange", "plum", "purple",
            "khaki", "sienna", "black"
        );
        $deviceTypes = array(
                'Server','Appliance','Storage Array','Switch','Chassis',
                'Patch Panel','Physical Infrastructure','CDU','Sensor'
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
                        if(!isset($devList[$dev->DeviceType])) {
                          $devList[$dev->DeviceType] = array();
                        }
                        $devList[$dev->DeviceType][$dev->DeviceID]=array();
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
            foreach($cabinet->ListCabinetsByDC() as $cab) {
                $device = new Device();
                $device->Cabinet = $cab->CabinetID;
                foreach($device->ViewDevicesByCabinet(true) as $dev) {
                    if(!isset($devList[$dev->DeviceType])) {
                        $devList[$dev->DeviceType] = array();
                    }
                    $devList[$dev->DeviceType][$dev->DeviceID]=array();
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
                    if(!isset($devList[$dev->DeviceType])) {
                      $devList[$dev->DeviceType] = array();
                    }
                    $devList[$dev->DeviceType][$dev->DeviceID]=array();
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
                        if(!isset($devList[$dev->DeviceType])) {
                          $devList[$dev->DeviceType] = array();
                        }
                        $devList[$dev->DeviceType][$dev->DeviceID]=array();
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
                if(!isset($devList[$dev->DeviceType])) {
                  $devList[$dev->DeviceType] = array();
                }
                $devList[$dev->DeviceType][$dev->DeviceID]=array();
            }
        } elseif(isset($_REQUEST['tagname'])){
            $graphname .= "Custom Tag " . $_REQUEST['tagname'];
            $device = new Device();
            foreach ( $device->SearchByCustomTag( $_REQUEST['tagname'] ) as $dev ) {
                if(!isset($devList[$dev->DeviceType])) {
                  $devList[$dev->DeviceType] = array();
                }
                $devList[$dev->DeviceType][$dev->DeviceID]=array();
            }
        } elseif (isset($_REQUEST['projectid'])) {
            $p = Projects::getProject( $_REQUEST["projectid"]);
            $graphname .= __("Project Name") . ": " . $p->ProjectName;
            $dList = ProjectMembership::getProjectMembership($p->ProjectID);
            foreach( $dList as $dev ) {
                if ( !isset($devList[$dev->DeviceType])) {
                    $devList[$dev->DeviceType] = array();    
                }
                $devList[$dev->DeviceType][$dev->DeviceID]=array();
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

        foreach($devList as $deviceType => $dev) {
            foreach($dev as $devid => $interesting_ports) {
                $ports=DevicePorts::getPortList($devid);
                foreach($ports as $port) {
                    if(($mediaID == -1) || ($port->MediaID == $mediaID)) {
                        if(isset($port->ConnectedDeviceID) && ($port->ConnectedDeviceID>0)) {
                            # if the connected device isn't in our list of devices, add it so we 
                            # at least get nice names for the devices outside the selected scope
                            $tdev = new Device();
                            $tdev->DeviceID = $port->ConnectedDeviceID;
                            $tdev->GetDevice();
                            if(!isset($devList[$tdev->DeviceType][$port->ConnectedDeviceID])) {
                                $tPort = new DevicePorts();
                                $tPort->DeviceID = $port->ConnectedDeviceID;
                                $tPort->PortNumber = $port->ConnectedPort;
                                $tPort->getPort();
                                $devList[$tdev->DeviceType][$tdev->DeviceID] = array();
                                $devList[$tdev->DeviceType][$tdev->DeviceID][] = $tPort;
                            }
                            unset($tdev);
                            $devList[$deviceType][$devid][] = $port;
                        }
                    }
                }
            }
        }
        # Generate a list of ports from the device lists.
        $portList=array();
        foreach($devList as $deviceType => $dev) {
            foreach($dev as $devid => $ports) {
                $c_port_list=array();
                foreach($ports as $port) {
                    $portList[]=array(
                        'ConnectedDeviceID'=>$port->ConnectedDeviceID,
                         'DeviceID'=>$port->DeviceID,
                         'ConnectedPort'=>$port->ConnectedPort,
                         'PortNumber'=>$port->PortNumber,
                         'ColorID'=>$port->ColorID,
                         'MediaID'=>$port->MediaID,
                         'Label'=>$port->Label
                    );
                    if ( $port->PortNumber < 0 ) {
                        $c_port_list[]="<".$port->PortNumber."> (Rear)".$port->Label;
                    } else {
                        $c_port_list[]="<".$port->PortNumber."> ".$port->Label;
                    }
                }
                $p_count = count($c_port_list);
                $n_dev_label = "{";
                $tdev = new Device();
                $tdev->DeviceID = $devid;
                $tdev->GetDevice();
                # Generate the "Label" for the device. The label specifies
                # the name and the port list.
                for($i=0;$i<$p_count;$i++) {
                    if(($p_count > 1)&&($i==0)) {
                        $n_dev_label .= "{";
                    }
                    if(floor($p_count/2) == $i){
                        if ($p_count > 1) {
                          $n_dev_label .= "}|{".$tdev->Label."}|{";
                        } else {
                          $n_dev_label .= "{".$tdev->Label."}|{";
                        }
                    }
                    $n_dev_label .= $c_port_list[$i];
                    if(($i < $p_count - 1) && ($i+1 != floor($p_count/2))) {
                        $n_dev_label .= "|";
                    }
                }
                $n_dev_label .= "}}";
                $devList[$deviceType][$devid] = $n_dev_label;
            }
        }
        # create a lookup table for colors on the fly. This helps make sure that
        # the random colors we select (for colors we can't match) stay consistent
        $myCableColorList = array();
        # build the following datastructure:
        # array[devicepair][portpair][deviceid] = label
        # and add all the devices as nodes on the graph
        $devportmapping = array();
        foreach($portList as $port){
            # sorted device pair to be used as a key. this keeps connections
            # between two hosts together and lets us match them up better later
            $tkeypair = array($port['DeviceID'], $port['ConnectedDeviceID']);
            # add the device to the dotfile, if it wasn't sent already
            foreach($devList as $deviceType => $dev) {
                if(array_key_exists($tkeypair[0], $dev)) {
                    $dt = $deviceType;
                    if(in_array($dt, $deviceTypes)){
                        $color = $safeDeviceColors[array_search($dt, $deviceTypes)];
                    }else{
                        $color = $safeDeviceColors[array_rand($safeDeviceColors)];
                    }

                    $graphstr .= "\t".$tkeypair[0]." [shape=Mrecord,URL=\"".$baseURI
                            .'devices.php?DeviceID='.$tkeypair[0]."\",label=\""
                            .$devList[$dt][$tkeypair[0]]."\",color=".$color."];\n";
                    unset($devList[$dt][$tkeypair[0]]);
                    break;
                }
            }
            foreach($devList as $deviceType => $dev) {
                if(array_key_exists($tkeypair[1], $dev)) {
                    $dt = $deviceType;
                    if(in_array($dt, $deviceTypes)){
                        $color = $safeDeviceColors[array_search($dt, $deviceTypes)];
                    }else{
                        $color = $safeDeviceColors[array_rand($safeDeviceColors)];
                    }
                    $graphstr .= "\t".$tkeypair[1]." [shape=Mrecord,URL=\"".$baseURI
                            .'devices.php?DeviceID='.$tkeypair[1]."\",label=\""
                            .$devList[$dt][$tkeypair[1]]."\",color=".$color."];\n";
                    unset($devList[$dt][$tkeypair[1]]);
                    break;
                }
            }
            sort($tkeypair);
            $tkey = $tkeypair[0].":".$tkeypair[1];
            if(!isset($devportmapping[$tkey])){
                $devportmapping[$tkey] = array();
            }
            # create a port key, 1st device gets first port number.
            # allows for matching up port numbers.
            if($port['DeviceID'] == $tkeypair[0]) {
                $pkey = $port['PortNumber'].":".$port['ConnectedPort'];
            } else {
                $pkey = $port['ConnectedPort'].":".$port['PortNumber'];
            }
            if(!isset($devportmapping[$tkey][$pkey])) {
               $devportmapping[$tkey][$pkey] = array();
            }
            # grab the current device's label and portnumber for the port, allows cleaner labeling of connections
            # used in $devid below
            $devportmapping[$tkey][$pkey][$port['DeviceID']] = array($port['Label'], $port['PortNumber']);
            # 0: tries to use the color from the db, if we can identify it.
            # otherwise uses a random color from the list above.
            # 1: randomizes the colors by colorid
            # 2: randomizes the colors by mediaid
            # 3: randomizes the colors by both 1 and 2. 
            if(($colorType == 0)||($colorType == 1)){
                $portColorKey = $port['ColorID'];
            }elseif($colorType == 2){
                $portColorKey = $port['MediaID'];
            }else{
                $portColorKey = $port['ColorID'].":".$port['MediaID'];
            }
            if(!isset($myCableColorList[$portColorKey])) {
                $cc = new ColorCoding();
                $cc->ColorID = $port['ColorID'];
                $cc->GetCode();
                $color = strtolower($cc->Name);
                if((!in_array($color, $colorList))||($colorType != 0)) {
                    $color = $colorList[array_rand($colorList)];
                }
            } else {
                $color = $myCableColorList[$portColorKey];
            }
            $myCableColorList[$portColorKey] = $color;
            $devportmapping[$tkey][$pkey]['color'] = $color;
            # store the media id. we only have 3 "styles" of cables we can use
            # we'll use this later to decide the style we should use. most useful
            # with media enforcement, otherwise the style might fluctuate between views
            $devportmapping[$tkey][$pkey]['mediaid'] = $port['MediaID'];
        }
        unset($devList);
        unset($portList);
        foreach ($devportmapping as $tkey => $value){
            $tkeypair = explode(":", $tkey, 2);
            foreach($value as $pkey => $devid) {
                # choose the style of connection. just went with a modulus to keep things simple.
                # Only modify the style if the colors can't distinguish media id.
                if(($colorType == 1 )||($colorType == 2)) {
                    $styleList = array('bold', 'dashed', 'solid');
                    $style = ",style=".$styleList[$devid['mediaid']%3];
                } else {
                    $style = "";
                }
                # if we can't find a label for either side, the side without is considered outside
                # the filter specified within your view.
                if(!isset($devid[$tkeypair[0]])) {
                    $devid[$tkeypair[0]][0] = "outside filter";
                    $style = ",style=dotted";
                }
                if(!isset($devid[$tkeypair[1]])) {
                    $devid[$tkeypair[1]][0] = "outside filter";
                    $style = ",style=dotted";
                }
                # add the connection to the dotfile.
                $graphstr .= "\t".$tkeypair[0].":\"".$devid[$tkeypair[0]][1]."\" -- "
                        .$tkeypair[1].":\"".$devid[$tkeypair[1]][1]
                        ."\" [color=".$devid['color'].$style;
                # label the connections if so desired.
                if (isset($_REQUEST["edgelabels"])) {
                    $graphstr .= ",label=\"".$devid[$tkeypair[0]][0]."--"
                    .$devid[$tkeypair[1]][0]."\"";
                }
                $graphstr .= "];\n";
            }
        }

        # Lets add a legend. prefixing with cluster puts a box around it.
        $graphstr .= "\tsubgraph cluster_legend {\n"
                    ."\t\tlabel = Legend\n\n";
        # put all the device types into the legend.
        foreach($deviceTypes as $dt){
            $color = $safeDeviceColors[array_search($dt, $deviceTypes)];
            $graphstr .= "\t\t\"".md5($dt)."\" [shape=Mrecord,color=".$color.",label=\"".$dt."\"];\n";
        }
        # add a couple invisible nodes for cabling
        $graphstr .="\t\tinvis1 [shape=box,style=invis];\n";
        $graphstr .="\t\tinvis2 [shape=box,style=invis];\n";
        # colorize the cables in the legend
        foreach($myCableColorList as $colorKey => $color) {
            if(($colorType == 0) || ($colorType == 1)){
                $cc = new ColorCoding();
                $cc->ColorID = $colorKey;
                $cc->GetCode();
                $label = $cc->Name!=""?$cc->Name:"Unset";
            } elseif ($colorType == 2){
                $mt = new MediaTypes();
                $mt->MediaID = $colorKey;
                $mt->GetType();
                $label = $mt->MediaType!=""?$mt->MediaType:"Unset";
            } else {
                $keys = explode(':', $colorKey);
                $cc = new ColorCoding();
                $cc->ColorID = $keys[0];
                $cc->GetCode();
                $mt = new MediaTypes();
                $mt->MediaID = $keys[1];
                $mt->GetType();
                $colorname = $cc->Name!=""?$cc->Name:"Unset";
                $mediatype = $mt->MediaType!=""?$mt->MediaType:"Unset";
                $label = $mediatype."--".$colorname;
            }
            $graphstr .= "\t\tinvis1 -- invis2 [color=".$color.",label=\"".$label."\"];\n";
        }
        $graphstr .= "\t}\n}";

        # safe format types. newer versions of graphviz also support pdf. maybe
        # we should add it when the ability is more prevalent.
        $formatTypes = array(
            'svg', 'png', 'jpg', 'dot'
        );
        if(!isset($formatTypes[$_REQUEST['format']])) {
            exit;
        }
        $ft = $formatTypes[$_REQUEST['format']];
        $header = "Content-Type: ";
        if ($ft == 'dot') {
            $header .= "text/plain";
            header($header);
            echo $graphstr;
            exit;
        }
        $dotfile = tempnam('/tmp/', 'dot_');
        $graphfile = tempnam('/tmp/', 'graph_');
        $file_written = file_put_contents($dotfile, $graphstr);
        if(!$file_written) {
            $body = "<span class=\"errmsg\">ERROR: There was a problem writing out the temp dot file. Check that php can write to /tmp</span>";
        } else {
            $graph = array();
            $retval = 0;
            if($ft == 'svg') {
                $header .= "image/svg+xml";
            } elseif($ft == 'png') {
                $header .= "image/png";
            } elseif($ft == 'jpg') {
                $header .= "image/jpeg";
            }
            exec($dotCommand." -T".$ft." -o".$graphfile." ".$dotfile, $graph, $retval);
            if($retval == 0) {
                header($header);
                unlink($dotfile);
                print file_get_contents($graphfile);
                unlink($graphfile);
                exit;
            } elseif ($ft == 'svg') {
                $body = "<span class=\"errmsg\">ERROR: There was a problem processing the graph. Probably a bug, please submit a report containing the contents of ".$dotfile." to the openDCIM bug tracker</span>";
            } else {
                $body = "<span class=\"errmsg\">ERROR: There was a problem processing the graph. Try choosing 'svg' as the output type.</span>";
            }
        }
    } else {
        $body="";
        if(isset($_REQUEST['containmenttype'])) {
            # start filtering
            $containmentType = isset($_POST['containmenttype'])?$_POST['containmenttype']:$_GET['containmenttype'];
            if($containmentType != "") {
                $body = "<form method='post'>";
                $options = array();
                if($containmentType == "container" ){
                    # filtering by container
                    $cList = new Container();    
                    $cList = $cList->GetContainerList();
                    $body .= "<select name=containerid id=containerid>";
                    foreach($cList as $c){
                        $options[$c->ContainerID] = $c->Name;
                    }
                } elseif($containmentType == "datacenter" ){
                    # filtering by dc
                    $dcList = new DataCenter();    
                    $dcList = $dcList->GetDCList();
                    $body .= "<select name=datacenterid id=datacenterid>";
                    foreach($dcList as $dc){
                        $options[$dc->DataCenterID] = $dc->Name;
                    }
                } elseif($containmentType == "zone" ){
                    #filtering by zone
                    $zList = new Zone();    
                    $zList = $zList->GetZoneList();
                    $body .= "<select name=zoneid id=zoneid>";
                    foreach($zList as $zone){
                        $dc = new DataCenter();
                        $dc->DataCenterID = $zone->DataCenterID;
                        $dc->GetDataCenter();
                        $options[$zone->ZoneID] = $dc->Name."::".$zone->Description;
                    }
                } elseif($containmentType == "cabinetrow" ){
                    # filter by cabrow
                    $crList = new CabRow();    
                    $crList = $crList->GetCabRowList();
                    $body .= "<select name=cabrowid id=cabrowid>";
                    foreach($crList as $cabrow){
                        $options[$cabrow->CabRowID] = $cabrow->Name;
                    }
                } elseif($containmentType == "cabinet" ){
                    # filter by cabinet
                    $cList = new Cabinet();    
                    $cList = $cList->ListCabinets();
                    $body .= "<select name=cabid id=cabid>";
                    foreach($cList as $cabinet){
                        $dc = new DataCenter();
                        $dc->DataCenterID = $cabinet->DataCenterID;
                        $dc->GetDataCenter();
                        $options[$cabinet->CabinetID] = $dc->Name."::".$cabinet->Location;
                    }
                } elseif($containmentType == "customtag" ) {
                    # filter by custom tag
                    $tList = Tags::FindAll();
                    $body .= "<select name=tagname id=tagname>";
                    foreach( $tList as $TagID=>$Name ){
                        $options[$Name] = $Name;
                    }
                } elseif($containmentType == "project" ) {
                    # filter by project / service
                    $pList = Projects::getProjectList();
                    $body .= "<select name=projectid id=projectid>";
                    foreach( $pList as $p ) {
                        $options[$p->ProjectID] = $p->ProjectName;
                    }
                }
                # sort and output the options based on the name of the option
                asort($options);
                foreach($options as $key => $val) {
                    $body .= "<option value=".$key.">".$val."</option>";
                }
                $body .= "</select>"
                        ."<select name=mediaid id=mediaid>"
                        ."<option value=-1>All Media Types</option>"
                        ."<option value=0>Unknown Media Types</option>";
                foreach(MediaTypes::GetMediaTypeList() as $mt) {
                        $body .= "<option value=".$mt->MediaID.">".$mt->MediaType."</option>";
                }
                $body .= "</select>"
                        ."<select name=colortype id=colortype>"
                        ."<option value=0>Try to use colors from the database</option>"
                        ."<option value=1>Randomize colors, per color</option>"
                        ."<option value=2>Randomize colors, per mediatype</option>"
                        ."<option value=3 selected>Randomize colors, per color and mediatype</option>"
                        ."</select>"
                        ."<input type=\"checkbox\" name=edgelabels checked value=true>Label Cables"
                        ."<select name=format id=format onchange='this.form.submit()'>"
                        ."<option value=''>Select format</option>"
                        ."<option value=0>SVG</option>"
                        ."<option value=1>PNG</option>"
                        ."<option value=2>JPG</option>"
                        ."<option value=3>DOT</option>"
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
                $('#containmenttype').val("");
        });
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
        <div class="page">
<?php
        include('sidebar.inc.php');
echo '          <div class="main">
                        <label for="containmenttype">',__("Filter type:"),'</label>
                        <select name="containmenttype" id="containmenttype">
                                <option value="">',__("Select filter type"),'</option>
                                <option value="cabinet">',__("Cabinet"),'</option>
                                <option value="cabinetrow">',__("Cabinet Row"),'</option>
                                <option value="container">',__("Container"),'</option>
                                <option value="customtag">',__("Custom Tag"),'</option>
                                <option value="datacenter">',__("Data Center"),'</option>
                                <option value="project">',__("Project"),'</option>
                                <option value="zone">',__("Zone"),'</option>
                        </select>';?>
                        <br><br>
                        <div id="datacontainer">
<?php echo $body; ?>
                        </div>
<?php if(!is_executable($dotCommand)) {
echo '                  <span class="errmsg">ERROR: You must have the dot command (from the graphviz package) installed and its location specified in the configuration</span>';
}
?>
                </div><!-- END div.main -->
        </div><!-- END div.page -->
</body>
</html>
