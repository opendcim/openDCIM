<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	if(!$person->SiteAdmin){
		header('Location: index.php');
		exit;
	}

	$subheader=__("Automated Disposal Processing");

	if(!defined('DISPOSAL_SESSION_KEY')){
		define('DISPOSAL_SESSION_KEY','opendcim_disposal_processing');
	}

	function disposalDefaultState(){
		return array(
			'stage'=>'idle',
			'file'=>'',
			'headers'=>array(),
			'selected_column'=>-1,
			'serials'=>array(),
			'categories'=>array(
				'unknown'=>array(),
				'not_storage'=>array(),
				'storage_ready'=>array(),
				'already_disposed'=>array()
			),
			'actions'=>array(
				'unknown_ignored'=>false,
				'unknown_created'=>false,
				'not_storage_moved'=>false,
				'storage_confirmed'=>false,
				'final_processed'=>false,
				'rollback_done'=>false
			),
			'log'=>array(),
			'disposition_id'=>0,
			'operation_date'=>date('Y-m-d'),
			'completed'=>false
		);
	}

	function disposalSaveState($state){
		$_SESSION[DISPOSAL_SESSION_KEY]=$state;
	}

	function disposalLoadState(){
		if(isset($_SESSION[DISPOSAL_SESSION_KEY]) && is_array($_SESSION[DISPOSAL_SESSION_KEY])){
			$default=disposalDefaultState();
			$state=array_merge($default,$_SESSION[DISPOSAL_SESSION_KEY]);
			foreach($default['categories'] as $key=>$value){
				if(!isset($state['categories'][$key]) || !is_array($state['categories'][$key])){
					$state['categories'][$key]=array();
				}
			}
			foreach($default['actions'] as $key=>$value){
				if(!isset($state['actions'][$key])){
					$state['actions'][$key]=false;
				}
			}
			if(!isset($state['log']) || !is_array($state['log'])){
				$state['log']=array();
			}
			return $state;
		}
		$state=disposalDefaultState();
		disposalSaveState($state);
		return $state;
	}

	function disposalResetState(){
		$state=disposalLoadState();
		if(isset($state['file']) && $state['file']!='' && file_exists($state['file'])){
			@unlink($state['file']);
		}
		$state=disposalDefaultState();
		disposalSaveState($state);
		return $state;
	}

	function disposalFormatLocation($device){
		$cabinetLabel='';
		$rowLabel='';
		$roomLabel='';
		if($device->Cabinet>0){
			$cab=new Cabinet();
			$cab->CabinetID=$device->Cabinet;
			if($cab->GetCabinet()){
				$cabinetLabel=$cab->Location;
				if($cab->CabRowID>0){
					$row=new CabRow();
					$row->CabRowID=$cab->CabRowID;
					if($row->GetCabRow()){
						$rowLabel=$row->Name;
					}
				}
				$dc=new DataCenter();
				$dc->DataCenterID=$cab->DataCenterID;
				if($dc->GetDataCenter()){
					$roomLabel=$dc->Name;
				}
			}
		}elseif($device->Cabinet==-1){
			$roomLabel=__("Storage Room");
		}elseif($device->Cabinet==0){
			$roomLabel=__("Disposed");
		}
		return trim(sprintf("%s / %s / %s",$rowLabel,$roomLabel,$cabinetLabel),"/ ");
	}

	function disposalBuildRecord($device){
		return array(
			'deviceid'=>$device->DeviceID,
			'label'=>$device->Label,
			'serial'=>$device->SerialNo,
			'cabinet'=>$device->Cabinet,
			'position'=>$device->Position,
			'status'=>$device->Status,
			'location'=>disposalFormatLocation($device)
		);
	}

	function disposalAppendLog(&$state,$record,$operation,$actionType,$extra=array()){
		$state['log'][]=array(
			'deviceid'=>isset($record['deviceid'])?$record['deviceid']:0,
			'label'=>isset($record['label'])?$record['label']:'',
			'serial'=>isset($record['serial'])?$record['serial']:'',
			'previous_state'=>isset($extra['previous_state'])?$extra['previous_state']:array(),
			'cabinet'=>isset($record['cabinet'])?$record['cabinet']:'',
			'position'=>isset($record['position'])?$record['position']:'',
			'location'=>isset($record['location'])?$record['location']:__("N/A"),
			'timestamp'=>date('c'),
			'operation'=>$operation,
			'action_type'=>$actionType,
			'disposition_id'=>isset($extra['disposition_id'])?$extra['disposition_id']:0,
			'operation_date'=>isset($extra['operation_date'])?$extra['operation_date']:''
		);
	}

	function disposalPerformRollback(&$state,&$messages,&$errors){
		if(empty($state['log'])){
			$errors[]=__("No operations to rollback.");
			return;
		}
		for($i=count($state['log'])-1;$i>=0;$i--){
			$entry=$state['log'][$i];
			$deviceID=intval($entry['deviceid']);
			$prev=isset($entry['previous_state'])?$entry['previous_state']:array();
			switch($entry['action_type']){
				case 'move_to_storage':
				case 'dispose':
					if($deviceID>0){
						$dev=new Device($deviceID);
						if($dev->GetDevice()){
							if(isset($prev['cabinet'])){
								$dev->Cabinet=$prev['cabinet'];
							}
							if(array_key_exists('position',$prev)){
								$dev->Position=$prev['position'];
							}
							if(isset($prev['status'])){
								$dev->Status=$prev['status'];
							}
							$dev->UpdateDevice();
							if($entry['action_type']=='dispose'){
								DispositionMembership::removeDevice($deviceID);
							}
						}
					}
					break;
				case 'create_minimal':
					if($deviceID>0){
						$dev=new Device($deviceID);
						if($dev->GetDevice()){
							$dev->DeleteDevice();
						}
					}
					break;
				default:
					break;
			}
		}
		$state['actions']['rollback_done']=true;
		$messages[]=__("The rollback completed successfully.");
	}

	$messages=array();
	$errors=array();
	$state=disposalLoadState();

	if($_SERVER['REQUEST_METHOD']!='POST' && !isset($_GET['downloadlog'])){
		if($state['stage']!='idle'){
			$state=disposalResetState();
		}
	}

	if(isset($_GET['downloadlog']) && !empty($state['log'])){
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment;filename="disposal-log-'.date('YmdHis').'.csv"');
		echo "DeviceID,Label,SerialNumber,PreviousCabinet,PreviousPosition,Operation,Timestamp\r\n";
		foreach($state['log'] as $entry){
			$row=array(
				$entry['deviceid'],
				preg_replace('/"/','""',$entry['label']),
				preg_replace('/"/','""',$entry['serial']),
				isset($entry['previous_state']['cabinet'])?$entry['previous_state']['cabinet']:'',
				isset($entry['previous_state']['position'])?$entry['previous_state']['position']:'',
				preg_replace('/"/','""',$entry['operation']),
				$entry['timestamp']
			);
			echo '"'.implode('","',$row).'"'."\r\n";
		}
		exit;
	}

	if(isset($_POST['dp-action'])){
		$action=$_POST['dp-action'];
		switch($action){
			case 'upload':
				$state=disposalResetState();
				if(!isset($_FILES['disposalfile']) || $_FILES['disposalfile']['error']!=UPLOAD_ERR_OK){
					$errors[]=__("Invalid file uploaded.");
					break;
				}
				try{
					$inputType=\PhpOffice\PhpSpreadsheet\IOFactory::identify($_FILES['disposalfile']['tmp_name']);
					$reader=\PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputType);
					$spreadsheet=$reader->load($_FILES['disposalfile']['tmp_name']);
				}catch(Exception $e){
					$errors[]=__("The uploaded file could not be read.");
					break;
				}
				$sheet=$spreadsheet->getSheet(0);
				$highestColumn=$sheet->getHighestColumn();
				$headerRow=$sheet->rangeToArray('A1:'.$highestColumn.'1');
				$headers=(isset($headerRow[0]) && is_array($headerRow[0]))?$headerRow[0]:array();
				$tmp=tempnam(sys_get_temp_dir(),'dp_');
				@unlink($tmp);
				$target=$tmp.'.xlsx';
				if(!move_uploaded_file($_FILES['disposalfile']['tmp_name'],$target)){
					$errors[]=__("Invalid file uploaded.");
					break;
				}
				$state['file']=$target;
				$state['headers']=$headers;
				$state['stage']='select_column';
				$messages[]=__("File uploaded successfully. Please select the column that contains the serial numbers.");
				break;
			case 'select-column':
				if($state['file']=='' || !file_exists($state['file'])){
					$errors[]=__("The uploaded file could not be read.");
					break;
				}
				$columnIndex=isset($_POST['selected_column'])?intval($_POST['selected_column']):-1;
				if($columnIndex<0 || $columnIndex>=count($state['headers'])){
					$errors[]=__("Please select a valid column.");
					break;
				}
				try{
					$inputType=\PhpOffice\PhpSpreadsheet\IOFactory::identify($state['file']);
					$reader=\PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputType);
					$spreadsheet=$reader->load($state['file']);
				}catch(Exception $e){
					$errors[]=__("The uploaded file could not be read.");
					break;
				}
				$sheet=$spreadsheet->getSheet(0);
				$highestRow=$sheet->getHighestRow();
				$serials=array();
				for($row=2;$row<=$highestRow;$row++){
					$value=$sheet->getCellByColumnAndRow($columnIndex+1,$row)->getCalculatedValue();
					$serial=trim((string)$value);
					if($serial!==''){
						$serials[$serial]=$serial;
					}
				}
				$serials=array_values($serials);
				if(empty($serials)){
					$errors[]=__("No serial numbers were found in the selected column.");
					break;
				}
				$categories=array(
					'unknown'=>array(),
					'not_storage'=>array(),
					'storage_ready'=>array(),
					'already_disposed'=>array()
				);
				foreach($serials as $serial){
					$device=Device::GetDeviceBySerialNumber($serial,false);
					if(!$device){
						$categories['unknown'][]=array('serial'=>$serial);
						continue;
					}
					$record=disposalBuildRecord($device);
					if($device->Cabinet>0){
						$categories['not_storage'][$device->DeviceID]=$record;
					}elseif($device->Cabinet==-1){
						$categories['storage_ready'][$device->DeviceID]=$record;
					}elseif($device->Cabinet==0){
						$record['status_note']=__("Already disposed / Skipped");
						$categories['already_disposed'][$device->DeviceID]=$record;
						disposalAppendLog($state,$record,__("Already disposed / Skipped"),'skipped');
					}else{
						$categories['not_storage'][$device->DeviceID]=$record;
					}
				}
				$state['serials']=$serials;
				$state['categories']=$categories;
				$state['selected_column']=$columnIndex;
				$state['stage']='ready';
				$messages[]=__("Serial numbers ready for processing.");
				break;
			case 'ignore-unknown':
				if($state['actions']['unknown_ignored']){
					break;
				}
				if(!empty($state['categories']['unknown'])){
					foreach($state['categories']['unknown'] as $entry){
						$record=array('serial'=>$entry['serial'],'location'=>__("N/A"));
						disposalAppendLog($state,$record,sprintf(__("Ignored unknown serial number %s"),$entry['serial']),'ignored');
					}
					$state['categories']['unknown']=array();
					$state['actions']['unknown_ignored']=true;
					$messages[]=__("Unknown serial numbers have been ignored.");
				}
				break;
			case 'create-minimal':
				if($state['actions']['unknown_created']){
					break;
				}
				if(!empty($state['categories']['unknown'])){
					foreach($state['categories']['unknown'] as $entry){
						$serial=$entry['serial'];
						$device=new Device();
						$device->Label=$serial;
						$device->SerialNo=$serial;
						$device->Cabinet=-1;
						$device->Position=0;
						$device->Height=0;
						$device->Ports=0;
						$device->NominalWatts=0;
						$device->PowerSupplyCount=0;
						$device->FirstPortNum=1;
						$device->TemplateID=0;
						$device->DeviceType='Server';
						$device->ChassisSlots=0;
						$device->RearChassisSlots=0;
						$device->ParentDevice=0;
						$device->Status='Reserved';
						$device->AuditStamp=date('Y-m-d');
						$device->MfgDate=date('Y-m-d');
						$device->InstallDate=date('Y-m-d');
						$device->WarrantyExpire=date('Y-m-d');
						if($device->CreateDevice()){
							$record=disposalBuildRecord($device);
							$state['categories']['storage_ready'][$device->DeviceID]=$record;
							disposalAppendLog($state,$record,__("Created minimal storage record"),'create_minimal',array('previous_state'=>array('created'=>true)));
						}else{
							$errors[]=sprintf(__("Failed to create device for serial number %s."),$serial);
						}
					}
					$state['categories']['unknown']=array();
					$state['actions']['unknown_created']=true;
					$messages[]=__("Minimal device entries created successfully.");
				}
				break;
			case 'move-to-storage':
				if($state['actions']['not_storage_moved']){
					break;
				}
				$selected=isset($_POST['deviceids'])?$_POST['deviceids']:array();
				if(!is_array($selected) || empty($selected)){
					$errors[]=__("Please select at least one device to move.");
					break;
				}
				foreach($selected as $deviceID){
					$deviceID=intval($deviceID);
					if(!isset($state['categories']['not_storage'][$deviceID])){
						continue;
					}
					$record=$state['categories']['not_storage'][$deviceID];
					$dev=new Device($deviceID);
					if($dev->GetDevice()){
						$prevState=array('cabinet'=>$dev->Cabinet,'position'=>$dev->Position,'status'=>$dev->Status);
						$dev->MoveToStorage();
						$updated=disposalBuildRecord($dev);
						$state['categories']['storage_ready'][$deviceID]=$updated;
						disposalAppendLog($state,$record,__("Moved to Storage Room"),'move_to_storage',array('previous_state'=>$prevState));
					}
					unset($state['categories']['not_storage'][$deviceID]);
				}
				$state['actions']['not_storage_moved']=true;
				$messages[]=__("Devices moved to the Storage Room.");
				break;
			case 'confirm-storage':
				$state['actions']['storage_confirmed']=true;
				$messages[]=__("Storage Room devices confirmed. You may proceed with the final processing.");
				break;
			case 'start-processing':
				if($state['actions']['final_processed']){
					break;
				}
				if(!$state['actions']['storage_confirmed']){
					$errors[]=__("Please confirm the Storage Room devices first.");
					break;
				}
				if(empty($state['categories']['storage_ready'])){
					$errors[]=__("No devices are ready in the Storage Room.");
					break;
				}
				$dispID=isset($_POST['dispositionid'])?intval($_POST['dispositionid']):0;
				$dispList=Disposition::getDisposition($dispID);
				if(empty($dispList)){
					$errors[]=__("Please select a valid disposal method.");
					break;
				}
				$operationDate=isset($_POST['operation_date'])?$_POST['operation_date']:'';
				$dateCheck=DateTime::createFromFormat('Y-m-d',$operationDate);
				if(!$dateCheck){
					$errors[]=__("Please provide a valid operation date.");
					break;
				}
				global $dbh;
				foreach($state['categories']['storage_ready'] as $deviceID=>$record){
					$dev=new Device($deviceID);
					if($dev->GetDevice()){
						$prevState=array('cabinet'=>$dev->Cabinet,'position'=>$dev->Position,'status'=>$dev->Status);
						$dev->Dispose($dispID);
						$st=$dbh->prepare("UPDATE fac_DispositionMembership SET DispositionDate=:DispositionDate WHERE DeviceID=:DeviceID");
						$st->execute(array(":DispositionDate"=>$operationDate,":DeviceID"=>$deviceID));
						disposalAppendLog($state,$record,__("Disposed from Storage Room"),'dispose',array('previous_state'=>$prevState,'disposition_id'=>$dispID,'operation_date'=>$operationDate));
					}
				}
				$state['categories']['storage_ready']=array();
				$state['actions']['final_processed']=true;
				$state['completed']=true;
				$state['disposition_id']=$dispID;
				$state['operation_date']=$operationDate;
				$messages[]=__("Devices disposed successfully.");
				break;
			case 'rollback':
				if($state['actions']['rollback_done']){
					break;
				}
				disposalPerformRollback($state,$messages,$errors);
				break;
			case 'complete':
				$state=disposalResetState();
				$messages[]=__("Process completed.");
				break;
			case 'cancel':
				$state=disposalResetState();
				$messages[]=__("Operation cancelled.");
				break;
			default:
				break;
		}
		disposalSaveState($state);
	}

	$dList=Disposition::getDisposition();
