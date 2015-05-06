<?php
	$devMode = true;

	require_once('db.inc.php');
	require_once('facilities.inc.php');

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

class PatchConnection {
	/* PatchConnection:	Self explanatory - any device set as a patch will allow you to map out the port connections to
							any other device within the same data center.  For trans-data center connections, you can map the
							port back to itself, and list the external source in the Notes field.
	*/
	
	var $PanelDeviceID;
	var $PanelPortNumber;
	var $FrontEndpointDeviceID;
	var $FrontEndpointPort;
	var $RearEndpointDeviceID;
	var $RearEndpointPort;
	var $FrontNotes;
	var $RearNotes;

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}

	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function GetConnectionRecord(){
		global $dbh;

		$this->MakeSafe();
		$sql="select * from fac_PatchConnection where PanelDeviceID=$this->PanelDeviceID and PanelPortNumber=$this->PanelPortNumber";
		$sth=$dbh->prepare($sql);$sth->execute();

		if($sth->rowCount()==0){
			return -1;
		}

		foreach($sth as $row){	
			$this->FrontEndpointDeviceID=$row["FrontEndpointDeviceID"];
			$this->FrontEndpointPort=$row["FrontEndpointPort"];
			$this->RearEndpointDeviceID=$row["RearEndpointDeviceID"];
			$this->RearEndpointPort=$row["RearEndpointPort"];
			$this->FrontNotes=$row["FrontNotes"];
			$this->RearNotes=$row["RearNotes"];
		}
		
		return 1;		
	}
	
	function MakeFrontConnection($recursive=true){
		$this->MakeSafe();

		$tmpDev=new Device();
		$tmpDev->DeviceID = $this->PanelDeviceID;
		$tmpDev->GetDevice();
		
		// If you pass a port number lower than 1, or higher than the total number of ports defined for the patch panel, then bounce
		if ( $this->PanelPortNumber < 1 || $this->PanelPortNumber > $tmpDev->Ports )
			return -1;
			
		$sql="INSERT INTO fac_PatchConnection VALUES ($this->PanelDeviceID, $this->PanelPortNumber, $this->FrontEndpointDeviceID, $this->FrontEndpointPort, NULL, NULL, \"$this->FrontNotes\", NULL ) ON DUPLICATE KEY UPDATE FrontEndpointDeviceID=$this->FrontEndpointDeviceID,FrontEndpointPort=$this->FrontEndpointPort,FrontNotes=\"$this->FrontNotes\";";

		if(!$this->query($sql)){
			return -1;
		}

		$tmpDev->DeviceID=$this->FrontEndpointDeviceID;
		$tmpDev->GetDevice();
		
		if($recursive && $tmpDev->DeviceType=="Switch"){
			$tmpSw = new SwitchConnection();
			$tmpSw->SwitchDeviceID = $this->FrontEndpointDeviceID;
			$tmpSw->SwitchPortNumber = $this->FrontEndpointPort;
			$tmpSw->EndpointDeviceID = $this->PanelDeviceID;
			$tmpSw->EndpointPort = $this->PanelPortNumber;
			$tmpSw->Notes = $this->FrontNotes;
			
			// Remove any existing connection from this port
			$tmpSw->RemoveConnection( );
			// Call yourself, but with the recursive = false so that you don't create a loop
			$tmpSw->CreateConnection(false );
		}

		if ( $recursive && $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPanel = new PatchConnection();
			$tmpPanel->PanelDeviceID = $this->FrontEndpointDeviceID;
			$tmpPanel->PanelPortNumber = $this->FrontEndpointPort;
			$tmpPanel->FrontEndpointDeviceID = $this->PanelDeviceID;
			$tmpPanel->FrontEndpointPort = $this->PanelPortNumber;
			$tmpPanel->FrontNotes = $this->FrontNotes;
			$tmpPanel->MakeFrontConnection( false );
		}
		
		$this->GetConnectionRecord(); // reload the object from the DB
		return 1;
	}
	
	function MakeRearConnection($recursive=true){
		$this->MakeSafe();
		
		$tmpDev=new Device();
		$tmpDev->DeviceID = $this->PanelDeviceID;
		$tmpDev->GetDevice();
		
		// If you pass a port number lower than 1, or higher than the total number of ports defined for the patch panel, then bounce
		if ( $this->PanelPortNumber < 1 || $this->PanelPortNumber > $tmpDev->Ports )
			return -1;
		
		$sql="INSERT INTO fac_PatchConnection VALUES ($this->PanelDeviceID, $this->PanelPortNumber, NULL, NULL, $this->RearEndpointDeviceID, $this->RearEndpointPort, NULL, \"$this->RearNotes\" ) ON DUPLICATE KEY UPDATE RearEndpointDeviceID=$this->RearEndpointDeviceID,RearEndpointPort=$this->RearEndpointPort,RearNotes=\"$this->RearNotes\";";
		if(!$this->query($sql)){	
			return -1;
		}

		$tmpDev->DeviceID = $this->RearEndpointDeviceID;
		$tmpDev->GetDevice();
		
		// Patch Panel rear connections will only go to circuits or other patch panels
		// So there is no need to test for a switch like with the front side
		if ( $recursive && $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPanel = new PatchConnection();
			$tmpPanel->PanelDeviceID = $this->RearEndpointDeviceID;
			$tmpPanel->PanelPortNumber = $this->RearEndpointPort;
			$tmpPanel->RearEndpointDeviceID = $this->PanelDeviceID;
			$tmpPanel->RearEndpointPort = $this->PanelPortNumber;
			$tmpPanel->RearNotes = $this->RearNotes;
			$tmpPanel->MakeRearConnection( false );
		}
		
		$this->GetConnectionRecord(); // reload the object from the DB
		return 1;
	}
	
	function RemoveFrontConnection($recursive=true){
		$this->GetConnectionRecord(); // just pulled data from db both variables are int already, no need to sanitize again
		$sql="UPDATE fac_PatchConnection SET FrontEndpointDeviceID=NULL, FrontEndpointPort=NULL, FrontNotes=NULL WHERE PanelDeviceID=$this->PanelDeviceID AND PanelPortNumber=$this->PanelPortNumber;";

		if(!$this->query($sql)){
			return -1;
		}
		
		// Check the endpoint of the front connection in case it has a reciprocal connection
		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->FrontEndpointDeviceID;
		$tmpDev->GetDevice();
		
		if ( $recursive && $tmpDev->DeviceType == "Switch" ) {
			$tmpSw = new SwitchConnection();
			$tmpSw->SwitchDeviceID = $this->FrontEndpointDeviceID;
			$tmpSw->SwitchPortNumber = $this->FrontEndpointPort;
			$tmpSw->RemoveConnection();	
		}
		
		// Patch panel connections can go front to front, or rear to rear, but never front to rear
		// So since this is a front connection removal, you only need to remove the front connection
		// at the opposite end
		if ( $recursive && $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPanel = new PatchConnection();
			$tmpPanel->PanelDeviceID = $this->FrontEndpointDeviceID;
			$tmpPanel->PanelPortNumber = $this->FrontEndpointPort;
			$tmpPanel->RemoveFrontConnection( false );
		}
		$this->GetConnectionRecord(); // reload the object from the DB
		return 1;
	}
	
	function RemoveRearConnection($recursive=true){
		$this->GetConnectionRecord(); // just pulled data from db both variables are int already, no need to sanitize again
		$sql="UPDATE fac_PatchConnection SET RearEndpointDeviceID=NULL, RearEndpointPort=NULL, RearNotes=NULL WHERE PanelDeviceID=$this->PanelDeviceID AND PanelPortNumber=$this->PanelPortNumber;";

		if(!$this->query()){
			return -1;
		}
		
		// Check the endpoint of the front connection in case it has a reciprocal connection
		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->RearEndpointDeviceID;
		$tmpDev->GetDevice();
		// Patch panel rear connections can only go to either
		//		(a) Another patch panel (rear)
		//		(b) A circuit ID - in which case the DeviceID is 0, but the notes has the circuit ID
		
		// Patch panel connections can go front to front, or rear to rear, but never front to rear
		// So since this is a front connection removal, you only need to remove the front connection
		// at the opposite end
		if($recursive && $tmpDev->DeviceType == "Patch Panel"){
			$tmpPanel=new PatchConnection();
			$tmpPanel->PanelDeviceID = $this->RearEndpointDeviceID;
			$tmpPanel->PanelPortNumber = $this->RearEndpointPort;
			$tmpPanel->RemoveRearConnection(false);
		}
		
		return 1;
	}
	
	function DropEndpointConnections() {
		global $dbh;

		// You call this when deleting an endpoint device, other than a patch panel
		$this->MakeSafe();

		$sql="UPDATE fac_PatchConnection SET FrontEndpointDeviceID=NULL, 
			FrontEndpointPort=NULL, FrontNotes=NULL WHERE 
			FrontEndpointDeviceID=$this->FrontEndpointDeviceID;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("DropEndpointConnections::PDO Error: {$info[2]} SQL=$sql");
			return -1;
		}else{
			return true;
		}
	}
	
	function DropPanelConnections() {
		global $dbh;

		// You only call this when you are deleting another patch panel
		$this->MakeSafe();
		$sql="UPDATE fac_PatchConnection SET RearEndpointDeviceID=NULL, 
			RearEndpointPort=NULL, RearNotes=NULL WHERE 
			FrontEndpointDeviceID=$this->FrontEndpointDeviceID;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("DropPanelConnections::PDO Error: {$info[2]} SQL=$sql");
			return -1;
		}
		
		// Delete any records for this panel itself
		$sql="DELETE FROM fac_PatchConnection WHERE PanelDeviceID=$this->PanelDeviceID;";
		
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("DropPanelConnections::PDO Error: {$info[2]} SQL=$sql");
			return -1;
		}else{
			return true;
		}
	}
	
	function GetPanelConnections(){
		$this->MakeSafe();
		$sql="SELECT * FROM fac_PatchConnection WHERE PanelDeviceID=$this->PanelDeviceID ORDER BY PanelPortNumber;";
		
		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->PanelDeviceID;
		$tmpDev->GetDevice();
		$connList=array();
		
		for ( $i = 1; $i <= $tmpDev->Ports; $i++ ) {
			$connList[$i] = new PatchConnection();
			$connList[$i]->PanelDeviceID = $tmpDev->DeviceID;
			$connList[$i]->PanelPortNumber = $i;
		}      
	
		foreach($this->query($sql) as $connRow){	
			$connNum=$connRow["PanelPortNumber"];
			$connList[$connNum]->PanelDeviceID=$connRow["PanelDeviceID"];
			$connList[$connNum]->PanelPortNumber=$connRow["PanelPortNumber"];
			$connList[$connNum]->FrontEndpointDeviceID=$connRow["FrontEndpointDeviceID"];
			$connList[$connNum]->FrontEndpointPort=$connRow["FrontEndpointPort"];
			$connList[$connNum]->RearEndpointDeviceID=$connRow["RearEndpointDeviceID"];
			$connList[$connNum]->RearEndpointPort=$connRow["RearEndpointPort"];
			$connList[$connNum]->FrontNotes=$connRow["FrontNotes"];
			$connList[$connNum]->RearNotes=$connRow["RearNotes"];
		}
		
		return $connList;
	}
	
	function GetEndpointConnections(){
		$this->MakeSafe();
		$sql="SELECT * FROM fac_PatchConnection WHERE FrontEndpointDeviceID=$this->FrontEndpointDeviceID ORDER BY PanelDeviceID ASC;";
		
		$patchList=array();
		foreach($this->query($sql) as $row){
			$pNum=sizeof($patchList);
			$patchList[$pNum]=new PatchConnection();
			$patchList[$pNum]->PanelDeviceID=$row["PanelDeviceID"];
			$patchList[$pNum]->PanelPortNumber=$row["PanelPortNumber"];
			$patchList[$pNum]->FrontEndpointDeviceID=$row["FrontEndpointDeviceID"];
			$patchList[$pNum]->FrontEndpointPort=$row["FrontEndpointPort"];
			$patchList[$pNum]->RearEndpointDeviceID=$row["RearEndpointDeviceID"];
			$patchList[$pNum]->RearEndpointPort=$row["RearEndpointPort"];
			$patchList[$pNum]->FrontNotes=$row["FrontNotes"];
			$patchList[$pNum]->RearNotes=$row["RearNotes"];
		}
		
		return $patchList;
	}

	function MakeSafe(){
		$this->PanelDeviceID=intval($this->PanelDeviceID);
		$this->PanelPortNumber=intval($this->PanelPortNumber);
		$this->FrontEndpointDeviceID=(is_null($this->FrontEndpointDeviceID))?'NULL':intval($this->FrontEndpointDeviceID);
		$this->FrontEndpointPort=(is_null($this->FrontEndpointPort))?'NULL':intval($this->FrontEndpointPort);
		$this->FrontNotes=(is_null($this->FrontNotes))?'NULL':addslashes($this->FrontNotes);
		$this->RearEndpointDeviceID=(is_null($this->RearEndpointDeviceID))?'NULL':intval($this->RearEndpointDeviceID);
		$this->RearEndpointPort=(is_null($this->RearEndpointPort))?'NULL':intval($this->RearEndpointPort);
		$this->RearNotes=(is_null($this->RearNotes))?'NULL':addslashes($this->RearNotes);
	}	

}

