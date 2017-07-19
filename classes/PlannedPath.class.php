<?php
/*
	openDCIM

	This is the main class library for the openDCIM application, which
	is a PHP/Web based data center infrastructure management system.

	This application was originally written by Scott A. Milliken while
	employed at Vanderbilt University in Nashville, TN, as the
	Data Center Manager, and released under the GNU GPL.

	Copyright (C) 2011 Scott A. Milliken

	This program is free software:  you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published
	by the Free Software Foundation, version 3.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	For further details on the license, see http://www.gnu.org/licenses
*/

class PlannedPath {
	/* PlannedPath:		Search a minimun weight connection path between two endpoint devices.
	 					It use the panels to reach the goal. 
	 					From each device, first it try connecting patch panels or final device on actual cabinet, 
	 					and if is not posible, in the actual row of cabinets.
	 					Initial info are devID1, port1, devID2, port2.
	 					It use "MediaEnforce" configuration parameter for connections.
	 					Then, call to "MakePath" method to find the Path.
	 					If successful, go to the beginning of the connection path with "GotoHeadDevice" method.
						Walk the path to the other end with "GotoNextDevice" method.
						The "MakePath" method leaves a log file (ppath.log) on the server with the execution of the algorithm, for testing.
	 					Contribution of Jose Miguel Gomez Apesteguia (July 2013)
	*/
	
	//Device info for output
	var $DeviceID;
	var $PortNumber; //The sign of PortNumber indicate if the path continue to front port (>0) or rear port (<0)

	//initial device info input   
	var $devID1; 	
	var $port1;

	//final device info input
	var $devID2; 	
	var $port2;
	
	//aux info	
	private $cab2;		//Cabinet of final device
	private $row2;		//row of final device
	private $espejo2; 	//for ports protected by a panel in devID2 (port2 connected to rear connection of panel)

	private $nodes;		//array of nodes: [dev][port]{["prev_dev"],["prev_port"]}
	private $candidates;	//array of candidate nodes: [dev]{[port]}
							//an array smaller than $nodes, so the selection of the next node is faster
	private $used_candidates;	//array of used candidates
	
	//Path for output 
	var $Path; 			//array with created Path
	private $acti;  	//index of actual dev in $Path
	
	//error output
	var $PathError;
	
	private function escribe_log($texto){
		//remove next line if you want a log file on server
		//return;
		
	    $ddf = fopen('ppath.log','ab');
        fwrite($ddf,$texto."\r\n");
	    fclose($ddf);
	}
	
	private function MakeSafe(){
		$this->devID1=intval($this->devID1);
		$this->port1=intval($this->port1);
		$this->devID2=intval($this->devID2);
		$this->port2=intval($this->port2);
	}
	
	private function ClearPath(){
		$this->Path=array();
		$this->nodes=array();
		$this->candidates=array();
		$this->used_candidates=array();
	}
	
