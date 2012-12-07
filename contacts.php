<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$contact=new Contact();
	$user=new User();

	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->ContactAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	// AJAX
	if(isset($_POST['n']) && isset($_POST['o'])){
		mysql_query("UPDATE fac_Device SET PrimaryContact=".intval($_POST['n'])." WHERE PrimaryContact=".intval($_POST['o']).";", $facDB);
		if(mysql_affected_rows($facDB)>0){
			return "yes";
		}else{
			return "no";
		}
	}
	if(isset($_POST['deletecheck'])){
		$contactid=intval($_POST['contactid']);
		$sql="SELECT * FROM fac_Device WHERE PrimaryContact = $contactid";
		$results=mysql_query($sql, $facDB);
		if(mysql_num_rows($results)>0){
			echo "<p>{$_POST['contact']} is currently the primary contact listed for the following equipment:</p><div><ul>";
			while($devices=mysql_fetch_assoc($results)){
				echo "<li><a href=\"devices.php?deviceid={$devices['DeviceID']}\">{$devices['Label']}</a></li>";
			}
			$contacts=Contact::GetContactList($facDB);
			$contlist='<select id="primarycontact" name="primarycontact"><option value="0">Unassigned</option>';
			foreach($contacts as $contactid => $contact){
				$contlist.="<option value=\"$contact->ContactID\">$contact->LastName, $contact->FirstName</option>";
			}
			$contlist.='</select>';
			echo "</ul></div><div class=\"middle\"><button>=></button></div><div>Transfer primary contact to: $contlist</div>";
		}else{
			return 0;
		}
		exit;
	}
	if(isset($_POST['deptcheck'])){
		$contactid=intval($_POST['contactid']);
		$sql="SELECT * FROM fac_DeptContacts WHERE ContactID = $contactid";
		$results=mysql_query($sql, $facDB);
		if(mysql_num_rows($results)>0){
			$dept=new Department();
			$emptydept=array();
			echo "<p>Contact will be removed from the following departments</p><ul>";
			while($depts=mysql_fetch_assoc($results)){
				$dept->DeptID=$depts['DeptID'];
				$dept->GetDeptByID($facDB);
				$subresults=mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM fac_DeptContacts WHERE DeptID = $dept->DeptID;",$facDB));
				$subresults=$subresults[0];
				if($subresults<2){
					$emptydept[$dept->DeptID]=$dept->Name;
				}
				print "<li>$dept->Name ($subresults)</li>";
			}
			echo "</ul>";
			$dev=new Device();
			if(count($emptydept)){
				echo "<p>The following departments will be empty after this user is removed</p><ul>";
				foreach($emptydept as $deptid => $deptname){
					print "<li>$deptname";
					$dev->Owner=$deptid;
					$devices=$dev->GetDevicesbyOwner($facDB);
					if(count($devices)>0){
						print "<p>The following devices belong to $deptname:</p><ul>";
						foreach($devices as $dev){
							print "<li><a href=\"devices.php?deviceid=$dev->DeviceID\">$dev->Label</a></li>";
						}
						print "</ul>";
					}
					// check for racks owned by the soon to be deleted department
					$cablist=Cabinet::ListCabinets($facDB, $deptid);
					if(count($cablist)>0){
						print "<p>The following racks are assigned to $deptname:</p><ul>";
						foreach($cablist as $cab){
							print "<li><a href=\"cabinets.php?cabinetid=$cab->CabinetID\">$cab->Location</a></li>";
						}
						print "</ul>";
					}
					print "</li>";
				}
				print "</ul>";
			}
		}else{
			return 0;
		}
		exit;
	}
	// END - AJAX

	$formfix="";
	if(isset($_REQUEST['contactid']) && ($_REQUEST['contactid']>0)) {
		$contact->ContactID=(isset($_POST['contactid']) ? $_POST['contactid'] : $_GET['contactid']);
		$contact->GetContactByID($facDB);

		$formfix="?contactid=$contact->ContactID";
	}

	if(isset($_POST['action']) && (($_POST['action']=='Create') || ($_POST['action']=='Update'))){
		$contact->ContactID=trim($_POST['contactid']);
		$contact->UserID=trim($_POST['UserID']);
		$contact->LastName=trim($_POST['lastname']);
		$contact->FirstName=trim($_POST['firstname']);
		$contact->Phone1=trim($_POST['phone1']);
		$contact->Phone2=trim($_POST['phone2']);
		$contact->Phone3=trim($_POST['phone3']);
		$contact->Email=trim($_POST['email']);

		if($contact->LastName!=''){
			if($_POST['action']=='Create'){
					$contact->CreateContact($facDB);
			}else{
				$contact->UpdateContact($facDB);
			}
		}
		//Refresh object from db
		$contact->GetContactByID($facDB);
	}
	$contactList=$contact->GetContactList($facDB);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>

  <script type="text/javascript">
  	$(document).ready(function() {
		$('#cform').validationEngine({});
		$('button[name=deletecheck]').click(function(){
			$.post('', {contactid: $('#contactid').val(), deletecheck:'', contact: $('#contactid option:selected').text()}, function(data){
				$('#deletedialog').dialog({
					modal: true,
					minWidth: 600,
					maxWidth: 600,
					closeOnEscape: true,
					position: { my: "center", at: "center", of: window },
					autoOpen: false,
					buttons: {
						"Yes": function(){
							alert ('user clicked yes, submit something to remove the contact');
						},
						"No": function(){
							$(this).dialog("close");
						}
					}
				});
				$('#deletedialog').html('');
				if(data!=''){
					$('#deletedialog').html(data);
					$('#deletedialog').dialog("open");
				}
				$.post('', {contactid: $('#contactid').val(), deptcheck:''}, function(data){
					if(data!=''){
						$('#deletedialog').append(data);
						$('#deletedialog').dialog("close");
						$('#deletedialog').dialog("open");
					}
				});
			});
		});
	});
  </script>
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
    include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php print $config->ParameterArray['OrgName']; ?></h2>
