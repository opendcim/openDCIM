<?php
/*
	openDCIM

	This is the main class library for the openDCIM application, which
	is a PHP/Web based data center infrastructure management system.

	This application was originally written by Scott A. Milliken while
	employed at Vanderbilt University in Nashville, TN, as the
	Data Center Manager, and released under the GNU GPL.

	Copyright (C) 2011 Scott A. Milliken

	This program is free software:  you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published
	by the Free Software Foundation, version 3.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	For further details on the license, see http://www.gnu.org/licenses
*/

/*	Master include file - while all could fit easily into this one include,
	for the sake of modularity and ease of checking out portions for multiple
	developers, functions have been split out into more granular groupings.
*/

date_default_timezone_set($config->ParameterArray['timezone']);

// Pull in the Composer autoloader
require_once( __DIR__ . "/vendor/autoload.php" );

if (!function_exists('apache_request_headers')) {
	function apache_request_headers() {
		foreach($_SERVER as $key => $value) {
			if ("HTTP_" == substr($key, 0, 5)) {
				$key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
				$out[$key] = $value;
			}else{
				$out[$key] = $value;
			}
		}
		return $out;
	}
}

require_once( "misc.inc.php" );

// SNMP Library, don't attempt to load without php-snmp extensions
if(extension_loaded('snmp')){
	require_once('OSS_SNMP/SNMP.php');
}

?>
