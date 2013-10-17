<?php
/* All functions contained herein will be general use functions */

/* This is used on every page so we might as well just init it once */
$user=new User();
$user->UserID = @$_SERVER['REMOTE_USER'];
$user->GetUserRights();
	
/* 
Regex to make sure a valid URL is in the config before offering options for contact lookups
http://www.php.net/manual/en/function.preg-match.php#93824

Example Usage:
	if(isValidURL("http://test.com"){//do something}

*/
function isValidURL($url){
	$urlregex="((https?|ftp)\:\/\/)?"; // SCHEME
	$urlregex.="([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
	$urlregex.="([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
	$urlregex.="(\:[0-9]{2,5})?"; // Port
	$urlregex.="(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
	$urlregex.="(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
	$urlregex.="(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor 
	if(preg_match("/^$urlregex$/",$url)){return true;}
}

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
	if(array_key_exists('HTTPS', $_SERVER) && $_SERVER["HTTPS"]=='on') {
		$url = "https://".$_SERVER['HTTP_HOST'].$target;
	} else {
		$url = "http://".$_SERVER['HTTP_HOST'].$target;
	}
	return $url;
}

// search haystack for needle and return an array of the key path,
// FALSE otherwise.
// if NeedleKey is given, return only for this key
// mixed ArraySearchRecursive(mixed Needle,array Haystack[,NeedleKey[,bool Strict[,array Path]]])

function ArraySearchRecursive($Needle,$Haystack,$NeedleKey="",$Strict=false,$Path=array()) {
	if(!is_array($Haystack))
		return false;
	foreach($Haystack as $Key => $Val) {
		if(is_array($Val)&&$SubPath=ArraySearchRecursive($Needle,$Val,$NeedleKey,$Strict,$Path)) {
			$Path=array_merge($Path,Array($Key),$SubPath);
			return $Path;
		}elseif((!$Strict&&$Val==$Needle&&$Key==(strlen($NeedleKey)>0?$NeedleKey:$Key))||($Strict&&$Val===$Needle&&$Key==(strlen($NeedleKey)>0?$NeedleKey:$Key))) {
			$Path[]=$Key;
			return $Path;
		}
	}
	return false;
}

/*
 * Sort multidimentional array
 *
 * $array = sort2d ( $array, 'key to sort on')
 */
function sort2d ($array, $index){
	//Create array of key and label to sort on.
	foreach(array_keys($array) as $key){$temp[$key]=$array[$key][$index];}
	//Case insensative natural sorting of temp array.
	natcasesort($temp);
	//Rebuild original array using the newly sorted order.
	foreach(array_keys($temp) as $key){$sorted[$key]=$array[$key];}
	return $sorted;
}  

/*
 * Define multibyte string functions in case they aren't present
 *
 */

if(!extension_loaded('mbstring')){
	function mb_strtoupper($text,$encoding=null){
		return strtoupper($text);
	}
	function mb_strtolower($text,$encoding=null){
		return strtolower($text);
	}
	function mb_convert_case($string, $transform, $locale){
		switch($transform){
			case 'MB_CASE_UPPER':
				$string=mb_strtoupper($string);
				break;
			case 'MB_CASE_LOWER':
				$string=mb_strtolower($string);
				break;
			case 'MB_CASE_TITLE':
				$string=ucwords(mb_strtolower($string));
				break;
		}
		return $string;
	}
}

/*
 * Transform text to uppercase, lowercase, initial caps, or do nothing based on system config
 * 2nd parameter is optional to override the system default
 *
 */
function transform($string,$method=null){
	$config=new Config();
	$method=(is_null($method))?$config->ParameterArray['LabelCase']:$method;
	switch ($method){
		case 'upper':
			$string=mb_convert_case($string, MB_CASE_UPPER, "UTF-8");
			break;
		case 'lower':
			$string=mb_convert_case($string, MB_CASE_LOWER, "UTF-8");
			break;
		case 'initial':
			$string=mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
			break;
		default:
			// Don't you touch my string.
	}
	return $string;
}

/*
 * Language internationalization slated for v2.0
 *
 */
if(isset($_COOKIE["lang"])){
	$locale=$_COOKIE["lang"];
}else{
	$locale=$config->ParameterArray['Locale'];
}

if(extension_loaded('gettext')){
	if(isset($locale)){
		setlocale(LC_ALL,$locale);
		putenv("LC_ALL=$locale");
		bindtextdomain("openDCIM","./locale");

		$codeset='utf8';
		if(isset($codeset)){
			bind_textdomain_codeset("openDCIM",$codeset);
		}
		textdomain("openDCIM");
	}
}

