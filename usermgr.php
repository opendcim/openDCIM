<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("Data Center Contact Detail");

	if(!$person->ContactAdmin){
		header('Location: '.redirect());
		exit;
	}

	$userRights=new People();
	$status="";

	if(isset($_REQUEST['PersonID']) && strlen($_REQUEST['PersonID']) >0){
		$userRights->PersonID=$_REQUEST['PersonID'];
		$userRights->GetPerson();
	}
	
	if(isset($_POST['action'])&&isset($_POST['UserID'])){
		if((($_POST['action']=='Create')||($_POST['action']=='Update'))&&(isset($_POST['LastName'])&&$_POST['LastName']!=null&&$_POST['LastName']!='')){
			$userRights->UserID=$_POST['UserID'];
			$userRights->LastName=$_POST['LastName'];
			$userRights->FirstName=$_POST['FirstName'];
			$userRights->Phone1=$_POST['Phone1'];
			$userRights->Phone2=$_POST['Phone2'];
			$userRights->Phone3=$_POST['Phone3'];
			$userRights->Email=$_POST['Email'];

			if ( isset($_POST['NewKey']) ) {
				$userRights->APIKey=md5($userRights->UserID . date('Y-m-d H:i:s') );
			}

			// if AUTHENTICATION == "LDAP" these get overwritten whenever an LDAP user logs in
			// however, an LDAP site still needs to be able to add a userid for API access
			// and set their rights
			$userRights->AdminOwnDevices=(isset($_POST['AdminOwnDevices']))?1:0;
			$userRights->ReadAccess=(isset($_POST['ReadAccess']))?1:0;
			$userRights->WriteAccess=(isset($_POST['WriteAccess']))?1:0;
			$userRights->DeleteAccess=(isset($_POST['DeleteAccess']))?1:0;
			$userRights->ContactAdmin=(isset($_POST['ContactAdmin']))?1:0;
			$userRights->RackRequest=(isset($_POST['RackRequest']))?1:0;
			$userRights->RackAdmin=(isset($_POST['RackAdmin']))?1:0;
			$userRights->BulkOperations=(isset($_POST['BulkOperations']))?1:0;
			$userRights->SiteAdmin=(isset($_POST['SiteAdmin']))?1:0;
			$userRights->Disabled=(isset($_POST['Disabled']))?1:0;

			if($_POST['action']=='Create'){
  				if ( $userRights->CreatePerson() ) {
					// We've, hopefully, successfully created a new device. Force them to the new device page.
					header('Location: '.redirect("usermgr.php?PersonID=$userRights->PersonID"));
					exit;
				} else {
					// Likely the UserID already exists
					if ( $userRights->GetPersonByUserID() ) {
						$status=__("Existing UserID account displayed.");
					} else {
						$status=__("Something is broken.   Unable to create Person account.");
					}
				}
			}else{
				$status=__("Updated");
				$userRights->UpdatePerson();
			}
		}else{
		//Should we ever add a delete user function it will go here
		}
		// Reload rights because actions like disable reset other rights
		$userRights->GetUserRights();
	}

	$userList=$userRights->GetUserList();
	$adminown=($userRights->AdminOwnDevices)?"checked":"";
	$read=($userRights->ReadAccess)?"checked":"";
	$write=($userRights->WriteAccess)?"checked":"";
	$delete=($userRights->DeleteAccess)?"checked":"";
	$contact=($userRights->ContactAdmin)?"checked":"";
	$request=($userRights->RackRequest)?"checked":"";
	$RackAdmin=($userRights->RackAdmin)?"checked":"";
	$BulkOperations=($userRights->BulkOperations)?"checked":"";
	$admin=($userRights->SiteAdmin)?"checked":"";
	$Disabled=($userRights->Disabled)?"checked":"";

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM User Manager</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript">
	$(document).ready(function(){
		$('.main form').validationEngine();

		$('#PersonID').change(function(e){
			location.href='?PersonID='+this.value;
		});

		$('#showdept').click(showdept);
		$('#transferdevices').click(ShowModal);

		// If they have an id assigned do a post page load lookup of how many devices they
		// are the primary contact for
		if(parseInt($('#PersonID').val())){
			UpdateDeviceCount();
		}

		$('#PrimaryContact').click(function(e){
			var poopup=window.open('search.php?key=dev&PrimaryContact='+$('#PersonID').val()+'&search');
		});

		$('#nofloat :input').click(DisabledFlipper);

		function DisabledFlipper(e){
			if(e.currentTarget.name=='Disabled'){
				$('#nofloat :input[name!="Disabled"]').each(function(){this.checked=false;});
				if(e.currentTarget.checked && parseInt($('#PrimaryContact').text())){
					ShowModal();
				}
			}else{
				$('#Disabled').prop('checked',false);
			}
		}
	});
	function UpdateDeviceCount(){
		var PersonID=$('#PersonID').val();
		$.get('api/v1/device?PrimaryContact='+PersonID).done(function(data){
			$('#PrimaryContact').text(data.device.length);
			if(data.device.length){
				$('#transferdevices').removeClass('hide').show();
			}else{
				$('#transferdevices').hide();
			}
		});
	}

	function ShowModal(e){
		$('#copy').replaceWith($('#PersonID').clone().attr('id','copy'));
		$('#copy option[value=0]').text('');
		$('#copy option[value='+$('#PersonID').val()+']').remove();
		// remove any disabled userids from the available transfer list
		$.get('api/v1/people?Disabled=1').done(function(data){
			if(!data.error){
				for(var x in data.people){
					$('#copy option[value='+data.people[x].PersonID+']').remove();
				}
			}
		});
		$('#deletemodal').dialog({
			dialogClass: "no-close",
			width: 600,
			modal: true,
			buttons: {
				Transfer: function(e){
					$('#doublecheck').dialog({
						dialogClass: "no-close",
						width: 600,
						modal: true,
						buttons: {
							Yes: function(e){
								$.post('api/v1/people/'+$('#PersonID').val()+'/transferdevicesto/'+$('#copy').val()).done(function(data){
									if(!data.error){
										$('#doublecheck').dialog('destroy');
										$('#deletemodal').dialog('destroy');
									}
								});
								UpdateDeviceCount();
							},
							No: function(e){
								$('#doublecheck').dialog('destroy');
								$('#deletemodal').dialog('destroy');
								$('#Disabled').prop('checked',false);
							}
						}
					});
				},
				No: function(e){
					$('#deletemodal').dialog('destroy');
					$('#Disabled').prop('checked',false);
				}
			}
		});
	}

	function showdept(){
		if($(".main form").validationEngine('validate')){
			// Serialize the form data
			var formdata=$(".main form").serializeArray();
			// Set the action of the form to Update
			formdata.push({name:'action',value:"Update"});
			// Update the user just incase they altered it before clicking on department
			$.post('',formdata);
			// Hide the rights selections
			$('#nofloat').parent('div').addClass('hide');
			// Set the remaining fields to read only
			$('.center input').attr('readonly','true');
			// Hide the form controls
			$('#controls').hide().removeClass('caption');
			// Load the group controls inside the iframe
			$('#groupadmin').css('display','block').attr('src', 'people_depts.php?personid='+$('#PersonID').val());
		}
	}
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>

