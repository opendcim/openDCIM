<?php
	require_once('db.inc.php');
	require_once('facilities.inc.php');

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

	$facimageselect='<div class="preview" id="drawings">';

	$path='./drawings';
	$dir=scandir($path);
	foreach($dir as $i => $f){
		if(is_file($path.DIRECTORY_SEPARATOR.$f)){
			@$imageinfo=getimagesize($path.DIRECTORY_SEPARATOR.$f);
			if(preg_match('/^image/i', $imageinfo['mime'])){
				$facimageselect.="<div><div style=\"background-image: url('drawings/$f');\"></div><div class=\"filename\">$f</div></div>\n";
			}
		}
	}
	$facimageselect.="</div>";

	$devimageselect='<div class="preview" id="pictures">';

	$path='./pictures';
	$dir=scandir($path);
	foreach($dir as $i => $f){
		if(is_file($path.DIRECTORY_SEPARATOR.$f)){
			@$imageinfo=getimagesize($path.DIRECTORY_SEPARATOR.$f);
			if(preg_match('/^image/i', $imageinfo['mime'])){
				$devimageselect.="<div><div style=\"background-image: url('pictures/$f');\"></div><div class=\"filename\">$f</div></div>\n";
			}
		}
	}
	$devimageselect.="</div>";

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/uploadifive.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.uploadifive.min.js"></script>
  <script type="text/javascript">
	function makeThumb(path,file){
		return $('<div>').append($('<div>').css('background-image', 'url('+path+'/'+file+')')).append($('<div>').addClass('filename').text(file))
	}
	function reload(target){
		$('#'+target).children().remove();
		$.post('',{dir: target}).done(function(a){
			$.each(a,function(dir,files){
				$.each(files,function(i,file){
					$('#'+target).append(makeThumb(dir,file));
				});
			});
		});
	}


  </script>
</head>
<body>
<div id="header"></div>
<div class="page imagem">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h2>OpenDCIM Image File Management</h2>
<div class="center"><div>
<div class="heading">Device Type Pictures</div>
<input type="file" name="dev_file_upload" data-dir="pictures" id="dev_file_upload" />

<script type="text/javascript">
<?php $timestamp = time();?>
$(function() {
    $('#dev_file_upload').uploadifive({
		'formData' : {
			'timestamp' : '<?php echo $timestamp;?>',
			'token'     : '<?php echo md5('unique_salt' . $timestamp);?>',
			'dir'		: 'pictures'
		},
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
<?php echo $devimageselect; ?>
</div></div><!-- END div.center -->

<div class="center"><div>
<div class="heading">Datacenter / Room Drawings</div>
<input type="file" name="drawing_file_upload" data-dir="drawings" id="drawing_file_upload" />

<script type="text/javascript">
<?php $timestamp = time();?>
$(function() {
    $('#drawing_file_upload').uploadifive({
		'formData' : {
				'timestamp' : '<?php echo $timestamp;?>',
				'token'     : '<?php echo md5('unique_salt' . $timestamp);?>',
				'dir'		: 'drawings'
			},
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
</div><div>
<?php echo $facimageselect; ?>
</div></div><!-- END div.center -->

</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