function GetValidTranslations() {
	$path='./locale';
	$dir=scandir($path);
	$lang=array();
	global $locale;

	foreach($dir as $i => $d){
		// get list of directories in locale that aren't . or ..
		if(is_dir($path.DIRECTORY_SEPARATOR.$d) && $d!=".." && $d!="."){
			// check the list of valid directories above to see if there is an openDCIM translation file present
			if(file_exists($path.DIRECTORY_SEPARATOR.$d.DIRECTORY_SEPARATOR."LC_MESSAGES".DIRECTORY_SEPARATOR."openDCIM.mo")){
				// build array of valid language choices
				$lang[$d]=$d;
			}
		}
	}
	return $lang;
}

function __($string){
	if(extension_loaded('gettext')){
		return _($string);
	}else{
		return $string;
	}
}

/*
	Check if we are doing a new install or an upgrade has been applied.  
	If found then force the user into only running that function.

	To bypass the installer check from running, simply add
	$devMode = true;
	to the db.inc.php file.
*/

if(isset($devMode)&&$devMode){
	// Development mode, so don't apply the upgrades
}else{
	if(file_exists("install.php") && basename($_SERVER['PHP_SELF'])!="install.php" ){
		// new installs need to run the install first.
		header("Location: ".redirect('install.php'));
	}
}

/**
 * Parse a string which contains numeric or alpha repetition specifications. It
 *  returns an array of tokens or a message of the parsing exception encountered
 *  with the location of the failing character.
 *
 * @param string $pat
 * @return mixed
 */
function parseGeneratorString($pat)
{
    $result = array();
    $cstr = '';
    $escape = false;
    $patLen = strlen($pat);

    for ($i=0; $i < $patLen; $i++) {
        if ($escape) {
            $cstr .= $pat[$i];
            $escape = false;
            continue;
        }
        if ($pat[$i] == '\\') {
            $escape = true;
            continue;
        }
        if ($pat[$i] == '(') {
            // current string complete, start of a pattern
            $result[] = array('String', array($cstr));
            $cstr = '';
            list($i, $patSpec, $msg) = parsePatternSpec($pat, $patLen, ++$i);
            if (! $patSpec) {
                echo 'Error: Parse pattern return error - \'', $msg, '\' ', $i, PHP_EOL;
                return array(null, $msg, $i);
            }
            $result[] = $patSpec;
            continue;
        }
        $cstr .= $pat[$i];
    }
    if ($cstr != '') {
        $result[] = array('String', array($cstr));
    }

    return array($result, '', $i);
}

/**
 * Parse the numeric or alpha pattern specification and return the specification
 *  token or a the message explaining the exception encountered and the position
 *  of the character where the exception was detected.
 *
 * @param type $pat
 * @param type $patLen
 * @param type $idx
 * @return type
 */
function parsePatternSpec(&$pat, $patLen, $idx) {
    $stopChars = array(';', ')');
    $patSpec = array();
    $msg = 'Wrong pattern specification';

    $ValueStr = '';
    $token = 'StartValue';
    $startValue = null;
    $increment = 1;
    $patType = '';

    for ($i = $idx; $i < $patLen; ++$i) {
        if (ctype_digit($pat[$i])) {
            list($ValueStr, $i, $msg) = getNumericString($pat, $i, $stopChars);
            $patType = 'numeric';
        } elseif (ctype_alpha($pat[$i])) {
            list($ValueStr, $i, $msg) = getAlphaString($pat, $i, $stopChars);
            $patType = 'alpha';
        } else {
            if ($token == 'StartValue') {
                $msg = 'No start value detected.';
            } elseif ($token == 'Increment') {
                $msg = 'Missing increment value.';
            } else {
                $msg = 'Unexpected character \'' . $pat[$i] . '\'';
            }
            return array($i, null, $msg);
        }
        if (($token == 'StartValue') and ($i >= $patLen)) {
            $msg = 'Incomplete pattern specification, missing stop character [\''
                . implode('\',\'', $stopChars) . '\']';
            return array($i, null, $msg);
        }
        if (($token == 'StartValue') and (in_array($pat[$i], $stopChars))) {
            if ($ValueStr === '') {
                $msg = 'Missing start value';
                return array($i, null, $msg);
            }
            if ($patType == 'numeric') {
                $startValue = intval($ValueStr);
            } else {
                $startValue = $ValueStr;
            }
            $ValueStr = '';
            if ($pat[$i] == ')') {
                $token = 'right_parenthesis';
            } elseif ($pat[$i] == ';') {
                $token = 'Increment';
                continue;
            }
        }
        if (($token == 'Increment')) {
            if ($patType == 'numeric') {
                $increment = intval($ValueStr);
            } else {
                $msg = 'Increment must be a number, wrong value \'' . $ValueStr . '\'';
                return array($i, null, $msg);
            }
        }
        if ($pat[$i] == ')') {
            $patSpec = array('Pattern', array($patType, $startValue, $increment));
            break;
        }
        $msg = 'Unexpected character \'' . $pat[$i] . '\' for token \'' . $token . '\'.';
        return array($i, null, $msg);
    }
    if ((! $patSpec) and ($token == 'Increment')) {
        $msg = 'Incomplete increment specification';
        return array($i, null, $msg);
    }
    return array($i, $patSpec, $msg);
}