	private function AddNodeToList ($dev,$port,$weight,$prev_dev,$prev_port) {
		//Trato distinto las conexiones traseras y las frontales: las traseras nunca van a ser candidatos
		//Separate treatment for rear and front connections: the rear will never be candidates
		if($port<0 && $dev<>$this->devID2){
			if (isset($this->nodes[$dev][$port])) {
				if ($this->nodes[$dev][$port]["weight"]>$weight){
					$this->escribe_log("  -->Better path=>UPDATE NODE D=".$dev." P=".$port." W=".$weight." PD=".$prev_dev." PP=".$prev_port);
					$this->nodes[$dev][$port]["weight"]=$weight;
					$this->nodes[$dev][$port]["prev_dev"]=$prev_dev;
					$this->nodes[$dev][$port]["prev_port"]=$prev_port;
				}
			} else {
				//es un nodo nuevo
				//it is a new node
				$this->escribe_log("  -->New node D=".$dev." P=".$port." W=".$weight." PD=".$prev_dev." PP=".$prev_port);
				$this->nodes[$dev][$port]["weight"]=$weight;
				$this->nodes[$dev][$port]["prev_dev"]=$prev_dev;
				$this->nodes[$dev][$port]["prev_port"]=$prev_port;
			}
		}else {
			if (isset($this->candidates[$dev])) {
				if ($this->nodes[$dev][$this->candidates[$dev]]["weight"]>$weight){
					$this->escribe_log("  -->Better path=>UPDATE NODE D=".$dev." P=".$port." W=".$weight." PD=".$prev_dev." PP=".$prev_port);
					unset($this->nodes[$dev]);
					$this->nodes[$dev][$port]["weight"]=$weight;
					$this->nodes[$dev][$port]["prev_dev"]=$prev_dev;
					$this->nodes[$dev][$port]["prev_port"]=$prev_port;
					$this->candidates[$dev]=$port;
				}
			} else {
				//es un nodo nuevo
				//it is a new node
				//Check is is already used
				if (!isset($this->used_candidates[$dev])){
					$this->escribe_log("  -->New node D=".$dev." P=".$port." W=".$weight." PD=".$prev_dev." PP=".$prev_port);
					$this->nodes[$dev][$port]["weight"]=$weight;
					$this->nodes[$dev][$port]["prev_dev"]=$prev_dev;
					$this->nodes[$dev][$port]["prev_port"]=$prev_port;
					$this->candidates[$dev]=$port;
				}
			}
		}
	}
	
	private function SelectNode () {
		//Busco el  nodo de la lista de candidatos el nodo con peso minimo
		//search node in candidate list with min weight
		$minweight=99999; //big number
		$this->DeviceID=0;
		$this->escribe_log("CANDIDATES:");
		foreach($this->candidates as $dev => $port) {
			$this->escribe_log("  [D=".$dev.", P=".$port.", W=".$this->nodes[$dev][$port]["weight"]."]");
			if($this->nodes[$dev][$port]["weight"]<$minweight){
				$minweight=$this->nodes[$dev][$port]["weight"];
				$this->DeviceID=$dev;
				$this->PortNumber=$port;
			}
		}
		$this->escribe_log("");
		return ($this->DeviceID<>0);
	}
	
