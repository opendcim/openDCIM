<?php

class Config{
	var $ParameterArray;
	var $defaults;
	
	function Config ($db){
		//Get parameter value pairs from fac_Config
		$sql='select Parameter, Value, DefaultVal from fac_Config';
		$result=mysql_query($sql,$db);
		
		while ($row=mysql_fetch_array($result)){
			if ($row['Parameter']== 'ClassList'){
				$List = explode(', ', $row['Value']);
				$this->ParameterArray[$row['Parameter']]=$List;
				$this->defaults[$row['Parameter']]=$row['DefaultVal'];
			}else{
				$this->ParameterArray[$row['Parameter']]=$row['Value'];
				$this->defaults[$row['Parameter']]=$row['DefaultVal'];
			}
		}
		return;
	}

	function UpdateConfig($db){
		foreach($this->ParameterArray as $key=>$value){
			if ($key=='ClassList'){
				$numItems=count($value);
				$i=0;
				$valueStr='';
				foreach($value as $item){
					$valueStr .= $item;
					if($i+1 != $numItems){
						$valueStr.=', ';
					}
					$i++;
				}
					
				$sql='update fac_Config set Value=\''.addslashes($valueStr).'\' where Parameter=\''.$key.'\'';
				$result=mysql_query($sql,$db);
			}else{
				$sql='update fac_Config set Value=\''.addslashes($value).'\' where Parameter=\''.$key.'\'';
				$result=mysql_query($sql,$db);
			}
		}
		return;
	}
	
	static function RevertToDefault($db, $parameter){
		if ($parameter=='none'){
			$sql='update fac_Config set Value=DefaultVal';
			}
		else{
			$sql='update fac_Config set Value=DefaultVal where Parameter = \''.$parameter.'\'';
			}
		$result=mysql_query($sql,$db);
		return;
	}
		
				
}

?>