<div class="page">
<?php
    include( 'sidebar.inc.php' );

echo '<div class="main">
<h3>',$status,'</h3>
<div class="center"><div>
<form method="POST">
<div class="table centermargin">
<div>
   <div><label for="PersonID">',__("User"),'</label></div>
   <div><select name="PersonID" id="PersonID">
   <option value=0>',__("New User"),'</option>';

	foreach($userList as $userRow){
		if($userRights->PersonID == $userRow->PersonID){$selected='selected';}else{$selected="";}
		print "<option value=$userRow->PersonID $selected>" . $userRow->LastName . ", " . $userRow->FirstName . " (" . $userRow->UserID . ")" . "</option>\n";
	}

echo '	</select>&nbsp;&nbsp;<span title="',__("This user is the primary contact for this many devices"),'" id="PrimaryContact"></span></div>
</div>
<div>
   <div><label for="UserID">',__("UserID"),'</label></div>
   <div><input type="text" class="validate[required,minSize[1],maxSize[50]]" name="UserID" id="UserID" value="',$userRights->UserID,'"></div>
</div>
<div>
   <div><label for="LastName">',__("Last Name"),'</label></div>
   <div><input type="text" class="validate[required,minSize[1],maxSize[50]]" name="LastName" id="LastName" value="',$userRights->LastName,'"></div>
</div>
<div>
   <div><label for="FirstName">',__("First Name"),'</label></div>
   <div><input type="text" class="validate[required,minSize[1],maxSize[50]]" name="FirstName" id="FirstName" value="',$userRights->FirstName,'"></div>