	private function UpdateList () {
		global $config;
		//find posible next devices with lower weight in list from actual node 
		//for each device found, if already it exists and it is not useded, update it if (new weight) < (old weight)
		//if it does not exist, insert in list with his actual weight and $used=false
		//Destination device is $this->devID2
		
		//weights
		$weight_cabinet=$config->ParameterArray["path_weight_cabinet"]; 	//weight for patches on actual cabinet
		$weight_rear=$config->ParameterArray["path_weight_rear"];		//weight fot rear connetcion between panels
		$weight_row=$config->ParameterArray["path_weight_row"];		//weigth for patches on same row of cabinets (except actual cabinet)
		//It is possible to assign a weight proportional to the distance between the actual cabinet and each cabinet of actual row, 
		//so you can prioritize closest cabinets in the actual row. In the future...
		
		$this->escribe_log("\nSelected node: D=".$this->DeviceID.
						"; P=".$this->PortNumber.
						"; W=".$this->nodes[$this->DeviceID][$this->PortNumber]["weight"].
						"; PD=".$this->nodes[$this->DeviceID][$this->PortNumber]["prev_dev"].
						"; PP=".$this->nodes[$this->DeviceID][$this->PortNumber]["prev_port"]);;	
			
		//Compruebo si el puerto del dispositivo actual esta conectado a la conexion trasera de un panel
		//I check if the port of this device is connected to a rear-panel connection
		$port=new DevicePorts();
		$port->DeviceID=$this->DeviceID;
		$port->PortNumber= $this->PortNumber;
		if (!$port->getPort()){
			$this->escribe_log("ERROR GETTING PORT");
			exit;
		}
		
		if ($port->ConnectedDeviceID<>0){
			if ($port->ConnectedPort<0){
				//It's a port of the first device connected to rear panel connection or it's a rear port of a panel.
				//Go to mirror device
				$this->escribe_log(" Rear connection to D=".$port->ConnectedDeviceID." P=".$port->ConnectedPort);
				$this->AddNodeToList($port->ConnectedDeviceID,-$port->ConnectedPort,$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_rear,$this->DeviceID, $this->PortNumber);
			} else {
				//port used in mirror panel
				//nothing to do
				$this->escribe_log(" Port used in mirror panel D=".$port->ConnectedDeviceID." P=".$port->ConnectedPort);
			}
		} else {
			//It's a free front port
			//get dev info: cabinet and row
			$device=new Device();
			$device->DeviceID=$this->DeviceID;
			$device->GetDevice();
			$cab=$device->GetDeviceCabinetID();
			$cabinet=new Cabinet();
			$cabinet->CabinetID=$cab;
			$cabinet->GetCabinet();
			$cabrow=new CabRow();
			$cabrow->CabRowID = $cabinet->CabRowID;
			$cabrow->GetCabRow();
			
			//busco el dispositivo final en el mismo armario (si no esta reflejado en un panel)
			//looking for the end device in the same cabinet (if not reflected in a panel)
			if ($cab==$this->cab2 && !$this->espejo2){
				$this->escribe_log(" DEV2 found in actual cabinet (".$cab."-'".$cabinet->Location."')");
				$this->AddNodeToList($this->devID2,-$this->port2,$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_cabinet,$this->DeviceID, $this->PortNumber);
			}
			//Busco el dispositivo final en la misma fila
			//Look for the end device in the same row
			elseif ($cabrow->CabRowID<>0 && $cabrow->CabRowID==$this->row2 && !$this->espejo2){
				$this->escribe_log(" DEV2 found in actual row (".$cabrow->CabRowID."-'".$cabrow->Name."')");
				$this->AddNodeToList($this->devID2,-$this->port2,$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_row,$this->DeviceID, $this->PortNumber);
			}
			
			//busco paneles con puertos libres en el armario actual
			//Look for panels with free ports on actual cabinet
			$this->escribe_log("Look for panels with free ports on actual cabinet (".$cab."-'".$cabinet->Location."')");
			global $dbh;
			global $config;

			$mediaenforce="";
			if($config->ParameterArray["MediaEnforce"]=='enabled'){
				$mediaenforce=" AND af.MediaID=".$port->MediaID;
			}
			$sql="SELECT af.DeviceID AS DeviceID1,
						af.PortNumber AS PortNumber1,
						bf.DeviceID AS DeviceID2,
						bf.PortNumber AS PortNumber2	 
				FROM fac_Ports af, fac_Ports ar, fac_Ports bf, fac_Device d 
				WHERE d.Cabinet=".$cab." AND 
					af.DeviceID=d.DeviceID AND 
					af.DeviceID!=".$this->DeviceID." AND
					af.ConnectedDeviceID IS NULL".$mediaenforce." AND 
					d.DeviceType='Patch Panel' AND
					af.PortNumber>0 AND
					ar.DeviceID=af.DeviceID AND ar.PortNumber=-af.PortNumber AND
					bf.DeviceID=ar.ConnectedDeviceID AND bf.PortNumber=-ar.ConnectedPort AND
					bf.ConnectedDeviceID IS NULL
				ORDER BY DeviceID1,PortNumber1,DeviceID2,PortNumber2;";
			foreach($dbh->query($sql) as $row){
				//Compruebo si tengo que anadir esta pareja
				//I check if I have to add this pair of nodes
				if (isset($this->candidates[$row["DeviceID2"]]) 
					&& $this->nodes[$row["DeviceID2"]][$this->candidates[$row["DeviceID2"]]]["weight"]>$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_cabinet+$weight_rear
					|| !isset($this->candidates[$row["DeviceID2"]]) && !isset($this->used_candidates[$row["DeviceID2"]])){
					$this->AddNodeToList($row["DeviceID1"],-$row["PortNumber1"],$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_cabinet, $this->DeviceID, $this->PortNumber);
					//Anado directamente el espejo de este puerto
					//I add directly the mirror port of this port
					$this->AddNodeToList($row["DeviceID2"],$row["PortNumber2"],$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_cabinet+$weight_rear, $row["DeviceID1"],-$row["PortNumber1"]);
				}
			}
			
			//busco paneles con puertos libres en la fila actual
			//Look for panels with free ports on actual row
			$this->escribe_log("Look for panels with free ports on actual row (".$cabrow->CabRowID."-'".$cabrow->Name."')");
			$sql="SELECT af.DeviceID AS DeviceID1,
						af.PortNumber AS PortNumber1,
						bf.DeviceID AS DeviceID2,
						bf.PortNumber AS PortNumber2 
				FROM fac_Ports af, fac_Ports ar, fac_Ports bf, fac_Device d, fac_Cabinet c 
				WHERE af.DeviceID=d.DeviceID AND 
					af.DeviceID!=".$this->DeviceID." AND
					d.Cabinet=c.CabinetID AND
					d.Cabinet<>".$cab." AND
					c.CabRowID=".$cabrow->CabRowID." AND 
					af.ConnectedDeviceID IS NULL".$mediaenforce." AND 
					d.DeviceType='Patch Panel' AND
					af.PortNumber>0 AND
					ar.DeviceID=af.DeviceID AND ar.PortNumber=-af.PortNumber AND
					bf.DeviceID=ar.ConnectedDeviceID AND bf.PortNumber=-ar.ConnectedPort AND
					bf.ConnectedDeviceID IS NULL
				ORDER BY DeviceID1,PortNumber1,DeviceID2,PortNumber2;";
			foreach($dbh->query($sql) as $row){
				//Compruebo si tengo que anadir esta pareja
				//I check if I have to add this pair of nodes
				if (isset($this->candidates[$row["DeviceID2"]]) 
					&& $this->nodes[$row["DeviceID2"]][$this->candidates[$row["DeviceID2"]]]["weight"]>$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_row+$weight_rear
					|| !isset($this->candidates[$row["DeviceID2"]]) && !isset($this->used_candidates[$row["DeviceID2"]])){
					$this->AddNodeToList($row["DeviceID1"],-$row["PortNumber1"],$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_row,$this->DeviceID, $this->PortNumber);
					//Anado directamente el espejo de este puerto
					//I add directly the mirror port of this port
					$this->AddNodeToList($row["DeviceID2"],$row["PortNumber2"],$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_row+$weight_rear, $row["DeviceID1"],-$row["PortNumber1"]);
				}
			}
		}
		//quito el nodo de la lista de candidatos
		//remove the node from candidates and I include it in used_candidates
		$this->escribe_log("....Candidate DEV=".$this->DeviceID."->PORT=".$this->PortNumber." used");
		unset($this->candidates[$this->DeviceID]);
		$this->used_candidates[$this->DeviceID]=true; //any value
	}
	
