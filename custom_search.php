<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Data Center Operations Custom Search");

  $model = new Device();
  $availFieldList = get_object_vars( $model );
  // Append the list of DeviceCustomAttributes
  $attrList=DeviceCustomAttribute::GetDeviceCustomAttributeList();
  foreach( $attrList as $ca ) {
  	$availFieldList[$ca->Label] = true;
  }
  // Remove the Rights and CustomValues fields since they are not directly relatable
  unset($availFieldList["Rights"]);
  unset($availFieldList["CustomValues"]);

  ksort($availFieldList);

  if ( isset( $_POST["devicefield"]) && count( $_POST["devicefield"]>0 )) {
    $searchString = "";
    foreach( $_POST["devicefield"] as $key=>$val ) {
      if ( $_POST["devicefield"][$key]!="" && $_POST["criteria"][$key]!="" ) {
        // If not the first variable, add an ampersand to indicate an additional parameter
        if ( $searchString != "" ) {
          $searchString .= "&";
        }
        $searchString .= $_POST["devicefield"][$key] . "=" . $_POST["criteria"][$key];
      }
    }

    if ( $searchString !="" ) {
      header('Location: search.php?key=dev&loose&'.$searchString);
      exit;
    }
  }

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Custom Search Builder</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript">
  $(document).ready(function() {
	$('select[name=searchoptions]').on('change',function(e){
		if(e.currentTarget.value != 0){
			AddRow($('select[name=searchoptions] > option:selected'));
		}
	});
	$('#customsearch').on('click',function(e){
		var searchstring='search.php?key=dev&loose';
		$('#searchfields > div > div > :input').each(function(){
			if(this.value){
				searchstring+="&"+this.name+"="+this.value;
			}
		});
		newtab(searchstring);
	});
	function BuildSelect(data,name){
		select=$('<select />').attr('name',name);
		for(var i in data){
			select.append($('<option />').text(data[i].Location).val(data[i].CabinetID));
		}
		return select;
	}
	function SortSelect(){
		var sel = $('select[name=searchoptions]');
		sel.html(sel.find('option').sort(function(x, y) {
			return $(x).text() > $(y).text() ? 1 : -1;
		}));
		sel.prepend(sel.find('option[value=0]'));
		sel.val(0);
	}
	function AddRow(option){
		var row=$('<div />');
		var addrem=$('<div />').data('original',option).html('<img src="images/del.gif">').click(
			function(e) {
				$('select[name=searchoptions]').append($(e.currentTarget).data('original'));
        		$(this).parent().remove();
				$('select[name=searchoptions]').val(0);
				SortSelect();
			}
		);
		var label=$('<div />').html(option.val());
		var searchfield=$('<div />');
		switch(option.val()) {
			case "BackSide":
				select=$('<select />').attr('name','BackSide');
				select.append($('<option />').text("False").val("0"));
				select.append($('<option />').text("True").val("1"));
				searchfield.append(select);
				break;
			case "Cabinet":
				$.get('api/v1/cabinet').done(function(data){
					var selectlist=BuildSelect(data.cabinet,option.val());
					searchfield.append(selectlist);
				});
				break;
			case "DeviceType":
				var devTypes = Array( 'Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure','CDU','Sensor' );
				select=$('<select />').attr('name','DeviceType');
				for(var i in devTypes){
					select.append($('<option />').text(devTypes[i]).val(devTypes[i]));
				}
				searchfield.append(select);
				break;
			case "HalfDepth":
				select=$('<select />').attr('name','HalfDepth');
				select.append($('<option />').text("False").val("0"));
				select.append($('<option />').text("True").val("1"));
				searchfield.append(select);
				break;
			case "Hypervisor":
				var hypTypes = Array( 'ESX', 'ProxMox', 'None' );
				select=$('<select />').attr('name','Hypervisor');
				for(var i in hypTypes){
					select.append($('<option />').text(hypTypes[i]).val(hypTypes[i]));
				}
				searchfield.append(select);
				break;
			case "Owner":
				$.get('api/v1/department').done(function(data){
					select=$('<select />').attr('name','Owner');
					for(var i in data.department){
						select.append($('<option />').text(data.department[i].Name).val(data.department[i].DeptID));
					}
					searchfield.append(select);
				});
				break;
			case "PrimaryContact":
				$.get('api/v1/people').done(function(data){
					select=$('<select />').attr('name','PrimaryContact');
					for(var i in data.people){
						select.append($('<option />').text(data.people[i].LastName+', '+data.people[i].FirstName).val(data.people[i].PersonID));
					}
					searchfield.append(select);
				});
				break;
			case "Status":
				$.get('api/v1/devicestatus').done(function(data){
					select=$('<select />').attr('name','Status');
					for(var i in data.devicestatus){
						select.append($('<option />').text(data.devicestatus[i].Status).val(data.devicestatus[i].StatusID));
					}
					searchfield.append(select);
				});
				break;
			default:
				searchfield.append($('<input>').attr('name',option.val()));
		}
		row.append(addrem).append(label).append(searchfield);
		row.insertBefore($('select[name=searchoptions]').parent('div').parent('div'));
		option.remove();
	}
	function newtab(searchlink){
		var poopup=window.open(searchlink);
		poopup.focus();
	}
  });
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page index">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<div class="center"><div>
<h3>Device Criteria</h3>
<?php
echo '<form action="',$_SERVER["SCRIPT_NAME"].$formpatch,'" method="POST">
<div class="table" id="searchfields">
  <div>
    <div></div>
    <div>',__("Search Field"),'</div>
    <div>',__("Contains"),'</div>
  </div>';

    echo '  <div>
    <div></div>
    <div><select name="searchoptions"><option value="0" selected>',__("Select search field"),'</option>';

    foreach($availFieldList as $tmpField=>$val){
      print "\t\t\t<option value=\"$tmpField\">$tmpField</option>\n";
    }

    echo '    </select></div>
    <div></div>
  </div>
    <div>
    <div></div>
    <div></div>
  </div>
  <div class="caption">
    <button type="button" id="customsearch">',__("Search"),'</button>
  </div>
</div><!-- END div.table --> '
?>
</form>


<!-- CONTENT GOES HERE -->



</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
