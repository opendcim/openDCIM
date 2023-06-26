<?php

// Pre-Flight check
	$tests=array();
	$errors=0;

	if(strtolower(PHP_OS)=='linux'){
		$tests['os']['state']="good";
		$tests['os']['message']='OS Detected: '.PHP_OS;
	}else{
		$tests['os']['state']="good";
		$tests['os']['message']='OS Detected: '.PHP_OS.' We strongly recommend against running this on anything other than Linux.';
	}

	if (extension_loaded('mbstring')) {
		$tests['php_mbstring']['state']="good";
		$tests['php_mbstring']['message']='';
	}else{
		$tests['php_mbstring']['state']="fail";
		$tests['php_mbstring']['message']='PHP is missing the <a href="http://php.net/mbstring">mbstring extension</a>';
		$errors++;
	}

	if(extension_loaded('gettext')) {
		$tests['php_gettext']['state']="good";
		$tests['php_gettext']['message']='';

		$path='./locale';
		$dir=scandir($path);
		$lang=array();
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

		$locales=array();
		foreach(explode("\n",trim(shell_exec('locale -a | grep -i utf'))) as $line){
			$locales[]=substr($line, 0, strpos($line, '.'));
		}
		if(count($locales)>1){
			$tests['php_gettext']['message'].="Locales detected: ";
			foreach(array_intersect($locales,$lang) as $locale){
				$tests['php_gettext']['message'].="$locale, ";
			}
		}else{
			$tests['php_gettext']['state']="fail";
			$tests['php_gettext']['message']='Gettext is detected but we cannot verify that you have the appropriate locales loaded and available. <a href="http://wiki.opendcim.org/wiki/index.php/Translation">http://wiki.opendcim.org/wiki/index.php/Translation</a>';
		}
	}else{
		$tests['php_gettext']['state']="fail";
		$tests['php_gettext']['message']='PHP is missing the <a href="http://php.net/manual/book.gettext.php">Gettext extension</a>. Please install it.';
	}

	if(extension_loaded('snmp')) {
		$tests['php_snmp']['state']="good";
		$tests['php_snmp']['message']='';
	}else{
		$tests['php_snmp']['state']="fail";
		$tests['php_snmp']['message']='PHP is missing the <a href="http://php.net/manual/book.snmp.php">snmp extension</a>. Please install it.';
	}

	if(extension_loaded('gd')) {
		$tests['php_gd']['state']="good";
		$tests['php_gd']['message']='';
	}else{
		$tests['php_gd']['state']="warning";
		$tests['php_gd']['message']='PHP is missing the <a href="http://php.net/manual/en/book.image.php">gd extension</a>. Please install it. Some reports will fail if this isn\'t present';
	}

	if(function_exists('utf8_decode')){
		$tests['php_xml']['state']="good";
		$tests['php_xml']['message']='';
	}else{
		$tests['php_xml']['state']="fail";
		$tests['php_xml']['message']='PHP is missing the <a href="http://us3.php.net/manual/en/book.xml.php">XML Parser</a>.  Please install it.<br><br>For CENT/RHEL yum -y install php-xml';
	}

	if(extension_loaded('zip')) {
		$tests['php_zip']['state']="good";
		$tests['php_zip']['message']='';
	}else{
		$tests['php_zip']['state']="warning";
		$tests['php_zip']['message']='PHP is missing the <a href="http://php.net/manual/en/book.zip.php">zip extension</a>. Please install it. This is necessary for the bulk import functions to operate correctly.';
	}

	if(extension_loaded('ldap')) {
		$tests['php_ldap']['state']="good";
		$tests['php_ldap']['message']='';
	}else{
		$tests['php_ldap']['state']="warning";
		$tests['php_ldap']['message']='PHP is missing the <a href="http://php.net/manual/en/book.ldap.php">ldap extension</a>. Please install it. This is necessary for the advanced login functions like single sign on to work.';
	}

	if(extension_loaded('curl')) {
		$tests['php_curl']['state']="good";
		$tests['php_curl']['message']='';
	}else{
		$tests['php_curl']['state']="warning";
		$tests['php_curl']['message']='PHP is missing the <a href="http://php.net/manual/en/book.curl.php">curl extension</a>. Please install it. This is necessary for the advanced login functions like single sign on to work.';
	}

	if (function_exists('json_encode')) {
		$tests['php_json']['state']="good";
		$tests['php_json']['message']='PHP json module detected';
	}else{
		$tests['php_json']['state']="fail";
		$tests['php_json']['message']='PHP is missing the <a href="http://php.net/manual/book.json.php">JavaScript Object Notation (JSON) extension</a>.  Please install it.';
		$errors++;
	}
	// Sort the array at this point to get all the PHP modules together so it's more clear to the user
	uksort($tests, 'strcasecmp');

	$tests['php_pdo']['message']='';
	if (extension_loaded('PDO')) {
		$tests['php_pdo']['state']="good";
		if (count(PDO::getAvailableDrivers())>0) {
			$tests['pdodrivers']['message']='Available drivers: '.implode(", ",PDO::getAvailableDrivers());
			$tests['pdodrivers']['state']="good";
			// pdo is loaded check for the db.inc
			if(file_exists("db.inc.php")){
				$tests['db.inc']['state']="good";
				$tests['db.inc']['message']="db.inc.php has been detected and in the proper place";
				require_once("db.inc.php");
				// check for strict_trans_tables
				if(strpos(@end($dbh->query("select @@session.sql_mode;")->fetch()),'STRICT_TRANS_TABLES') === false){
					$tests['strictdb']['state']="good";
					$tests['strictdb']['message']='';
				}else{
					$tests['strictdb']['state']="fail";
					$tests['strictdb']['message']='openDCIM does not support STRICT_TRANS_TABLES. The following SQL statement might clear the error for this session.  More information can be found <a href="https://github.com/opendcim/openDCIM/issues/457">here</a>.<br><br><i>SET GLOBAL sql_mode = "";</i>';
					$errors++;
				}
				if(isset($pdo_options)){
					$tests['utf8-db']['state']="good";
					$tests['utf8-db']['message']='';
				}else{
					$tests['utf8-db']['state']="fail";
					$tests['utf8-db']['message']='Please copy over db.inc.php-dist to db.inc.php.  We found a problem with UTF8 support and MySQL that requires an additional parameter to work correctly.';
					$errors++;
				}
			}else{
				$tests['db.inc']['state']="fail";
				$tests['db.inc']['message']="Please copy db.inc.php-dist to db.inc.php and edit appropriately";
				$errors++;
			}

		}else{
			$tests['pdodrivers']['message']='Available drivers: none';
			$tests['pdodrivers']['state']="fail";
			$errors++;
		}
	}else{
		$tests['php_pdo']['state']="fail";
		$tests['php_pdo']['message']='openDCIM requires the <a href="http://php.net/manual/pdo.installation.php">PDO extension</a> and you do not appear to have it loaded';
		$tests['pdodrivers']['state']="fail";
		$tests['pdodrivers']['message']='No PDO drivers have been detected';
		$errors++;
	}

	// If AUTHENTICATION isn't defined then this asshole is upgrading to 4.0 and didn't add it into the db.inc.php
	if(defined('AUTHENTICATION')){
		$tests['authentication']['state']="good";
		$tests['authentication']['message']="Authentication set to ".AUTHENTICATION;
		if(AUTHENTICATION=="Apache"){
			if(isset($_SERVER['REMOTE_USER'])){
				$tests['Remote User']['state']="good";
				$tests['Remote User']['message']='';
			}else{
				$tests['Remote User']['message']='<a href="http://httpd.apache.org/docs/2.2/howto/auth.html">http://httpd.apache.org/docs/2.2/howto/auth.html</a>';
			}
		// If $config is an object, this is an already running installation rather than a new one
		}elseif (is_object( $config ) && AUTHENTICATION=="Saml" ) {
				if ( function_exists("openssl_csr_new") ) {
					$tests["openSSL"]["state"]="good";
					$tests["openSSL"]["message"]="";
				} else {
					$tests["openSSL"]["state"]="fail";
					$tests["openSSL"]["message"]="openSSL is required in order to process Saml based authentication.";
				}
		} elseif(is_object( $config ) && AUTHENTICATION=="LDAP") {
			if ($tests['php_ldap']['state']=="warning"){
				// If authentication is set to LDAP then the ldap module moves to required and no longer a warning if missing
				$tests['php_ldap']['state']="fail";
			}
			$tests["Remote User"]["state"]="good";
			$tests["Remote User"]["message"]="";
		}else {
			$tests['Remote User']['message']='Only Apache or LDAP authentication (using the Debug password) is supported for the initial install. Please use the provided .htaccess to authenticate as admin OR supply your own apache password file';
		}
		// Try to not duplicate everything
		if(!isset($tests['Remote User']['state'])){
			$tests['Remote User']['state']="fail";
			$errors++;
		}
	}else{
		$tests['authentication']['state']="fail";
		$tests['authentication']['message']=($tests['db.inc']['state']=="good")?"You didn't read the upgrade notes. Jerk. There is no AUTHENTICATION defined in db.inc.php":"How can you expect to work this if you can't even copy the db.inc.php into the right place?";
		$errors++;
	}

	// Do a quick check for file rights.
	$all_paths_writable=true;
	$wantedtitle="";
	if ( is_object( $config )) {
		$wantedpaths[] = $config->ParameterArray["drawingpath"];
		$wantedpaths[] = $config->ParameterArray["picturepath"];
	} else {
		$wantedpaths[] = "assets/pictures";
		$wantedpaths[] = "assets/drawings";
	}

	foreach($wantedpaths as $i => $file){
		$all_paths_writable=(is_writable('.'.DIRECTORY_SEPARATOR.$file) && $all_paths_writable)?true:false;
		if(!is_writable('.'.DIRECTORY_SEPARATOR.$file)){
			$wantedtitle="{$wantedtitle}{$file} is not writeable\n";
		}
	}
	$wantedtitle=addslashes(htmlentities($wantedtitle));

	if($all_paths_writable) {
		$tests['directory_rights']['state']="good";
		$tests['directory_rights']['message']='All required directories are writable';
	}else{
		$tests['directory_rights']['state']="fail";
		$tests['directory_rights']['message']='Some paths are not writable please check <a href="rightscheck.php" target="_new">rightscheck.php</a> and correct any issues present.';
		$errors++;
	}


	//Adding in some preliminary support for nginix
	if(preg_match("/apache/i", $_SERVER['SERVER_SOFTWARE'])){
		if(function_exists('apache_get_modules')){
			if(in_array('mod_rewrite', apache_get_modules())){
				$tests['mod_rewrite']['state']="good";
				$tests['mod_rewrite']['message']='mod_rewrite detected';
				$tests['api_test']['state']="fail";
				$tests['api_test']['message']="Apache does not appear to be rewriting URLs correctly. Check your AllowOverride directive and change to 'AllowOverride All' or check the RewriteBase parameter in api/v1/.htaccess and api/test/.htaccess";
			}else{
				$tests['mod_rewrite']['state']="fail";
				$tests['mod_rewrite']['message']='Apache is missing the <a href="http://httpd.apache.org/docs/current/mod/mod_rewrite.html">mod_rewrite</a> module and it is required for the API to function correctly.  Please install it.';
				$errors++;
			}
		}else{ // if function apache_get_modules isn't present then php might be running as mod_cgi
			$tests['mod_rewrite']['state']="good";
			$tests['mod_rewrite']['message']='PHP is running as modcgi and cannot be detected, assuming present';
			$tests['api_test']['state']="fail";
			$tests['api_test']['message']="Apache does not appear to be rewriting URLs correctly. Check your AllowOverride directive and change to 'AllowOverride All'";
		}
	}elseif(preg_match("/nginx/i", $_SERVER['SERVER_SOFTWARE'])){
		$tests['mod_rewrite']['state']="good";
		$tests['mod_rewrite']['message']="nginx doesn't support mod_rewrite. You must manually create rewrite rules, like these.<pre>
    location ~ ^/opendcim/api/v1 {
        rewrite ^(.*) /opendcim/api/v1/index.php last;
    }
    location ~ ^/opendcim/api/test {
        rewrite ^(.*) /opendcim/api/test/index.php last;
    }</pre>";
		$tests['api_test']['state']="fail";
		$tests['api_test']['message']="Apache does not appear to be rewriting URLs correctly. Check your AllowOverride directive and change to 'AllowOverride All'";

	}else{
		$tests['web_server']['state']="fail";
		$tests['web_server']['message']="Did not detect a supported web server. Server Detected: <b> {$_SERVER['SERVER_SOFTWARE']}</b>";
		$errors++;
	}

	if ($errors >0 || !isset($_GET['preflight-ok'])) {
        echo '<!doctype html><html><head><title>openDCIM :: pre-flight environment sanity check</title><script type="text/javascript" src="scripts/jquery.min.js"></script><style type="text/css">table{width:80%;border-collapse:collapse;border:3px solid black;}th{text-align:left;text-transform:uppercase;border-right: 1px solid black;}th,td{padding:5px;}tr:nth-child(even){background-color:#d1e1f1;}td:last-child{text-align:center;text-transform:uppercase;border:2px solid;background-color:green;}.fail td:last-child{font-weight: bold;background-color: red;}.warning td:last-child{font-weight: bold;background-color: yellow;}.hide{display: none;}</style></head><body><span id="sped"><a href="https://wiki.opendcim.org/wiki/index.php/System_Requirements" target="_blank">System Requirements</a> | <a href="https://wiki.opendcim.org/wiki/index.php/Installation" target="_blank">Installation Guide</a></span><table>';
		foreach($tests as $test => $text){
			$hide=($test=='api_test')?' class="hide"':'';
			$title=($test=='directory_rights')?' title="'.$wantedtitle.'"':'';
			print "<tr id=\"$test\"$hide><th>$test</th><td>{$text['message']}</td><td{$title}>{$text['state']}</td></tr>";
		}
		echo '<tr><th>javascript</th><td>Javascript is used heavily for data validation and a more polished user experience.</td><td><script>document.write("good");document.getElementById("api_test").className=document.getElementById("api_test").className.replace(/\bhide\b/,"");</script><noscript>fail</noscript></td></tr>
			</table>
		<p>If you see any errors on this page then you must correct them before the installer can continue.&nbsp;&nbsp;&nbsp;<span id="continue" class="hide">If the installer does not auto-continue,<a href="?preflight-ok"> click here</a><br><br>Please wait a few minutes before attempting to continue if a conversion is going on you might get unpredictable results by clicking</span></p>
		<span id="errors" class="hide">'.$errors.'</span>
<script type="text/javascript">
(function() {
	var rows=document.getElementsByTagName("tr");
	for(var row in rows){
	  var cells=rows[row].childNodes;
		if(typeof cells!="undefined"){
			if(cells[cells.length-1].textContent=="fail"){
				rows[row].className=rows[row].className + " fail";
			} else if(cells[cells.length-1].textContent=="warning"){
				rows[row].className=rows[row].className + " warning";
			}
		}
	}

	var xmlhttp=new XMLHttpRequest();
	xmlhttp.open("GET","api/test/test",false);
	xmlhttp.send();
	if(xmlhttp.status==200){
		var response=JSON.parse(xmlhttp.responseText);
		if(!response.error){
			var row=document.getElementById("api_test");
			row.className="";
			row.childNodes[1].textContent="";
			row.childNodes[2].textContent="GOOD";
			// only attempt to auto forward if we are in the installer and there are no errors
			if(parseInt(document.getElementById("errors").textContent)==0 && location.href.search("install")!=-1){
				document.getElementById("continue").className=document.getElementById("continue").className.replace(/\bhide\b/,"");
				location.href="?preflight-ok";
			}
		}
	}
})();
</script>
		</body></html>';
		exit;
	}