/**
 * Parse a numeric string.
 *
 * @param string $pat
 * @param int $idx
 * @param array $stopChars
 * @return mixed
 */
function getNumericString(&$pat, $idx, &$stopChars) {
    $strValue = '';
    for ($i=$idx; $i < strlen($pat); $i++) {
        $char = $pat[$i];
        if (in_array($char, $stopChars)) {
                return array(intval($strValue), $i, 'NumericValue');
        }
        if (ctype_digit($char)) {
            $strValue .= $char;
        } else {
            $msg = 'Non-numeric character encountered \'' . $char . '\' ';
            return array(null, $i, $msg);
        }
    }
    $msg = 'Stop character not encountered [\'' . implode('\',\'', $stopChars) . '\'].';
    return array(null, $i, $msg);
}

/**
 * Parse an alpha string.
 *
 * @param string $pat
 * @param int $idx
 * @param array $stopChars
 * @return mixed
 */
function getAlphaString(&$pat, $idx, &$stopChars) {
    $strValue = '';
    $patType = 'alpha';
    $escaped = false;

    for ($i=$idx; $i < strlen($pat); $i++) {
        $char = $pat[$i];
        if ($char == '\\') {
            $escaped= true;
            continue;
        }
        if ($escaped) {
            $strValue .= $char;
            $escaped = false;
            continue;
        }
        if (in_array($char, $stopChars)) {
            return array($strValue, $i, 'AlphaValue');
        }
        if (ctype_alpha($char)) {
            $strValue .= $char;
        } else {
            $msg = 'Non-numeric character encountered \'' . $char . '\' ';
            return array(null, $i, $msg);
        }
    }
    $msg = 'Stop character not encountered \'' . implode(',', $stopChars) . '\'.';
    return array(null, $i, $msg);
}

// Code provided for num2alpha and alpha2num in
// http://stackoverflow.com/questions/5554369/php-how-to-output-list-like-this-aa-ab-ac-all-the-way-to-zzzy-zzzz-zzzza
//function num2alpha($n, $shift=0) {
//    for ($r = ''; $n >= 0; $n = intval($n / 26) - 1)
//        $r = chr($n % 26 + 0x41 + $shift) . $r;
//    return $r;
//}

/**
 * Return the alpha represenation of the integer based on Excel offset.
 *
 * @param int $n
 * @param int $offset
 * @return string
 */
function num2alpha($n, $offset = 0x40) {
    for ($r = ''; $n >= 0; $n = intval($n / 26) - 1) {
        $r = chr($n % 26 + ($offset + 1)) . $r;
    }
    return $r;
}

/**
 * Return the numeric representation the alpha string  based on Excel offset.
 * @param string $a
 * @param int $offset
 * @return int
 */
function alpha2num($a, $offset = 0x40)
{
    $base = 26;
    $l = strlen($a);
    $n = 0;
    for ($i = 0; $i < $l; $i++) {
        $n = $n*$base + ord($a[$i]) - $offset;
    }
    return $n-1;
}

/**
 * Take the generator string specification produced by parseGeneratorString and
 *  return a list of strings where the patterns are instantiated.
 *
 * @param array $patSpecs
 * @param int $count
 * @return array
 */
function generatePatterns($patSpecs, $count) {
    $patternList = array();
    for ($i=0; $i < $count; $i++) {
        $str = '';
        foreach ($patSpecs as $pat) {
            if ($pat) {
               if ($pat[0] == 'String') {
                    $str .= $pat[1][0];
                } elseif ($pat[0] == 'Pattern') {
                    if ($pat[1][0] == 'numeric') {
                        $str .= (integer)($pat[1][1] + $i*$pat[1][2]);
                    } elseif ($pat[1][0] == 'alpha') {
                        $charIntVal = ord($pat[1][1]);
                        if (($charIntVal >= 65) and ($charIntVal <= 90)) {
                            $offset = 0x40;
                            $charIntVal = alpha2num($pat[1][1]);
                        } else {
                            $offset = 0x60;
                            $charIntVal = alpha2num($pat[1][1], $offset);
                        }
                        $str .= num2alpha(($charIntVal + $i*$pat[1][2]), $offset);
                    }
                }
            }
        }
        $patternList[] = $str;
    }

    return $patternList;
}

function locale_number( $number, $decimals=2 ) {
    $locale = localeconv();
    return number_format($number,$decimals,
               $locale['decimal_point'],
               $locale['thousands_sep']);
}
?>
