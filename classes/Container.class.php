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
class Container {
	var $ContainerID;
	var $Name;
	var $ParentID;
	var $DrawingFileName;
	var $MapX;
	var $MapY;

	function MakeSafe(){
		$this->ContainerID=intval($this->ContainerID);
		$this->Name=sanitize($this->Name);
		$this->ParentID=intval($this->ParentID);
		$this->DrawingFileName=sanitize($this->DrawingFileName);
		$this->MapX=abs($this->MapX);
		$this->MapY=abs($this->MapY);
	}
	
	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
		$this->DrawingFileName=stripslashes($this->DrawingFileName);
	}

	static function RowToObject($row){
		$container=new Container();
		$container->ContainerID=$row["ContainerID"];
		$container->Name=$row["Name"];
		$container->ParentID=$row["ParentID"];
		$container->DrawingFileName=$row["DrawingFileName"];
		$container->MapX=$row["MapX"];
		$container->MapY=$row["MapY"];
		$container->MakeDisplay();

		return $container;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function CreateContainer() {
		global $dbh;
		
		$this->MakeSafe();
		$sql="INSERT INTO fac_Container set Name=\"$this->Name\", ParentID=$this->ParentID, 
				DrawingFileName=\"$this->DrawingFileName\", MapX=$this->MapX, MapY=$this->MapY;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		} else {
			$this->ContainerID = $dbh->lastInsertID();
		}

		updateNavTreeHTML();
				
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->ContainerID;
	}

	function UpdateContainer(){
		$this->MakeSafe();

		$oldcontainer=new Container();
		$oldcontainer->ContainerID=$this->ContainerID;
		$oldcontainer->GetContainer();

		$sql="UPDATE fac_Container SET Name=\"$this->Name\", ParentID=$this->ParentID, 
			DrawingFileName=\"$this->DrawingFileName\", MapX=$this->MapX, MapY=$this->MapY 
			WHERE ContainerID=$this->ContainerID;";
		
		if(!$this->query($sql)){
			updateNavTreeHTML();
				
			return false;
		}else{
			updateNavTreeHTML();			

			(class_exists('LogActions'))?LogActions::LogThis($this,$oldcontainer):'';
			return true;
		}
	}

	function DeleteContainer(){
		global $dbh;
		$this->MakeSafe();
		
		$ChildContainerList=$this->GetChildContainerList();
		foreach($ChildContainerList as $cRow){
			$cRow->ParentID=0;
			$cRow->UpdateContainer();
		}
		$ChildDCList=$this->GetChildDCList();
		foreach($ChildDCList as $dcRow){
			$dcRow->ContainerID=0;
			$dcRow->UpdateDataCenter();
		}

		// Now delete the container itself
		$sql="DELETE FROM fac_Container WHERE ContainerID=$this->ContainerID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		updateNavTreeHTML();
				
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return;
	}
	
	function GetContainer(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Container WHERE ContainerID=$this->ContainerID 
			ORDER BY LENGTH(Name), Name ASC;";

		if($row=$this->query($sql)->fetch()){
			foreach(Container::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}

	function GetChildren(){
		$children=array();
		foreach($this->GetChildContainerList() as $con){
			$children[]=$con;
		}
		foreach($this->GetChildDCList() as $dc){
			$children[]=$dc;
		}

		return $children;
	}

	function GetChildContainerList(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Container WHERE ParentID=$this->ContainerID 
			ORDER BY Name ASC, LENGTH(Name);";

		$containerList=array();
		foreach($this->query($sql) as $row){
			$containerList[]=Container::RowToObject($row);
		}

		return $containerList;
	}

	function GetChildDCList(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_DataCenter WHERE ContainerID=$this->ContainerID 
			ORDER BY Name ASC;";

		$datacenterList=array();
		foreach($this->query($sql) as $row){
			$datacenterList[$row["DataCenterID"]]=DataCenter::RowToObject($row);
		}

		return $datacenterList;
	}
		
	function BuildMenuTree() {
		$c=new Container();
		//begin the tree
		$tree="\n<ul class=\"mktree\" id=\"datacenters\">\n";;
		//Add root children
		$tree.=$c->AddContainerToTree();
		$tree.="<li id=\"dc-1\"><a href=\"storageroom.php\">".__("General Storage Room")."</a></li>\n";
		$tree.="</ul>\n";

		return $tree;
	}

	private function AddContainerToTree($lev=0) {
		$tree="";
		$container_opened=false;
		
		if($this->GetContainer()){
			$lev++;
			$tree.=str_repeat(" ",$lev)."<li class=\"liOpen\" id=\"c$this->ContainerID\">"
				."<a class=\"CONTAINER\" href=\"container_stats.php?container=$this->ContainerID\">"
				."$this->Name</a>\n";
			$lev++;
			$tree.=str_repeat(" ",$lev)."<ul>\n";
			$container_opened=true;
		}
		
		$cList=$this->GetChildContainerList();
		$lev++;
		if(count($cList) >0){
			while(list($cID,$container)=each($cList)){
				$tree.=$container->AddContainerToTree($lev);
			}
		}
		
		$dcList=$this->GetChildDCList();

		if(count($dcList) >0){
			while(list($dcID,$datacenter)=each($dcList)){
				$tree.=$datacenter->AddDCToTree($lev);
			} //DC
		}

		if ($container_opened){
			
			$tree.=str_repeat(" ",$lev-1)."</ul>\n";
			$tree.=str_repeat(" ",$lev-2)."</li>\n";
		}
		
		return $tree;
	}

    /**
     * Returns the maximum level of containers.
     * 
     * @param int $level
     * @return int
     */
    public function computeMaxLevel($level = 0)
    {
        $this->GetContainer();
        $maxLevel = $level;
        $containerChildList = $this->GetChildContainerList();
        if (count($containerChildList) > 0) {
            $level++;
            foreach ($containerChildList as $cName => $childContainer) {
                $retval = $childContainer->computeMaxLevel($level);
                $maxLevel = max(array($retval, $maxLevel));
            }
        }
        return $maxLevel;
    }

	function MakeContainerImage(){
		global $config;
		$mapHTML="";
		$mapfile="";
		$tam=50;
	 
		if ( strlen($this->DrawingFileName) > 0 ) {
			$mapfile = $config->ParameterArray['drawingpath'] . $this->DrawingFileName;
		}
	   
		if ( file_exists( $mapfile ) ) {
			if(mime_content_type($mapfile)=='image/svg+xml'){
				$svgfile = simplexml_load_file($mapfile);
				$width = substr($svgfile['width'],0,4);
				$height = substr($svgfile['height'],0,4);
			}else{					
				list($width, $height, $type, $attr)=getimagesize($mapfile);
			}
			$mapHTML.="<div style='position:relative;'>\n";
			$mapHTML.="<img src=\"$mapfile\" width=\"$width\" height=\"$height\" alt=\"Container Image\">\n";
			
			$cList=$this->GetChildContainerList();
			if ( count( $cList ) > 0 ) {
				while ( list( $cID, $container ) = each( $cList ) ) {
					if (is_null($container->MapX) || $container->MapX==0 
						|| is_null($container->MapY) || $container->MapY==0 ){
						$mapHTML.="<div>\n";
						$mapHTML.="<a title=\"$container->Name\" href=\"container_stats.php?container=$container->ContainerID\">";
						$mapHTML.="<br><div style='background-color: #dcdcdc;'>$container->Name</div></a>";
						$mapHTML.= "</div>\n";
						}
					else {
						$mapHTML.="<div style='position:absolute; top:".($container->MapY-$tam/2)."px; left:".($container->MapX-$tam/2)."px;'>\n";
						$mapHTML.="<a title=\"$container->Name\" href=\"container_stats.php?container=$container->ContainerID\">";
						$mapHTML.="<img src=\"images/Container.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
						$mapHTML.="<div style='position:absolute; top:".($container->MapY+$tam/2)."px; left:".($container->MapX-$tam/2)."px; background-color: #dcdcdc;'>";
						$mapHTML.="<a title=\"$container->Name\" href=\"container_stats.php?container=$container->ContainerID\">";
						$mapHTML.= $container->Name."</a></div>";
					}
				}
			}
			
			$dcList=$this->GetChildDCList();
			if ( count( $dcList ) > 0 ) {
				while ( list( $dcID, $dc ) = each( $dcList ) ) {
					if (is_null($dc->MapX) || $dc->MapX==0 
						|| is_null($dc->MapY) || $dc->MapY==0 ){
						$mapHTML.="<div>\n";
						$mapHTML.="<a title=\"".$dc->Name."\" href=\"dc_stats.php?dc=".$dcID."\">";
						$mapHTML.=$dc->Name."</a>";
						$mapHTML.= "</div>\n";
					}
					else{
						$mapHTML.="<div style='position:absolute; top:".($dc->MapY-$tam/2)."px; left:".($dc->MapX-$tam/2)."px;'>\n";
						$mapHTML.="<a title=\"".$dc->Name."\" href=\"dc_stats.php?dc=".$dcID."\">";
						$mapHTML.="<img src=\"images/DC.png\" width=$tam height=$tam alt=\"Datacenter\"></a>\n</div>\n";
						$mapHTML.="<div style='position:absolute; top:".($dc->MapY+$tam/2)."px; left:".($dc->MapX-$tam/2)."px; background-color: #dcdcdc;'>";
						$mapHTML.="<a title=\"".$dc->Name."\" href=\"dc_stats.php?dc=".$dcID."\">";
						$mapHTML.=$dc->Name."</a></div>";
					}
				}
			}
			
			$mapHTML .= "</div>\n";
	    }
	    return $mapHTML;
	}

	function MakeContainerMiniImage($tipo="",$id=0) {
		global $config;
		$mapHTML = "";
		$mapfile="";
		$tam=50;
		$red=.5;
		$tam*=$red;
		$yo_ok=false;
	 
		if ( strlen($this->DrawingFileName) > 0 ) {
			$mapfile = $config->ParameterArray['drawingpath'] . $this->DrawingFileName;
		}
	   
		if ( file_exists( $mapfile ) ) {
			if(mime_content_type($mapfile)=='image/svg+xml'){
				$svgfile = simplexml_load_file($mapfile);
				$width = substr($svgfile['width'],0,4);
				$height = substr($svgfile['height'],0,4);
			}else{
				list($width, $height, $type, $attr)=getimagesize($mapfile);
			}
			$mapHTML.="<div style='position:relative;'>\n";
			$mapHTML.="<img id='containerimg' src=\"".$mapfile."\" width=\"".($width*$red)."\" height=\"".($height*$red)."\" 
					 onclick='coords(event)' alt=\"Container Image\">\n";
			
			$cList=$this->GetChildContainerList();
			if ( count( $cList ) > 0 ) {
				while ( list( $cID, $container ) = each( $cList ) ) {
					if ((is_null($container->MapX) || $container->MapX<0 || $container->MapX>$width 
						|| is_null($container->MapY) || $container->MapY<0 || $container->MapY>$height)
						&& $tipo=="container" && $id==$container->ContainerID ){
							$mapHTML.="<div id='yo' hidden style='position:absolute;'>\n";
							$mapHTML.="<img src=\"images/Container.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
							$yo_ok=true;
						}
					else {
						
						if ($tipo=="container" && $id==$container->ContainerID) {
							$mapHTML.="<div id='yo' style='position:absolute; top:".($container->MapY*$red-$tam/2)."px; left:".($container->MapX*$red-$tam/2)."px;'>\n";
							$mapHTML.="<img src=\"images/Container.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
							$yo_ok=true;
						}
						else {
							$mapHTML.="<div style='position:absolute; top:".($container->MapY*$red-$tam/2)."px; left:".($container->MapX*$red-$tam/2)."px;'>\n";
							$mapHTML.="<img src=\"images/ContainerGris.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
						}
					}
				}
			}
			
			$dcList=$this->GetChildDCList();
			if ( count( $dcList ) > 0 ) {
				while ( list( $dcID, $dc ) = each( $dcList ) ) {
					if ((is_null($dc->MapX) || $dc->MapX<0 || $dc->MapX>$width
						|| is_null($dc->MapY) || $dc->MapY<0 || $dc->MapY>$height)
						&& $tipo=="dc" && $id==$dcID){
							$mapHTML.="<div id='yo' hidden style='position:absolute;'>\n";
							$mapHTML.="<img src=\"images/DC.png\" width=$tam height=$tam alt=\"Datacenter\">\n</div>\n";
							$yo_ok=true;
						}
					else{
						if ($tipo=="dc" && $id==$dcID){
							$mapHTML.="<div id='yo' style='position:absolute; top:".($dc->MapY*$red-$tam/2)."px; left:".($dc->MapX*$red-$tam/2)."px;'>\n";
							$mapHTML.="<img src=\"images/DC.png\" width=$tam height=$tam alt=\"Datacenter\">\n</div>\n";
							$yo_ok=true;
						}
						else {
							$mapHTML.="<div style='position:absolute; top:".($dc->MapY*$red-$tam/2)."px; left:".($dc->MapX*$red-$tam/2)."px;'>\n";
							$mapHTML.="<img src=\"images/DCGris.png\" width=$tam height=$tam alt=\"Datacenter\">\n</div>\n";
						}
					}
				}
			}
			if (!$yo_ok){
				$mapHTML.="<div id='yo' hidden style='position:absolute;'>\n";
				if ($tipo=="container")
					$mapHTML.="<img src=\"images/Container.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
				else
					$mapHTML.="<img src=\"images/DC.png\" width=$tam height=$tam alt=\"Datacenter\">\n</div>\n";
			}
			
			$mapHTML .= "</div>\n";
	    }
	    return $mapHTML;
	}
	
	function GetContainerStatistics(){
		$this->GetContainer();

		$cStats["DCs"] = 0;
		$cStats["TotalU"] = 0;
		$cStats["Infrastructure"] = 0;
		$cStats["Occupied"] = 0;
		$cStats["Allocated"] = 0;
		$cStats["Available"] = 0;
		$cStats["SquareFootage"] = 0;
		$cStats["MaxkW"] = 0;
		$cStats["ComputedWatts"] = 0;
		$cStats["MeasuredWatts"] = 0;
		$cStats["TotalCabinets"] = 0;
		
		$dcList=$this->GetChildDCList();
		if(count($dcList) >0){
			while(list($dcID,$datacenter)=each($dcList)){
				$dcStats=$datacenter->GetDCStatistics();
				$cStats["DCs"]++;
				$cStats["TotalU"]+=$dcStats["TotalU"];
				$cStats["Infrastructure"]+=$dcStats["Infrastructure"];
				$cStats["Occupied"]+=$dcStats["Occupied"];
				$cStats["Allocated"]+=$dcStats["Allocated"];
				$cStats["Available"]+=$dcStats["Available"];
				$cStats["SquareFootage"]+=$datacenter->SquareFootage;
				$cStats["ComputedWatts"]+=$dcStats["ComputedWatts"];
				$cStats["MeasuredWatts"]+=$dcStats["MeasuredWatts"];
				$cStats["TotalCabinets"]+=$dcStats["TotalCabinets"];
				$cStats["MaxkW"]+=$datacenter->MaxkW;
			} 
		}
		
		$cList=$this->GetChildContainerList();
		if(count($cList) >0){
			while(list($cID,$container)=each($cList)){
				$childStats=$container->GetContainerStatistics();
				$cStats["DCs"]+=$childStats["DCs"];
				$cStats["TotalU"]+=$childStats["TotalU"];
				$cStats["Infrastructure"]+=$childStats["Infrastructure"];
				$cStats["Occupied"]+=$childStats["Occupied"];
				$cStats["Allocated"]+=$childStats["Allocated"];
				$cStats["Available"]+=$childStats["Available"];
				$cStats["SquareFootage"]+=$childStats["SquareFootage"];
				$cStats["ComputedWatts"]+=$childStats["ComputedWatts"];
				$cStats["MeasuredWatts"]+=$childStats["MeasuredWatts"];
				$cStats["TotalCabinets"]+=$childStats["TotalCabinets"];
				$cStats["MaxkW"]+=$childStats["MaxkW"];
			}
		}
		return $cStats;
	}
	
	static function GetContainerList($indexedbyid=false){
		global $dbh;

		$sql="SELECT * FROM fac_Container ORDER BY LENGTH(Name), Name ASC;";

		$containerList=array();
		foreach($dbh->query($sql) as $row){
			if($indexedbyid){
				$containerList[$row["ContainerID"]]=Container::RowToObject($row);
			}else{
				$containerList[]=Container::RowToObject($row);
			}
		}

		return $containerList;
	}
	
}
?>
