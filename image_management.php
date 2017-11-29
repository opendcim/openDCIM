<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$subheader=__("OpenDCIM Image File Management");

	$timestamp=time();
	$salt=md5('unique_salt' . $timestamp);

	if(isset($_POST['dir'])){
		$array=array();
		$path=(in_array($_POST['dir'],array('drawings','pictures')))?$_POST['dir']:'';
		if(is_dir($path)){
			$dir=scandir($path);
			foreach($dir as $i => $f){
				if(is_file($path.DIRECTORY_SEPARATOR.$f) && ($f!='.' && $f!='..' && $f!='P_ERROR.png')){
					$mimeType=mime_content_type($path.DIRECTORY_SEPARATOR.$f);
					if(preg_match('/^image/i', $mimeType)){
						$array[$path][]=$f;
					}
				}
			}
		}
		header('Content-Type: application/json');
		echo json_encode($array);
		exit;
	}

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php echo __("openDCIM Data Center Inventory");?></title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/uploadifive.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.uploadifive.js"></script>
  <script type="text/javascript" src="scripts/common.js?v<?php echo filemtime('scripts/common.js');?>"></script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page imagem">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">

<?php
// Only show the device pictures if they have global write access or site admin.
if($person->SiteAdmin || $person->WriteAccess){
?>

<div class="center"><div>
<div class="heading"><?php print __("Device Pictures");?></div>
<input type="file" name="dev_file_upload" data-dir="pictures" id="dev_file_upload" />

<script type="text/javascript">
$(function() {
    $('#dev_file_upload').uploadifive({
		'formData' : {
			'timestamp' : '<?php echo $timestamp;?>',
			'token'     : '<?php echo $salt;?>',
			'dir'		: 'pictures'
		},
		'removeCompleted' : true,
		'checkScript' : 'scripts/check-exists.php',
		'uploadScript' : 'scripts/uploadifive.php',
		'onUploadComplete'	: function(file, data) {
			data=$.parseJSON(data);
			if(data.status=='1'){
				// something broke, deal with it
				var toast=$('<div>').addClass('uploadifive-queue-item complete');
				var close=$('<a>').addClass('close').text('X').click(function(){$(this).parent('div').remove();});
				var span=$('<span>');
				var error=$('<div>').addClass('border').css({'margin-top': '2px', 'padding': '3px'}).text(data.msg);
				toast.append(close);
				toast.append($('<div>').append(span.clone().addClass('filename').text(file.name)).append(span.clone().addClass('fileinfo').text(' - Error')));
				toast.append(error);
				$('#uploadifive-'+this[0].id+'-queue').append(toast);
			}else{
				// fuck yeah, reload the thumbnails
				reload($(this).data('dir'));
			}
		}
    });
});
</script>
</div><div>

<div class="preview" id="pictures">
</div>

</div></div><!-- END div.center -->

<?php
}

// Only show the site drawings if they have site admin rights.
if($person->SiteAdmin){
?>

<div class="center"><div>
<div class="heading"><?php print __("Infrastructure Drawings");?></div>
<input type="file" name="drawing_file_upload" data-dir="drawings" id="drawing_file_upload" />

</div><div>

<div class="preview" id="drawings">
</div>
<script type="text/javascript">
$(function() {
    $('#drawing_file_upload').uploadifive({
		'formData' : {
				'timestamp' : '<?php echo $timestamp;?>',
				'token'     : '<?php echo $salt;?>',
				'dir'		: 'drawings'
			},
		'removeCompleted' : true,
		'checkScript'		: 'scripts/check-exists.php',
		'uploadScript'		: 'scripts/uploadifive.php',
		'onUploadComplete'	: function(file, data) {
			data=$.parseJSON(data);
			if(data.status=='1'){
				// something broke, deal with it
				var toast=$('<div>').addClass('uploadifive-queue-item complete');
				var close=$('<a>').addClass('close').text('X').click(function(){$(this).parent('div').remove();});
				var span=$('<span>');
				var error=$('<div>').addClass('border').css({'margin-top': '2px', 'padding': '3px'}).text(data.msg);
				toast.append(close);
				toast.append($('<div>').append(span.clone().addClass('filename').text(file.name)).append(span.clone().addClass('fileinfo').text(' - Error')));
				toast.append(error);
				$('#uploadifive-'+this[0].id+'-queue').append(toast);
			}else{
				// fuck yeah, reload the thumbnails
				reload($(this).data('dir'));
			}
		}
    });
});
</script>
</div></div><!-- END div.center -->
<?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
<?php } ?>


</div><!-- END div.main -->
</div><!-- END div.page -->

<?php 
echo '<div id="delete-confirm" title="'.__("Delete image file?").'" class="hide">
	<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>'.__("This image will be permanently deleted and cannot be recovered. Are you sure?").'</p>
</div>';
?>

<script type="text/javascript">
	$('.center input').each(function(){
		reload($(this).data('dir'));
	});
	timestamp="<?php echo $timestamp; ?>";
	token="<?php echo $salt; ?>";
</script>
</body>
</html>
