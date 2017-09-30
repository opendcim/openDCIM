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

class ConnectionPath {
	/* ConnectionPath:	Display connection path between two endpoint devices through DC infrastructure.
						Initial info are DeviceID and PortNumber. 
						Then locates one end of the connection path with "GotoHeadDevice" method.
						Walk the path to the other end with "GotoNextDevice" method.	 
	 					Contribution of Jose Miguel Gomez Apesteguia (June 2013)
	*/
	
	var $DeviceID;
	var $PortNumber; //The sign of PortNumber indicate if the path continue by front port (>0) or rear port (<0)
	
	private $PathAux; //loops control
	
	function MakeSafe(){
		$this->DeviceID=intval($this->DeviceID);
		$this->PortNumber=intval($this->PortNumber);
	}

	private function AddDeviceToPathAux () {
		$i=count($this->PathAux);
		$this->PathAux[$i]["DeviceID"]=$this->DeviceID;
		$this->PathAux[$i]["PortNumber"]=$this->PortNumber;
	}
	
	private function ClearPathAux(){
		$this->PathAux=array();
	}
	
	private function IsDeviceInPathAux () {
		$ret=false;
		$crossovercount=0;
		for ($i=0; $i<count($this->PathAux); $i++){
			if ($this->PathAux[$i]["DeviceID"]==$this->DeviceID && $this->PathAux[$i]["PortNumber"]=$this->PortNumber) {
				++$crossovercount;
				if($crossovercount>=200){
					$ret=true;
					break;
				}
			}
		}
		return $ret;
	}
	
	function GotoHeadDevice () {
	//It puts the object in the first device of the path, if it is not it already
		$this->MakeSafe();
		$this->ClearPathAux();
		
		$FrontPort=new DevicePorts();
		$FrontPort->DeviceID=$this->DeviceID;
		$FrontPort->PortNumber=abs($this->PortNumber);
		
		$RearPort=new DevicePorts();
		$RearPort->DeviceID=$this->DeviceID;
		$RearPort->PortNumber=-abs($this->PortNumber);
		
		if ($FrontPort->getPort() && $RearPort->getPort()){
			//It's a Panel (intermediate device)
			while ($this->GotoNextDevice ()){
				if (!$this->IsDeviceInPathAux()){
					$this->AddDeviceToPathAux();
				}else {
					//loop!!
					return false;
				}
			}
			//change orientation
			$this->PortNumber=-$this->PortNumber;
		} else {
			//It's not a panel
			$this->PortNumber=abs($this->PortNumber);
		}
		return true;
	}
	
	function GotoNextDevice () {
	//It puts the object with the DeviceID and PortNumber of the following device in the path.
	//If the current device of the object is not connected to at all, gives back "false" and the object does not change
		global $dbh;
		$this->MakeSafe();
		
		$port=new DevicePorts();
		$port->DeviceID=$this->DeviceID;
		$port->PortNumber=$this->PortNumber;
		if ($port->getPort()){
			if (is_null($port->ConnectedDeviceID) || is_null($port->ConnectedPort)){
				return false;
			} else {
				$this->DeviceID=$port->ConnectedDeviceID;
				$this->PortNumber=-$port->ConnectedPort;
				return true;
			}
		} else
			return false;
	}
	
	
} 
?>
