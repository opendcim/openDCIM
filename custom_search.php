<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

//	Uncomment these if you need/want to set a title in the header
//	$header=__("");
	$subheader=__("Data Center Operations Custom Search");

  $model = new Device();
  $availFieldList = get_object_vars( $model );
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
    $('#newline').click(function (){
      $(this).parent().prev().clone().insertBefore($(this).parent()).children('div:first-child').html('<img src="images/del.gif">').click(function() {
        $(this).parent().remove();
      });
    });
    $('.remove').click(function (){
      if(!$(this).next().next().children('input').attr('oldcount')){
        $(this).children('img').after('<input type="hidden" name="'+$(this).next().children("select").attr("name")+'" value="'+$(this).next().children("select").val()+'">');
        $(this).children('img').after('<input type="hidden" name="'+$(this).next().next().children("input").attr("name")+'" value="-1">');
        $(this).next().children('select').attr('disabled','disabled');
        $(this).next().next().children('input').attr({
          'oldcount': $(this).next().next().children('input').val(),
          'value': '-1',
          'disabled': 'disabled'
        });
      }else{
        $(this).children('input').remove();
        $(this).next().children('select').removeAttr('disabled');
        $(this).next().next().children('input').val($(this).next().next().children('input').attr('oldcount')).removeAttr('oldcount').removeAttr('disabled');
      }
    });
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
<div class="table">
  <div>
    <div></div>
    <div>',__("Search Field"),'</div>
    <div>',__("Contains"),'</div>
  </div>';

    echo '  <div>
    <div></div>
    <div><select name="devicefield[]"><option value="0" selected>',__("Select search field"),'</option>';

    foreach($availFieldList as $tmpField=>$val){
      print "\t\t\t<option value=\"$tmpField\">$tmpField</option>\n";
    }

    echo '    </select></div>
    <div><input class="criteria" name="criteria[]" type="text" size=20 maxlength=40></div>
  </div>
    <div>
    <div id="newline"><img src="images/add.gif" alt="add new row"></div>
    <div></div>
    <div></div>
  </div>
  <div class="caption">
    <button type="submit" name="action" value="search">',__("Search"),'</button>
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