?>
<!doctype html>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?php echo __("Automated Disposal Processing"); ?></title>
	<link rel="stylesheet" href="css/inventory.php" type="text/css">
	<link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
	<script type="text/javascript" src="scripts/jquery.min.js"></script>
	<script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
	<style>
		.dp-section{margin-bottom:20px;padding:15px;border:1px solid #ccc;background:#fafafa;}
		.dp-section h3{margin-top:0;}
		.dp-table .title-row{font-weight:bold;background:#e0e0e0;}
		.dp-table>div{display:grid;grid-template-columns:repeat(5,1fr);padding:5px;border-bottom:1px solid #e5e5e5;}
		.dp-table>div:nth-child(even){background:#f7f7f7;}
		.dp-table .full{grid-column:1 / -1;}
		.dp-muted{color:#777;}
		.dp-disabled button[disabled]{opacity:.5;cursor:not-allowed;}
		.dp-category-unknown{border-color:#b22222;background:#fff5f5;}
		.dp-category-notstorage{border-color:#1e90ff;background:#f0f8ff;}
		.dp-category-storage{border-color:#2e8b57;background:#f5fff7;}
		.dp-category-disposed{border-color:#777;background:#f2f2f2;}
	</style>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php include( 'sidebar.inc.php' ); ?>
<div class="main">
	<div class="center">
		<div class="notice warning" style="background:#fde4e1;border:1px solid #d32f2f;color:#d32f2f;padding:10px;margin-bottom:15px;">
			<?php echo __("Before starting this automated disposal workflow, please perform a full database backup."); ?>
		</div>
		<br>
		<br>
		<?php
			foreach($errors as $msg){
				printf('<div class="notice bad">%s</div>',htmlspecialchars($msg));
			}
			foreach($messages as $msg){
				printf('<div class="notice good">%s</div>',htmlspecialchars($msg));
			}
		?>
		<br>
		<div class="dp-section">
			<h3><?php echo __("Import disposal spreadsheet"); ?></h3>
			<?php if($state['stage']=='idle'){ ?>
			<form method="post" enctype="multipart/form-data">
				<input type="hidden" name="dp-action" value="upload">
				<div>
					<label for="disposalfile"><?php echo __("Upload Excel File"); ?></label>
					<input type="file" name="disposalfile" id="disposalfile" accept=".xlsx,.xls">
				</div>
				<div><button type="submit"><?php echo __("Start Import"); ?></button></div>
			</form>
			<?php }elseif($state['stage']=='select_column'){ ?>
			<form method="post">
				<input type="hidden" name="dp-action" value="select-column">
				<div>
					<label for="selected_column"><?php echo __("Select Serial Number Column"); ?></label>
					<select name="selected_column" id="selected_column">
						<?php foreach($state['headers'] as $idx=>$header){ ?>
						<option value="<?php echo $idx; ?>"><?php echo htmlspecialchars($header); ?></option>
						<?php } ?>
					</select>
				</div>
				<div><button type="submit"><?php echo __("Continue"); ?></button></div>
			</form>
			<form method="post">
				<input type="hidden" name="dp-action" value="cancel">
				<button type="submit"><?php echo __("Cancel operation"); ?></button>
			</form>
			<?php } ?>
		</div>
		<br>
		<br>
		<?php if($state['stage']=='ready' || $state['completed']){ ?>
		<div class="dp-section dp-category-unknown">
			<h3><?php echo __("Category 1: Unknown serial numbers"); ?></h3>
			<div class="dp-table">
				<div class="title-row">
					<div><?php echo __("Serial Number"); ?></div>
					<div class="full"></div>
				</div>
				<?php
					if(empty($state['categories']['unknown'])){
						echo '<div class="full">'.__("No records found.").'</div>';
					}else{
						foreach($state['categories']['unknown'] as $entry){
							printf('<div><div>%s</div></div>',htmlspecialchars($entry['serial']));
						}
					}
				?>
			</div>
			<div class="dp-actions">
				<form method="post" class="dp-disabled">
					<input type="hidden" name="dp-action" value="ignore-unknown">
					<button type="submit" <?php echo ($state['actions']['unknown_ignored']?'disabled':''); ?>><?php echo __("Proceed without processing unknown SN"); ?></button>
				</form>
				<form method="post" class="dp-disabled">
					<input type="hidden" name="dp-action" value="create-minimal">
					<button type="submit" <?php echo ($state['actions']['unknown_created']?'disabled':''); ?>><?php echo __("Create minimal device entries automatically"); ?></button>
				</form>
				<form method="post">
					<input type="hidden" name="dp-action" value="cancel">
					<button type="submit"><?php echo __("Cancel operation"); ?></button>
				</form>
			</div>
		</div>
		<br>
		<br>
		<div class="dp-section dp-category-notstorage">
			<h3><?php echo __("Category 2: Devices not in the Storage Room"); ?></h3>
			<form method="post">
				<input type="hidden" name="dp-action" value="move-to-storage">
				<div class="dp-table">
					<div class="title-row">
						<div><?php echo __("Select"); ?></div>
						<div><?php echo __("Device ID"); ?></div>
						<div><?php echo __("Label"); ?></div>
						<div><?php echo __("Serial Number"); ?></div>
						<div><?php echo __("Location"); ?></div>
					</div>
					<?php
						if(empty($state['categories']['not_storage'])){
							echo '<div class="full">'.__("No records found.").'</div>';
						}else{
							foreach($state['categories']['not_storage'] as $record){
								printf('<div><div><input type="checkbox" name="deviceids[]" value="%s"></div><div>%s</div><div>%s</div><div>%s</div><div>%s</div></div>',
									$record['deviceid'],
									$record['deviceid'],
									htmlspecialchars($record['label']),
									htmlspecialchars($record['serial']),
									htmlspecialchars($record['location'])
								);
							}
						}
					?>
				</div>
				<div class="dp-actions dp-disabled">
					<button type="submit" <?php echo ($state['actions']['not_storage_moved']?'disabled':''); ?>><?php echo __("Move selected devices to Storage Room"); ?></button>
				</div>
			</form>
			<form method="post">
				<input type="hidden" name="dp-action" value="cancel">
				<button type="submit"><?php echo __("Cancel operation"); ?></button>
			</form>
		</div>
		<br>
		<br>
		<div class="dp-section dp-category-storage">
			<h3><?php echo __("Category 3: Devices ready in the Storage Room"); ?></h3>
			<div class="dp-table">
				<div class="title-row">
					<div><?php echo __("Device ID"); ?></div>
					<div><?php echo __("Label"); ?></div>
					<div><?php echo __("Serial Number"); ?></div>
					<div><?php echo __("Location"); ?></div>
					<div><?php echo __("Position"); ?></div>
				</div>
				<?php
					if(empty($state['categories']['storage_ready'])){
						echo '<div class="full">'.__("No records found.").'</div>';
					}else{
						foreach($state['categories']['storage_ready'] as $record){
							printf('<div><div>%s</div><div>%s</div><div>%s</div><div>%s</div><div>%s</div></div>',
								$record['deviceid'],
								htmlspecialchars($record['label']),
								htmlspecialchars($record['serial']),
								htmlspecialchars($record['location']),
								htmlspecialchars($record['position'])
							);
						}
					}
				?>
			</div>
			<form method="post" class="dp-disabled">
				<input type="hidden" name="dp-action" value="confirm-storage">
				<button type="submit" <?php echo ($state['actions']['storage_confirmed']?'disabled':''); ?>><?php echo __("Proceed with disposal method"); ?></button>
			</form>
			<form method="post">
				<input type="hidden" name="dp-action" value="cancel">
				<button type="submit"><?php echo __("Cancel operation"); ?></button>
			</form>
		</div>
		<br>
		<br>
		<div class="dp-section dp-category-disposed">
			<h3><?php echo __("Category 4: Already processed"); ?></h3>
			<p class="dp-muted"><?php echo __("Items already processed will be skipped."); ?></p>
			<div class="dp-table">
				<div class="title-row">
					<div><?php echo __("Device ID"); ?></div>
					<div><?php echo __("Label"); ?></div>
					<div><?php echo __("Serial Number"); ?></div>
					<div><?php echo __("Status"); ?></div>
					<div><?php echo __("Location"); ?></div>
				</div>
				<?php
					if(empty($state['categories']['already_disposed'])){
						echo '<div class="full">'.__("No records found.").'</div>';
					}else{
						foreach($state['categories']['already_disposed'] as $record){
							$statusNote=isset($record['status_note'])?$record['status_note']:__("Already disposed / Skipped");
							printf('<div class="dp-muted"><div>%s</div><div>%s</div><div>%s</div><div>%s</div><div>%s</div></div>',
								$record['deviceid'],
								htmlspecialchars($record['label']),
								htmlspecialchars($record['serial']),
								htmlspecialchars($statusNote),
								htmlspecialchars($record['location'])
							);
						}
					}
				?>
			</div>
		</div>
		<br>
		<div class="dp-section">
			<h3><?php echo __("Final disposal method"); ?></h3>
			<form method="post">
				<input type="hidden" name="dp-action" value="start-processing">
				<div>
					<label for="dispositionid"><?php echo __("Select disposition method"); ?></label>
					<select name="dispositionid" id="dispositionid">
						<?php
							foreach($dList as $disp){
								if($disp->Status=="Active"){
									$selected=($state['disposition_id']==$disp->DispositionID)?' selected':'';
									printf('<option value="%s"%s>%s</option>',$disp->DispositionID,$selected,htmlspecialchars($disp->Name));
								}
							}
						?>
					</select>
				</div>
				<div>
					<label for="operation_date"><?php echo __("Operation date"); ?></label>
					<input type="text" name="operation_date" id="operation_date" class="dp-datepicker" value="<?php echo htmlspecialchars($state['operation_date']); ?>">
				</div>
				<div>
					<button type="submit" <?php echo ($state['actions']['final_processed']?'disabled':''); ?>><?php echo __("Start Processing"); ?></button>
				</div>
			</form>
		</div>
		<br>
		<?php if(!empty($state['log'])){ ?>
		<div class="dp-section">
			<h3><?php echo __("Processing log"); ?></h3>
			<div class="dp-table">
				<div class="title-row">
					<div><?php echo __("Device ID"); ?></div>
					<div><?php echo __("Label"); ?></div>
					<div><?php echo __("Serial Number"); ?></div>
					<div><?php echo __("Operation"); ?></div>
					<div><?php echo __("Timestamp"); ?></div>
				</div>
				<?php foreach($state['log'] as $entry){ ?>
				<div>
					<div><?php echo $entry['deviceid']; ?></div>
					<div><?php echo htmlspecialchars($entry['label']); ?></div>
					<div><?php echo htmlspecialchars($entry['serial']); ?></div>
					<div><?php echo htmlspecialchars($entry['operation']); ?></div>
					<div><?php echo htmlspecialchars($entry['timestamp']); ?></div>
				</div>
				<?php } ?>
			</div>
			<div class="dp-actions">
				<form method="get">
					<button type="submit" name="downloadlog" value="1"><?php echo __("Download full log"); ?></button>
				</form>
			</div>
		</div>
		<br>
		<?php } ?>
		<?php if($state['completed']){ ?>
		<div class="dp-section">
			<form method="post" class="dp-disabled">
				<input type="hidden" name="dp-action" value="rollback">
				<button type="submit" <?php echo ($state['actions']['rollback_done']?'disabled':''); ?>><?php echo __("Undo last operation"); ?></button>
			</form>
			<form method="post">
				<input type="hidden" name="dp-action" value="complete">
				<button type="submit"><?php echo __("Process Completed"); ?></button>
			</form>
		</div>
		<?php } ?>
		<?php } ?>
	</div>
</div>
</div>
<script type="text/javascript">
$(document).ready(function(){
	$(".dp-datepicker").datepicker({dateFormat:'yy-mm-dd'});
});
</script>
</body>
</html>