</div>
<div>
   <div><label for="Phone1">',__("Phone 1"),'</label></div>
   <div><input type="text" name="Phone1" id="Phone1" value="',$userRights->Phone1,'"></div>
</div>
<div>
   <div><label for="Phone2">',__("Phone 2"),'</label></div>
   <div><input type="text" name="Phone2" id="Phone2" value="',$userRights->Phone2,'"></div>
</div>
<div>
   <div><label for="Phone3">',__("Phone 3"),'</label></div>
   <div><input type="text" name="Phone3" id="Phone3" value="',$userRights->Phone3,'"></div>
</div>
<div>
   <div><label for="Email">',__("Email Address"),'</label></div>
   <div><input type="text" class="validate[optional,custom[email],condRequired[RackRequest]]" name="Email" id="Email" value="',$userRights->Email,'"></div>
</div>
<div>
   <div><label for="APIKey">',__("API Key"),'</label></div>
   <div><input type="text" size="60" name="APIKey" id="APIKey" value="',$userRights->APIKey,'" readonly></div>
</div>
<div>
   <div><label for="NewKey">',__("Generate New Key"),'</label></div>
   <div><input name="NewKey" id="NewKey" type="checkbox"></div>
</div>
<div>
   <div><label>',__("Rights"),'</label></div>
   <div id="nofloat">
	<input name="AdminOwnDevices" id="AdminOwnDevices" type="checkbox" ',$adminown,'><label for="AdminOwnDevices">',__("Admin Own Devices"),'</label><br>
	<input name="ReadAccess" id="ReadAccess" type="checkbox" ',$read,'><label for="ReadAccess">',__("Read/Report Access (Global)"),'</label><br>
	<input name="WriteAccess" id="WriteAccess" type="checkbox" ',$write,'><label for="WriteAccess">',__("Modify/Enter Devices (Global)"),'</label><br>
	<input name="DeleteAccess" id="DeleteAccess" type="checkbox" ',$delete,'><label for="DeleteAccess">',__("Delete Devices (Global)"),'</label><br>
	<input name="ContactAdmin" id="ContactAdmin" type="checkbox" ',$contact,'><label for="ContactAdmin">',__("Enter/Modify Contacts and Departments"),'</label><br>
	<input name="RackRequest" id="RackRequest" type="checkbox" ',$request,'><label for="RackRequest">',__("Enter Rack Requests"),'</label><br>
	<input name="RackAdmin" id="RackAdmin" type="checkbox" ',$RackAdmin,'><label for="RackAdmin">',__("Complete Rack Requests"),'</label><br>
	<input name="BulkOperations" id="BulkOperations" type="checkbox" ',$BulkOperations,'><label for="BulkOperations">',__("Perform Bulk Operations"),'</label><br>
	<input name="SiteAdmin" id="SiteAdmin" type="checkbox" ',$admin,'><label for="SiteAdmin">',__("Manage Site and Users"),'</label><br>
	<input name="Disabled" id="Disabled" type="checkbox" ',$Disabled,'><label for="Disabled">',__("Disabled"),'</label><br>	
   </div>
</div>
<div class="caption" id="controls">';

	if($userRights->PersonID>0){
		echo '<button type="submit" name="action" value="Update">',__("Update"),'</button><button type="button" id="showdept">',__("Department Membership"),'</button><button class="hide" id="transferdevices" type="button">',__("Transfer Devices"),'</button>';
	}else{
		echo '	 <button type="submit" name="action" value="Create">',__("Create"),'</button>';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
<iframe name="groupadmin" id="groupadmin" scrolling="no"></iframe>
<br>
</div></div>
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div> <!-- END div.main -->
</div> <!-- END div.page -->
<script type="text/javascript">
$('iframe').load(function() {
    this.style.height =
    this.contentWindow.document.body.offsetHeight + 'px';
}).attr({frameborder:0,scrolling:'no'});
</script>
<?php echo '
<!-- hiding modal dialogs here so they can be translated easily -->
<div class="hide">
	<div title="',__("Transfer all devices to another primary contact"),'" id="deletemodal">
		<div>Transfer all existing devices to <select id="copy"></select></div>
	</div>
	<div title="',__("Are you REALLY sure?"),'" id="doublecheck">
		<div id="modaltext" class="warning"><span style="float:left; margin:0 7px 20px 0;" class="ui-icon ui-icon-alert"></span>',__("Are you sure REALLY sure?  There is no undo!!"),'
		<br><br>
		</div>
	</div>
</div>'; ?>
</body>
</html>