class SwitchConnection {
	/* SwitchConnection:	Self explanatory - any device set as a switch will allow you to map out the port connections to
							any other device within the same data center.  For trans-data center connections, you can map the
							port back to itself, and list the external source in the Notes field.
	*/
	
	var $SwitchDeviceID;
	var $SwitchPortNumber;
	var $EndpointDeviceID;
	var $EndpointPort;
	var $Notes;

	function MakeSafe(){
		$this->SwitchDeviceID=intval($this->SwitchDeviceID);
		$this->SwitchPortNumber=intval($this->SwitchPortNumber);
		$this->EndpointDeviceID=intval($this->EndpointDeviceID);
		$this->EndpointPort=intval($this->EndpointPort);
		$this->Notes=addslashes(trim($this->Notes));
	}

	function CreateConnection($recursive = true ) {
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INFO fac_SwitchConnection SET SwitchDeviceID=$this->SwitchDeviceID, 
			SwitchPortNumber=$this->SwitchPortNumber, 
			EndpointDeviceID=$this->EndpointDeviceID, 
			EndpointPort=$this->EndpointPort, Notes=\"$this->Notes\";"; 

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("CreateConnection::PDO Error: {$info[2]} SQL=$sql");
			return -1;
		}

		$tmpDev=new Device();
		$tmpDev->DeviceID=$this->EndpointDeviceID;
		$tmpDev->GetDevice();
		
