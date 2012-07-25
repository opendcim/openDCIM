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
	function Rebuild ($db){
/* Rebuild: This function should only be needed after something like the version erasing glitch from 1.1 and 1.2.
			At this time it is possible to get unwanted duplicate configuration parameters and this will clean
			them.

			I am not sanitizing input here because it should have no user interaction.  Read from the db, flush
			db, write unique values back to the db.
*/
		$sql='select * from fac_Config';
		$result=mysql_query($sql,$db);

		$uniqueconfig=array();

		// Build array of unique config parameters
		while ($row=mysql_fetch_array($result)){
			if(isset($uniqueconfig[$row['Parameter']]['Value'])){
				// if value in the array is equal to the default value AND the current value is different from the value in the array update the value in the array
				if($uniqueconfig[$row['Parameter']]['Value']==$row['DefaultVal'] && $uniqueconfig[$row['Parameter']]['Value']!=$row['Value']){
					$uniqueconfig[$row['Parameter']]['Value']=$row['Value'];
				}
			}else{
				// value wasn't set in the array so we'll take whatever we're given even if it is the default value
				$uniqueconfig[$row['Parameter']]['Value']=$row['Value'];
			}
			// the following aren't user configurable so no need to check for differences
			$uniqueconfig[$row['Parameter']]['UnitOfMeasure']=$row['UnitOfMeasure'];
			$uniqueconfig[$row['Parameter']]['ValType']=$row['ValType'];
			$uniqueconfig[$row['Parameter']]['DefaultVal']=$row['DefaultVal'];
		}

		// Empty config table
		mysql_query('TRUNCATE TABLE fac_Config;',$db);

		// Rebuild config table from cleaned array
		foreach($uniqueconfig as $key => $row){
			mysql_query("INSERT INTO fac_Config VALUES ('$key','{$row['Value']}','{$row['UnitOfMeasure']}','{$row['ValType']}','{$row['DefaultVal']}');",$db); 
		}
	}
}
?>
