<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$manufacturer=new Manufacturer();
	$ManufacturerListByID=$manufacturer->GetManufacturerList(true);

	$dev=new Device();
	$dev->DeviceType="Sensor";
	$sensors=$dev->Search();
	foreach($sensors as $sensor){
		if($sensor->PrimaryIP!=''){
			echo $sensor->Label.' ('.$sensor->DeviceID.') :: xxx.xxx.xxx.'.explode(".",$sensor->PrimaryIP)[3]."<br>";
			$t=new SensorTemplate();
			$t->TemplateID=$sensor->TemplateID;
			if(!$t->GetTemplate()){
				echo "Invalid template... skip to next device<br>";
				// Invalid template, how'd that happen?  Move on..
				continue;
			}
			echo '// Template details<br>';
			echo 'TEMPLATE: ['.$ManufacturerListByID[$t->ManufacturerID]->Name.'] '.$t->Model.' ('.$t->TemplateID.')<br>';
			echo 'TEMPLATE OID Temperature: '.$t->TemperatureOID.'<br>';
			echo 'TEMPLATE OID Humidity: '.$t->HumidityOID.'<br>';
			echo 'TEMPLATE Measurement Units: $t->mUnits='.$t->mUnits.'<br>';
			echo '// Get data from sensor<br>';
			$temp=($t->TemperatureOID)?floatval(Device::OSS_SNMP_Lookup($sensor,null,"$t->TemperatureOID")):0;
			$humidity=($t->HumidityOID)?floatval(Device::OSS_SNMP_Lookup($sensor,null,"$t->HumidityOID")):0;
			echo 'RAW::$temp='.$temp.'<br>';
			echo 'RAW::$humidity='.$humidity.'<br>';
			echo '// Make the temp and humidity safe for sql<br>';
			$temp=float_sqlsafe($temp);
			$humidity=float_sqlsafe($humidity);
			echo 'FLOAT::$temp='.$temp.'<br>';
			echo 'FLAOT::$humidity='.$humidity.'<br>';
			echo '// Strip out everything but numbers<br>';
			$temp=preg_replace("/[^0-9.,+]/","",$temp);
			$humidity=preg_replace("/[^0-9.'+]/","",$humidity);
			echo 'CLEAN::$temp='.$temp.'<br>';
			echo 'CLEAN::$humidity='.$humidity.'<br>';
			echo '// Apply multiplier<br>';
			$temp*=$t->TempMultiplier;
			$humidity*=$t->HumidityMultiplier;
			echo '$temp*$t->TempMultiplier('.$t->TempMultiplier.')='.$temp.'<br>';
			echo '$humidity*$t->HumidityMultiplier('.$t->HumidityMultiplier.')='.$humidity.'<br>';
			echo '// Convert the units if necessary<br>';
			if(($t->mUnits=="english") && ($config->ParameterArray["mUnits"]=="metric") && $temp){
				echo 'Reading imperial(english) values from device... converting to metric<br>';
				$temp=(($temp-32)*5/9);
				echo 'CONVERSION::$temp='.$temp.'<br>';
				// device template is set to metric but the user wants english so convert it
			}elseif(($t->mUnits=="metric") && ($config->ParameterArray["mUnits"]=="english")){
				echo 'Reading metric values from device... converting to imperial(english)<br>';
				$temp=(($temp*9/5)+32);
				echo 'CONVERSION::$temp='.$temp.'<br>';
			}else{
				echo 'No conversion necessary....<br>';
			}
			echo '// Handle internationalization cases using commas for periods breaking sql entries.<br>';
			$temp=number_format($temp, 2, '.', '');
			$humidity=number_format($humidity, 2, '.', '');
			echo 'FORMATTED::$temp='.$temp.'<br>';
			echo 'FORMATTED::$humidity='.$humidity.'<br>';
			echo '// Output SQL statement<br>';
			$insertsql="INSERT INTO fac_SensorReadings SET DeviceID=$sensor->DeviceID,Temperature=$temp, Humidity=$humidity, LastRead=NOW() ON DUPLICATE KEY UPDATE Temperature=$temp, Humidity=$humidity, LastRead=NOW();";
			echo 'SQL: '.$insertsql.'<br>';
			if(!$dbh->query($insertsql)){
				$info=$dbh->errorInfo();
				echo( "UpdateSensors::PDO Error: {$info[2]} SQL=$insertsql" );
			}
			echo '<br><br>';
//			print_r($sensor);
			flush();
		}
	}

?>
