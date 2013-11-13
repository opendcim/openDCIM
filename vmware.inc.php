<?php
class ESX {
  var $vmID;
  var $vmName;
  var $vmState;
  
  function EnumerateVMs( $serverIP, $community ) {
	global $config;
    $vmList = array();
    
    $pollCommand = $config->ParameterArray["snmpwalk"]." -v 2c -c $community $serverIP .1.3.6.1.4.1.6876.2.1.1.2 | ".$config->ParameterArray["cut"]." -d: -f4 | /bin/cut -d\\\" -f2";
    exec( $pollCommand, $namesOutput );
    
    $pollCommand = $config->ParameterArray["snmpwalk"]." -v 2c -c $community $serverIP .1.3.6.1.4.1.6876.2.1.1.6 | ".$config->ParameterArray["cut"]." -d: -f4 | /bin/cut -d\\\" -f2";
    exec( $pollCommand, $statesOutput );
    
	if ( ( count( $namesOutput ) > 0 ) && ( count( $statesOutput ) > 0 ) ) {
		$tempVMs = array_combine( $namesOutput, $statesOutput );
		
		$vmID = 0;
		
		foreach( $tempVMs as $key => $value ) {
				$vmList[$vmID] = new ESX();
				$vmList[$vmID]->vmID = $vmID;
				$vmList[$vmID]->vmName = $key;
				$vmList[$vmID]->vmState = $value;
				
				$vmID++;
		}
		
		return $vmList;
	}
  }
}

// Simulate a PHP 5.x function, remove if upgrading to PHP 5.x or higher
function array_combine($a1,$a2)
{
	$maxval = count($a1);
    for($i=0;$i<$maxval;$i++)
        $ra[$a1[$i]] = $a2[$i];
    if(isset($ra)) return $ra; else return false;
}
?>