<?php
echo '<h3>',_("Data Center Contact Detail"),'</h3>
<div class="center"><div>
<form id="cform" action="',$_SERVER['PHP_SELF'].$formfix,'" method="POST">
<div class="table">
<div>
   <div><label for="contactid">',_("Contact"),'</label></div>
   <div><input type="hidden" name="action" value="query"><select name="contactid" id="contactid" onChange="form.submit()">
   <option value=0>',_("New Contact"),'</option>';

	foreach($contactList as $contactRow ) {
		if($contact->ContactID == $contactRow->ContactID){$selected=' selected="selected"';}else{$selected='';}
		print "<option value=\"$contactRow->ContactID\"$selected>$contactRow->LastName, $contactRow->FirstName</option>";
	}
echo '	</select></div>
</div>
<div>
   <div><label for="UserID">',_("UserID"),'</label></div>
   <div><input type="text" class="validate[optional,minSize[1],maxSize[8]]" name="UserID" id="UserID" maxlength="8" value="',$contact->UserID,'"></div>
</div>
<div>
   <div><label for="lastname">',_("Last Name"),'</label></div>
   <div><input type="text" class="validate[required,minSize[1],maxSize[40]]" name="lastname" id="lastname" maxlength="40" value="',$contact->LastName,'"></div>
</div>
<div>
   <div><label for="firstname">',_("First Name"),'</label></div>
   <div><input type="text" class="validate[optional,minSize[1],maxSize[40]]" name="firstname" id="firstname" maxlength="40" value="',$contact->FirstName,'"></div>
</div>
<div>
   <div><label for="phone1">',_("Phone 1"),'</label></div>
   <div><input type="text" class="validate[optional,custom[phone]]" name="phone1" id="phone1" maxlength="20" value="',$contact->Phone1,'"></div>
</div>
<div>
   <div><label for="phone2">',_("Phone 2"),'</label></div>
   <div><input type="text" class="validate[optional,custom[phone]]" name="phone2" id="phone2" maxlength="20" value="',$contact->Phone2,'"></div>
</div>
<div>
   <div><label for="phone3">',_("Phone 3"),'</label></div>
   <div><input type="text" class="validate[optional,custom[phone]]" name="phone3" id="phone3" maxlength="20" value="',$contact->Phone3,'"></div>
</div>
<div>
   <div><label for="email">',_("Email"),'</label></div>
   <div><input type="text" class="validate[optional,custom[email],maxSize[80]]" size="50" name="email" id="email" maxlength="80" value="',$contact->Email,'"></div>
</div>
<div class="caption">';

	if($contact->ContactID >0){
		echo '   <button type="submit" name="action" value="Update">',_("Update"),'</button><button type="button" name="deletecheck">',_("Delete"),'</button>';
	}else{
		echo '   <button type="submit" name="action" value="Create">',_("Create"),'</button>';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
</div></div>

<div id="deletedialog" title="Continue removing contact?"></div>

<?php echo '<a href="index.php">[ ',_("Return to Main Menu"),' ]</a>'; ?>
</div> <!-- END div.main -->
</div> <!-- END div.page -->
</body>
</html>
