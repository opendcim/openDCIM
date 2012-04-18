<?php
/* All functions contained herein will be general use functions */

/* 
Regex to make sure a valid URL is in the config before offering options for contact lookups
http://phpcentral.com/208-url-validation-in-php.html

Example Usage:
	if(eregi($urlregex, "url to test")){ echo "Yup it's a URL"; }else{ echo "fail"; }

*/

$urlregex = "^(https?|ftp)\:\/\/([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-z0-9+\$_-]\.?)+)*\/?(\?[a-z+&\$_.-][a-z0-9;:@/&%=+\$_.-]*)?(#[a-z_.-][a-z0-9+\$_.-]*)?\$";

//Convert hex color codes to rgb values
function html2rgb($color){
	if($color[0]=='#'){
		$color=substr($color,1);
	}
	if(strlen($color)==6){
		list($r,$g,$b)=array($color[0].$color[1],$color[2].$color[3],$color[4].$color[5]);
	}elseif(strlen($color)==3){
		list($r,$g,$b)=array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
	}else{
		return false;
	}
	$r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

	return array($r, $g, $b);
}

/*
Used to ensure a properly formatted url in use of instances header("Location")

Example usage:
	header("Location: ".redirect());
	exit;
			- or -
	header("Location: ".redirect('storageroom.php'));
	exit;
			- or -
	$url=redirect("index.php?test=23")
	header("Location: $url");
	exit;
*/
function path(){
	$path=explode("/",$_SERVER['REQUEST_URI']);
	unset($path[(count($path)-1)]);
	$path=implode("/",$path);
	return $path;
}
function redirect($target = null) {
	// No argument was passed.  If a referrer was set, send them back to whence they came.
	if(is_null($target)){
		if(isset($_SERVER["HTTP_REFERER"])){
			return $_SERVER["HTTP_REFERER"];
		}else{
			// No referrer was set so send them to the root application directory
			$target=path();
		}
	}else{
		//Try to ensure that a properly formatted uri has been passed in.
		if(substr($target, 4)!='http'){
			//doesn't start with http or https check to see if it is a path
			if(substr($target, 1)!='/'){
				//didn't start with a slash so it must be a filename
				$target=path()."/".$target;
			}else{
				//started with a slash let's assume they know what they're doing
				$target=path().$target;
			}
		}else{
			//Why the heck did you send a full url here instead of just doing a header?
			return $target;
		}
	}
	if($_SERVER["HTTPS"]=='on'){
		$url = "https://".$_SERVER['HTTP_HOST'].$target;
	}else{
		$url = "http://".$_SERVER['HTTP_HOST'].$target;
	}
	return $url;
}
?>