		if ( $recursive && $tmpDev->DeviceType == "Switch" ) {
			$tmpSw = new SwitchConnection();
			$tmpSw->SwitchDeviceID = $this->EndpointDeviceID;
			$tmpSw->SwitchPortNumber = $this->EndpointPort;
			$tmpSw->EndpointDeviceID = $this->SwitchDeviceID;
			$tmpSw->EndpointPort = $this->SwitchPortNumber;
			$tmpSw->Notes = $this->Notes;
			
			// Remove any existing connection from this port
			$tmpSw->RemoveConnection();
			// Call yourself, but with the recursive = false so that you don't create a loop
			$tmpSw->CreateConnection(false );
		}

		if ( $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPan = new PatchConnection();
			$tmpPan->PanelDeviceID = $this->EndpointDeviceID;
			$tmpPan->PanelPortNumber = $this->EndpointPort;
			$tmpPan->FrontEndpointDeviceID = $this->SwitchDeviceID;
			$tmpPan->FrontEndpointPort = $this->SwitchPortNumber;
			$tmpPan->FrontNotes = $this->Notes;
			$tmpPan->MakeFrontConnection(false );
		}
		
		return 1;
	}
  
	function UpdateConnection() {
		$sql = "update fac_SwitchConnection set EndpointDeviceID=\"" . intval( $this->EndpointDeviceID ) . "\", EndpointPort=\"" . intval( $this->EndpointPort ) . "\", Notes=\"" . addslashes( $this->Notes ) . "\" where SwitchDeviceID=\"" . intval( $this->SwitchDeviceID ) . "\" and SwitchPortNumber=\"" . intval( $this->SwitchPortNumber ) . "\"";
		$this->query($sql);

		$tmpDev = new Device();
		$tmpDev->DeviceID = intval($this->EndpointDeviceID);
		$tmpDev->GetDevice();
		
		if ( $tmpDev->DeviceType == "Switch" ) {
			$tmpSw = new SwitchConnection();
			$tmpSw->SwitchDeviceID = $this->EndpointDeviceID;
			$tmpSw->SwitchPortNumber = $this->EndpointPort;
			$tmpSw->EndpointDeviceID = $this->SwitchDeviceID;
			$tmpSw->EndpointPort = $this->SwitchPortNumber;
			$tmpSw->Notes = $this->Notes;
			
			// Remove any existing connection from this port
			$tmpSw->RemoveConnection();
			// Call yourself, but with the recursive = false so that you don't create a loop
			$tmpSw->CreateConnection(false );
		}

		if ( $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPan = new PatchConnection();
			$tmpPan->PanelDeviceID = $this->EndpointDeviceID;
			$tmpPan->PanelPortNumber = $this->EndpointPort;
			$tmpPan->FrontEndpointDeviceID = $this->SwitchDeviceID;
			$tmpPan->FrontEndpointPort = $this->SwitchPortNumber;
			$tmpPan->FrontNotes = $this->Notes;
			$tmpPan->MakeFrontConnection(false );
		}
	}
	
	function GetConnectionRecord() {
		global $dbh;
		
		$sql = sprintf( "select * from fac_SwitchConnection where SwitchDeviceID=%d and SwitchPortNumber=%d", intval( $this->SwitchDeviceID), intval( $this->SwitchPortNumber ) );
			
		if ( ! $row =$dbh->query( $sql )->fetch() ) {
			return false;
		}

		$this->EndpointDeviceID = $row["EndpointDeviceID"];
		$this->EndpointPort = $row["EndpointPort"];
		$this->Notes = $row["Notes"];
		
		return;	
	}
    
	function RemoveConnection( $recursive=false ) {
		$this->GetConnectionRecord();

		$delSQL = "delete from fac_SwitchConnection where SwitchDeviceID=\"" . $this->SwitchDeviceID . "\" and SwitchPortNumber=\"" . $this->SwitchPortNumber . "\"";
		$result=$this->exec($delSQL);

		$tmpDev = new Device();
		$tmpDev->DeviceID = intval($this->EndpointDeviceID);
		$tmpDev->GetDevice();

		if ( $tmpDev->DeviceType == "Switch" && $recursive) {
			$sql = sprintf( "delete from fac_SwitchConnection where SwitchDeviceID=%d and SwitchPortNumber=%d", $this->EndpointDeviceID, $this->EndpointPort );
			$result=$this->exec($sql);
		}

		if ( $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPan = new PatchConnection();
			$tmpPan->PanelDeviceID = $this->EndpointDeviceID;
			$tmpPan->PanelPortNumber = $this->EndpointPort;
			$tmpPan->RemoveFrontConnection(false );
		}

		return $result;
	}
  
	function DropEndpointConnections() {
		global $dbh;

		$this->MakeSafe();

		$sql="DELETE FROM fac_SwitchConnection WHERE EndpointDeviceID=$this->EndpointDeviceID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("DropEndpointConnections::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			return true;
		}
	}
  
	function DropSwitchConnections() {
		global $dbh;

		$this->MakeSafe();

		$sql="DELETE FROM fac_SwitchConnection WHERE SwitchDeviceID=$this->EndpointDeviceID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("DropSwitchConnections::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			return true;
		}
	}

	function GetSwitchConnections() {
		$selectSQL = "select * from fac_SwitchConnection where SwitchDeviceID=\"" . $this->SwitchDeviceID . "\" order by SwitchPortNumber";

		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->SwitchDeviceID;
		$tmpDev->GetDevice();

		for ( $i = 1; $i <= $tmpDev->Ports; $i++ ) {
			$connList[$i] = new SwitchConnection();
			$connList[$i]->SwitchDeviceID = $tmpDev->DeviceID;
			$connList[$i]->SwitchPortNumber = $i;
		}      

		foreach($this->query($selectSQL) as $connRow){
			$connNum = $connRow["SwitchPortNumber"];
			$connList[$connNum]->SwitchDeviceID = $connRow["SwitchDeviceID"];
			$connList[$connNum]->SwitchPortNumber = $connRow["SwitchPortNumber"];
			$connList[$connNum]->EndpointDeviceID = $connRow["EndpointDeviceID"];
			$connList[$connNum]->EndpointPort = $connRow["EndpointPort"];
			$connList[$connNum]->Notes = $connRow["Notes"];
		}

		return $connList;
	}
  
	function GetSwitchPortConnector() {
		$selectSQL = "select * from fac_SwitchConnection where SwitchDeviceID=\"" . $this->SwitchDeviceID . "\" and SwitchPortNumber=\"" . $this->SwitchPortNumber . "\"";

		foreach($this->query($selectSQL) as $row){
			$this->EndpointDeviceID = $row["EndpointDeviceID"];
			$this->EndpointPort = $row["EndpointPort"];
			$this->Notes = $row["Notes"];
		}

		return;
	}
  
	function GetEndpointConnections() {
		$selectSQL = "select * from fac_SwitchConnection where EndpointDeviceID=\"" . $this->EndpointDeviceID . "\" order by EndpointPort";

		$connList = array();
		foreach($this->query($selectSQL) as $row){
			$numConnects = sizeof( $connList );

			$connList[$numConnects] = new SwitchConnection();
			$connList[$numConnects]->SwitchDeviceID = $connRow["SwitchDeviceID"];
			$connList[$numConnects]->SwitchPortNumber = $connRow["SwitchPortNumber"];
			$connList[$numConnects]->EndpointDeviceID = $connRow["EndpointDeviceID"];
			$connList[$numConnects]->EndpointPort = $connRow["EndpointPort"];
			$connList[$numConnects]->Notes = $connRow["Notes"];
		}

		return $connList;
	}  
}

	if(isset($_POST['DeviceID']) && isset($_POST['power'])){
		if(isset($_POST['con']) && isset($_POST['pduid'])){
			$pwrConnection=new PowerConnection();
			$pwrConnection->DeviceID=$_POST['DeviceID'];
			$pwrConnection->PDUID=$_POST['pduid'];
			$pwrConnection->PDUPosition=$_POST['con'];
			$pwrConnection->DeviceConnNumber=$_POST['power'];
			if(isset($_POST['e'])){
				$pwrConnection->CreateConnection();
			}else{
				$pwrConnection->RemoveConnection();
			}
			echo 'ok';
		}else{
			$dev=new Device();
			$pwrConnection=new PowerConnection();
			$pdu=new PowerDistribution();

			$dev->DeviceID=$_POST['DeviceID'];
			$dev->GetDevice();

			$pwrConnection->DeviceID=($dev->ParentDevice>0)?$dev->ParentDevice:$dev->DeviceID;
			$pwrCords=$pwrConnection->GetConnectionsByDevice();

			print "<span>Server Name: $dev->Label</span><span># Power Supplies: $dev->PowerSupplyCount</span><div class=\"table border\">\n			<div><div>".__("Power Strip")."</div><div>".__("Plug #")."</div><div>".__("Power Supply")."</div></div>";
			foreach($pwrCords as $cord){
				$pdu->PDUID=$cord->PDUID;
				$pdu->GetPDU();
				print "			<div><div data=\"$pdu->PDUID\"><a href=\"power_pdu.php?pduid=$pdu->PDUID\">$pdu->Label</a></div><div><a href=\"power_connection.php?pdu=$pdu->PDUID&conn=$cord->PDUPosition\">$cord->PDUPosition</a></div><div".(($cord->DeviceConnNumber==$_POST['power'])?' class="bold"':' class="disabled"').">$cord->DeviceConnNumber</div></div>\n";
			}
			print "</div>";
		}
		exit;
	}


	if(isset($_POST['EndpointDeviceID'])){
		$networkPatches=new SwitchConnection();
		$networkPatches->EndpointDeviceID=$_POST['EndpointDeviceID'];
		if(isset($_POST['SwitchDeviceID']) && isset($_POST['SwitchPortNumber'])){
			$networkPatches->SwitchDeviceID=$_POST['SwitchDeviceID'];
			$networkPatches->SwitchPortNumber=$_POST['SwitchPortNumber'];
			if(isset($_POST['EndpointPort'])){ // Update Connection
				$networkPatches->GetSwitchPortConnector();
				$networkPatches->EndpointPort=$_POST['EndpointPort'];
				$networkPatches->UpdateConnection();
				print "ok";
			}else{ // Delete Connection
				$networkPatches->RemoveConnection();
				print "ok";
			}
		}else{
			$patchList=$networkPatches->GetEndpointConnections();
			$tmpDev=new Device();
			$tmpDev->DeviceID=$networkPatches->EndpointDeviceID;
			$tmpDev->GetDevice();

			print "<span>Server Name: <a href=\"devices.php?DeviceID=$tmpDev->DeviceID\">$tmpDev->Label</a></span><span># Data Ports: $tmpDev->Ports</span><div class=\"table border\">\n				<div><div>".__("Switch")."</div><div>".__("Switch Port")."</div><div>".__("Device Port")."</div><div>".__("Notes")."</div></div>\n";

				foreach($patchList as $patchConn){
					$tmpDev->DeviceID=$patchConn->SwitchDeviceID;
					$tmpDev->GetDevice();
					print "\t\t\t\t<div><div data=\"$patchConn->SwitchDeviceID\"><a href=\"devices.php?DeviceID=$patchConn->SwitchDeviceID\">$tmpDev->Label</a></div><div><a href=\"changepatch.php?switchid=$patchConn->SwitchDeviceID&portid=$patchConn->SwitchPortNumber\">$patchConn->SwitchPortNumber</a></div><div>$patchConn->EndpointPort</div><div>$patchConn->Notes</div></div>\n";
				}
			print "</div><!-- END div.table -->\n";
		}
		exit;
	}



	$body="";
	$conflicts=0;
	// This will only have a conflict if someone hand entered data.  These will be unique cases that we should look at by hand.
	$sql="SELECT PDUID, CONCAT(PDUID,'-',PDUPosition) AS KEY1, COUNT(PDUID) AS Count  FROM fac_PowerConnection GROUP BY KEY1 HAVING (COUNT(KEY1)>1) ORDER BY PDUID ASC;";
	$sth=$dbh->prepare($sql);$sth->execute();
	if($sth->rowCount()>0){
		$body.="<p>This is a problem that will need a custom fix.  Please email the output below to wilbur@wilpig.org</p>";
		foreach($sth as $row){
			$body.=print_r($row, TRUE);
		}
		$conflicts+=0;
	}else{
		$body.="<p>No collisions detected for Power Connections (PDUID,PDUPosition)</p>\n";
		$conflicts+=1;
	}

	$sql="SELECT DeviceID, CONCAT(DeviceID,'-',DeviceConnNumber) AS KEY2, DeviceConnNumber, COUNT(DeviceID) AS Count FROM fac_PowerConnection GROUP BY KEY2 HAVING (COUNT(KEY2)>1) ORDER BY DeviceID ASC;";
	$sth=$dbh->prepare($sql);$sth->execute();
	if($sth->rowCount()>0){
		$body.="<p>The list below are devices that have multiple connections to the same power supply.</p>";
		$body.='<div class="table border power"><div><div>DeviceID</div><div>KEY2</div><div>Count</div></div>';
		foreach($sth as $row){
			$body.="<div><div>{$row['DeviceID']}</div><div data={$row['DeviceConnNumber']}>{$row['KEY2']}</div><div>{$row['Count']}</div></div>";
		}
		$body.='</div>';
		$conflicts+=0;
	}else{
		$body.="No collisions detected for Power Connections (DeviceID,DeviceConnNumber)<br>\n";
		$conflicts+=1;
	}

	// Check for duplicated switch ports same as initial power check this should only have a conflict and hand altered data.
	$sql="SELECT SwitchDeviceID, CONCAT(SwitchDeviceID,'-',SwitchPortNumber) AS KEY1, COUNT(SwitchDeviceID) AS Count FROM fac_SwitchConnection GROUP BY KEY1 HAVING (COUNT(KEY1)>1) ORDER BY SwitchDeviceID ASC;";
	$sth=$dbh->prepare($sql);$sth->execute();
	if($sth->rowCount()>0){
		$body.="<p>This is a problem that will need a custom fix.  Please email the output below to wilbur@wilpig.org</p>";
		foreach($sth as $row){
			$body.=print_r($row, TRUE);
		}
		$conflicts+=0;
	}else{
		$body.="<p>No collisions detected for Switch Connections (SwitchDeviceID,SwitchPortNumber)</p>\n";
		$conflicts+=1;
	}


	$sql="SELECT SwitchDeviceID, SwitchPortNumber, EndpointDeviceID, EndpointPort, CONCAT(EndpointDeviceID,'-',EndpointPort) AS KEY2, COUNT(EndpointDeviceID) AS Count FROM fac_SwitchConnection GROUP BY KEY2 HAVING (COUNT(KEY2)>1) ORDER BY EndpointDeviceID ASC;";
	$sth=$dbh->prepare($sql);$sth->execute();
	if($sth->rowCount()>0){
		$body.="<p>The list below are devices that have multiple connections to the same network card.</p>";
		$body.='<div class="table border network"><div><div>DeviceID</div><div>KEY2</div><div>Count</div></div>';
		foreach($sth as $row){
			$body.="<div><div>{$row['EndpointDeviceID']}</div><div>{$row['KEY2']}</div><div data=\"{$row['EndpointPort']}\">{$row['Count']}</div></div>";
		}
		$body.='</div>';
		$conflicts+=0;
	}else{
		$body.="<p>No collisions detected for Switch Connections (EndpointDeviceID,EndpointPort)</p>\n";
		$conflicts+=1;
	}

	if($conflicts==4){
			header('Location: '.redirect("install.php"));
			exit;
	}

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		$('.power > div:first-child ~ div, .network > div:first-child ~ div').each(function(){
			$(this).append('<div>edit</div>');
		});
		$('.power > div:first-child ~ div > div:last-child').each(function(){
			var editbox=$(this);
			var devid=$(this).prev().prev().prev().text();
			var ps=$(this).prev().prev().attr('data');
			$(this).click(function(){
				$.ajax({
					type: 'POST',
					url: 'conflicts.php',
					data: 'DeviceID='+devid+'&power='+ps,
					success: function(edit){
						editbox.unbind('click');
						var pduid;
						var con;
						// get an edit form
						editbox.html(edit);
						editbox.find('.table > div:first-child ~ div > div:last-child').each(function(){
							if($(this).hasClass('bold')){
								var p=$(this).prev();
								var row=$(this).parent();
								$(this).after('<div>Edit</div>');
								$(this).next().click(function(){
									$(this).prev().html('<input value="'+$(this).prev().text()+'"></input>');
									$(this).prev().children('input').change(function(){
										// issue ajax update command then change this back from an input to plain text
										var par=$(this).parent();
										pduid=p.prev().attr('data');
										con=p.text();
										ps=$(this).val();
										$.ajax({
											type: 'POST',
											url: 'conflicts.php',
											data: 'DeviceID='+devid+'&power='+ps+'&pduid='+pduid+'&con='+con+'&e=1',
											success: function(data){
												if(data=='ok'){
													par.text(ps);
												//	row.remove();
												}
											}
										}); 
									});
								});
							}
						});
						editbox.find('.table > div:first-child ~ div > div:last-child').each(function(){
							if($(this).prev().hasClass('bold')){
								var p=$(this).prev();
								var row=$(this).parent();
								$(this).after('<div>Delete</div>');
								$(this).next().click(function(){
									pduid=p.prev().prev().attr('data');
									con=p.prev().text();
									$.ajax({
										type: 'POST',
										url: 'conflicts.php',
										data: 'DeviceID='+devid+'&power='+ps+'&pduid='+pduid+'&con='+con,
										success: function(data){
											if(data=='ok'){
												row.remove();
											}
										}
									});
								});
							}
						});
					}
				});
			});
		});
		$('.network > div:first-child ~ div > div:last-child').each(function(){
			var editbox=$(this);
			var devid=$(this).prev().prev().prev().text();
			var dp=$(this).prev().attr('data');
			$(this).click(function(){
				$.ajax({
					type: 'POST',
					url: 'conflicts.php',
					data: 'EndpointDeviceID='+devid,
					success: function(edit){
						editbox.unbind('click');
						var sw;
						var sp;
						// get an edit form
						editbox.html(edit);
						editbox.find('.table > div:first-child ~ div > div:nth-child(3)').each(function(){
							var nic=$(this);
							if(nic.text()==dp){
								var row=$(this).parent();
								row.append('<div>Edit</div>');
								$(this).next().next().click(function(){
									sw=row.find('div:first-child').attr('data');
									sp=$(this).prev().prev().prev().text();
									nic.html('<input value="'+nic.text()+'"></input>');
									nic.children('input').change(function(){
										var change=$(this).val();
										$.ajax({
											type: 'POST',
											url: 'conflicts.php',
											data: 'EndpointDeviceID='+devid+'&SwitchDeviceID='+sw+'&SwitchPortNumber='+sp+'&EndpointPort='+$(this).val(),
											success: function(data){
												if(data=='ok'){
													nic.text(change);
												}
											}
										});
									});
								});
							}
						});
						editbox.find('.table > div:first-child ~ div > div:nth-child(3)').each(function(){
							if($(this).text()==dp){
								var row=$(this).parent();
								row.append('<div>Delete</div>');
								row.find('div:last-child').click(function(){
									sw=row.find('div:first-child').attr('data');
									sp=row.find('div:nth-child(2)').text();
									$.ajax({
										type: 'POST',
										url: 'conflicts.php',
										data: 'EndpointDeviceID='+devid+'&SwitchDeviceID='+sw+'&SwitchPortNumber='+sp,
										success: function(data){
											if(data=='ok'){
												row.remove();
											}
										}
									});
								});
							}
						});
					}
				});
			});
		});
	});
</script>
<style type="text/css">
div.table > div > div {vertical-align: top;}
.bold {font-weight: bold;}
.disabled {background-color: lightGrey; font-style: italic;}
.disabled:after {
	content: " - ok";
}
.center > div > p { max-width: 400px;}
.center > div > hr ~ p { display: list-item; }
</style>

</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<div class="center"><div>
<p>The tables below show devices that are currently sharing resources and will need to be resolved before the new database update can be applied.</p>
<p>The Key in each table is made up of the DeviceID and the ID of resource that is currently being shared incorrectly.</p>
<p>Click &quot;edit&quot; in each row to display the records that are in conflict.  Either use the word &quot;Delete&quot; to remove the connection outright or use the &quot;Edit&quot; option to change the value in the box.</p>
<p>After you have finished making changes <a href="conflicts.php">reload this page</a> until there are no conflicts remaing.  It will then automatically put you back to the installer and finish applying the update.</p>
<hr>


<?php echo $body; ?>
<!-- CONTENT GOES HERE -->



</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
