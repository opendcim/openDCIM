<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

	$timestamp=time();
	$salt=md5('unique_salt' . $timestamp);

	if(isset($_POST['dir'])){
		$array=array();
		$path=(in_array($_POST['dir'],array('drawings','pictures')))?$_POST['dir']:'';
		if(is_dir($path)){
			$dir=scandir($path);
			foreach($dir as $i => $f){
				if(is_file($path.DIRECTORY_SEPARATOR.$f) && ($f!='.' && $f!='..')){
					@$imageinfo=getimagesize($path.DIRECTORY_SEPARATOR.$f);
					if(preg_match('/^image/i', $imageinfo['mime'])){
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
  <script type="text/javascript" src="scripts/jquery.uploadifive.min.js"></script>
  <script type="text/javascript" src="scripts/common.js"></script>
</head>
<body>
<div id="header"></div>
<div class="page imagem">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h2><?php echo __("OpenDCIM Image File Management");?></h2>

<?php
// Only show the device pictures if they have global write access or site admin.
if($user->SiteAdmin || $user->WriteAccess){
?>

<div class="center"><div>
<div class="heading"><?php print __("Device Type Pictures");?></div>
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
			if(data!='1'){
				// something broke, deal with it
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
if($user->SiteAdmin){
?>

<div class="center"><div>
<div class="heading"><?php print __("Datacenter / Container Drawings");?></div>
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
			if(data!='1'){
				// something broke, deal with it
			}else{
				// fuck yeah, reload the thumbnails
				reload($(this).data('dir'));
			}
		}
    });
});
</script>
</div></div><!-- END div.center -->

<?php } ?>


</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
	$('.center input').each(function(){
		reload($(this).data('dir'));
	});
</script>
</body>
</html>