	function MakePath () {
		$this->MakeSafe();
		
		//reset PathError
		$this->PathError=0;
		
		//check devices/ports
		$device=new Device();
		$device->DeviceID=$this->devID1;
		if (!$device->GetDevice()){
			$this->PathError=1;  //dev1 does not exist
			return false;
		}
		$devType1=$device->DeviceType;
		if ($device->DeviceType=="Patch Panel"){
			$this->PathError=2;  //dev1 is a Patch Pannel
			return false;
		}
		$port1=new DevicePorts();
		$port1->DeviceID=$this->devID1;
		$port1->PortNumber=$this->port1;
		if (!$port1->getPort()){
			$this->PathError=3;  //dev1,port1 is missing
			return False;
		}
		if ($port1->ConnectedDeviceID>0 && $port1->ConnectedPort>0){
			$this->PathError=4;  //dev1,port1 is connected
			return False;
		}
		$device->DeviceID=$this->devID2;
		if (!$device->GetDevice()){
			$this->PathError=5;  //dev2 does not exist
			return false;
		}
		$devType2=$device->DeviceType;
		if ($device->DeviceType=="Patch Panel"){
			$this->PathError=6;  //dev2 is a Patch Pannel
			return false;
		}
		$port2=new DevicePorts();
		$port2->DeviceID=$this->devID2;
		$port2->PortNumber=$this->port2;
		if (!$port2->getPort()){
			$this->PathError=7;  //dev2,port2 is missing
			return False;
		}
		if ($port2->ConnectedDeviceID>0 && $port2->ConnectedPort>0){
			$this->PathError=8;  //dev2,port2 is connected
			return False;
		}
		
		//get dev2 info
		$this->cab2=$device->GetDeviceCabinetID();  //cab2
		$cabinet=new Cabinet();
		$cabinet->CabinetID=$this->cab2;
		$cabinet->GetCabinet();
		$this->row2=$cabinet->CabRowID;	//row2
		
		//if dev2 is panel protected device (connected to rear connection of a panel)
		$this->espejo2=($port2->ConnectedDeviceID>0 && $port2->ConnectedPort<0);
		
		@unlink('ppath.log');
		$this->escribe_log("**** NEW PATH ****");
		$this->escribe_log("DEV1: ID=".$this->devID1."  PORT=".$this->port1);
		$this->escribe_log("DEV2: ID=".$this->devID2."  PORT=".$this->port2."  CAB_ID=".$this->cab2."  ROW_ID=".$this->row2);
		$this->escribe_log("-------------------");

		//reset Path
		$this->ClearPath();
		//initiate list with device1, port1, weitgh=0, prev_dev=0, prev_port=0
		$this->AddNodeToList($this->devID1, $this->port1, 0, 0, 0);
		
		while ($this->SelectNode()){
			if ($this->DeviceID==$this->devID2){
				$this->escribe_log("Target found. Making the PATH...");
				//make the path
				$i=1;
				while ($this->DeviceID>0) {
					$dev=$this->DeviceID;
					$port=$this->PortNumber;
					$Path[$i]["DeviceID"]=$dev;
					$Path[$i]["PortNumber"]=$port;
					$this->DeviceID=$this->nodes[$dev][$port]["prev_dev"];
					$this->PortNumber=$this->nodes[$dev][$port]["prev_port"];
					$i++;
				}
				for ($j=1;$j<$i;$j++){
					$this->Path[$j]["DeviceID"]=$Path[$i-$j]["DeviceID"];
					$this->Path[$j]["PortNumber"]=$Path[$i-$j]["PortNumber"];
				}
				$this->escribe_log("PATH created.");
				$this->escribe_log("");
				return true;
			}
			$this->UpdateList();
		}
		$this->PathError=9;  //not found
		return false;
	}
	
	function GotoHeadDevice () {
	//Pone el objeto en el primer dispositivo del Path, si no lo es ya
	//Places the object in the first device of Path, if not already
		If (isset($this->Path[1]["DeviceID"]) && $this->Path[1]["DeviceID"]==$this->devID1){
			$this->DeviceID=$this->Path[1]["DeviceID"];
			$this->PortNumber=$this->Path[1]["PortNumber"];
			$this->acti=1;
			return true;
		}else {
			return false;
		} 
	}
	
	function GotoNextDevice () {
	//Pone el objeto con el DeviceID, PortNumber y Front del dispositivo siguiente en el path.
	//Si el dispositivo actual del objeto no esta conectado a nada, devuelve "false" y el objeto no cambia
	// Places the object with the DeviceID, PortNumber and Front of the next device in the path.
    // If the object's current device is not connected returns "false" and the object doesn't change.
		$this->acti++;
		If (isset($this->Path[$this->acti]["DeviceID"])){
			$this->DeviceID=$this->Path[$this->acti]["DeviceID"];
			$this->PortNumber=$this->Path[$this->acti]["PortNumber"];
			return true;
		}else {
			return false;
		} 
		
	}
	
}
?>
