<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subversion=__("Data Center Contact Detail");

	if(!$person->SiteAdmin){
		header('Location: '.redirect());
		exit;
	}

	$userRights=new People();
	$status="";

	if(isset($_REQUEST['personid']) && strlen($_REQUEST['personid']) >0){
		$userRights->PersonID=$_REQUEST['personid'];
		$userRights->GetPerson();
	}
	
	if(isset($_POST['action'])&&isset($_POST['userid'])){
		if((($_POST['action']=='Create')||($_POST['action']=='Update'))&&(isset($_POST['lastname'])&&$_POST['lastname']!=null&&$_POST['lastname']!='')){
			$userRights->UserID=$_POST['userid'];
			$userRights->LastName=$_POST['lastname'];
			$userRights->FirstName=$_POST['firstname'];
			$userRights->Phone1=$_POST['phone1'];
			$userRights->Phone2=$_POST['phone2'];
			$userRights->Phone3=$_POST['phone3'];
			$userRights->Email=$_POST['email'];
			$userRights->AdminOwnDevices=(isset($_POST['adminowndevices']))?1:0;
			$userRights->ReadAccess=(isset($_POST['readaccess']))?1:0;
			$userRights->WriteAccess=(isset($_POST['writeaccess']))?1:0;
			$userRights->DeleteAccess=(isset($_POST['deleteaccess']))?1:0;
			$userRights->ContactAdmin=(isset($_POST['contactadmin']))?1:0;
			$userRights->RackRequest=(isset($_POST['rackrequest']))?1:0;
			$userRights->RackAdmin=(isset($_POST['rackadmin']))?1:0;
			$userRights->SiteAdmin=(isset($_POST['siteadmin']))?1:0;
			$userRights->Disabled=(isset($_POST['disabled']))?1:0;

			if($_POST['action']=='Create'){
  				$userRights->CreatePerson();
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
	$rackadmin=($userRights->RackAdmin)?"checked":"";
	$admin=($userRights->SiteAdmin)?"checked":"";
	$disabled=($userRights->Disabled)?"checked":"";

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM User Manager</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript">
	$(document).ready(function(){
		$('#personid').change(function(e){
			location.href='?personid='+this.value;
		});

		$('#showdept').click(showdept);
		$('#transferdevices').click(ShowModal);

		// If they have an id assigned do a post page load lookup of how many devices they
		// are the primary contact for
		if(parseInt($('#personid').val())){
			UpdateDeviceCount();
		}

		$('#primarycontact').click(function(e){
			var poopup=window.open('search.php?key=dev&PrimaryContact='+$('#personid').val()+'&search');
		});

		$('#nofloat :input').click(DisabledFlipper);

		function DisabledFlipper(e){
			if(e.currentTarget.name=='disabled'){
				$('#nofloat :input[name!="disabled"]').each(function(){this.checked=false;});
				if(e.currentTarget.checked && parseInt($('#primarycontact').text())){
					ShowModal();
				}
			}else{
				$('#disabled').prop('checked',false);
			}
		}
	});
	function UpdateDeviceCount(){
		var PersonID=$('#personid').val();
		$.get('api/v1/device?PrimaryContact='+PersonID).done(function(data){
			$('#primarycontact').text(data.device.length);
			if(data.device.length){
				$('#transferdevices').removeClass('hide').show();
			}else{
				$('#transferdevices').hide();
			}
		});
	}

	function ShowModal(e){
		$('#copy').replaceWith($('#personid').clone().attr('id','copy'));
		$('#copy option[value=0]').text('');
		$('#copy option[value='+$('#personid').val()+']').remove();
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
								$.post('api/v1/people/'+$('#personid').val()+'/transferdevicesto/'+$('#copy').val()).done(function(data){
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
								$('#disabled').prop('checked',false);
							}
						}
					});
				},
				No: function(e){
					$('#deletemodal').dialog('destroy');
					$('#disabled').prop('checked',false);
				}
			}
		});
	}

	function showdept(){
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
		$('#groupadmin').css('display','block').attr('src', 'people_depts.php?personid='+$('#personid').val());
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
   <div><label for="personid">',__("User"),'</label></div>
   <div><select name="personid" id="personid">
   <option value=0>',__("New User"),'</option>';

	foreach($userList as $userRow){
		if($userRights->PersonID == $userRow->PersonID){$selected='selected';}else{$selected="";}
		print "<option value=$userRow->PersonID $selected>" . $userRow->LastName . ", " . $userRow->FirstName. "</option>\n";
	}

echo '	</select>&nbsp;&nbsp;<span title="',__("This user is the primary contact for this many devices"),'" id="primarycontact"></span></div>
</div>
<div>
   <div><label for="userid">',__("UserID"),'</label></div>
   <div><input type="text" name="userid" id="userid" value="',$userRights->UserID,'"></div>
</div>
<div>
   <div><label for="lastname">',__("Last Name"),'</label></div>
   <div><input type="text" name="lastname" id="lastname" value="',$userRights->LastName,'"></div>
</div>
<div>
   <div><label for="firstname">',__("First Name"),'</label></div>
   <div><input type="text" name="firstname" id="firstname" value="',$userRights->FirstName,'"></div>
</div>
<div>
   <div><label for="phone1">',__("Phone 1"),'</label></div>
   <div><input type="text" name="phone1" id="phone1" value="',$userRights->Phone1,'"></div>
</div>
<div>
   <div><label for="phone2">',__("Phone 2"),'</label></div>
   <div><input type="text" name="phone2" id="phone2" value="',$userRights->Phone2,'"></div>
</div>
<div>
   <div><label for="phone3">',__("Phone 3"),'</label></div>
   <div><input type="text" name="phone3" id="phone3" value="',$userRights->Phone3,'"></div>
</div>
<div>
   <div><label for="email">',__("Email Address"),'</label></div>
   <div><input type="text" name="email" id="email" value="',$userRights->Email,'"></div>
</div>
<div>
   <div><label>',__("Rights"),'</label></div>
   <div id="nofloat">
	<input name="adminowndevices" id="adminowndevices" type="checkbox" ',$adminown,'><label for="adminowndevices">',__("Admin Own Devices"),'</label><br>
	<input name="readaccess" id="readaccess" type="checkbox" ',$read,'><label for="readaccess">',__("Read/Report Access (Global)"),'</label><br>
	<input name="writeaccess" id="writeaccess" type="checkbox" ',$write,'><label for="writeaccess">',__("Modify/Enter Devices (Global)"),'</label><br>
	<input name="deleteaccess" id="deleteaccess" type="checkbox" ',$delete,'><label for="deleteaccess">',__("Delete Devices (Global)"),'</label><br>
	<input name="contactadmin" id="contactadmin" type="checkbox" ',$contact,'><label for="contactadmin">',__("Enter/Modify Contacts and Departments"),'</label><br>
	<input name="rackrequest" id="rackrequest" type="checkbox" ',$request,'><label for="rackrequest">',__("Enter Rack Requests"),'</label><br>
	<input name="rackadmin" id="rackadmin" type="checkbox" ',$rackadmin,'><label for="rackadmin">',__("Complete Rack Requests"),'</label><br>
	<input name="siteadmin" id="siteadmin" type="checkbox" ',$admin,'><label for="siteadmin">',__("Manage Site and Users"),'</label><br>
	<input name="disabled" id="disabled" type="checkbox" ',$disabled,'><label for="disabled">',__("Disabled"),'</label><br>	
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
