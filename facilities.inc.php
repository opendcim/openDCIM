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

require_once( "assets.inc.php" );
require_once( "customers.inc.php" );
require_once( "infrastructure.inc.php" );
require_once( "power.inc.php" );
require_once( "config.inc.php" );
require_once( "misc.inc.php" );
require_once( "vanderbilt.inc.php" );

/* Remove comment if you are going to use data center and resource logging.
require_once( "logging.inc.php" );
*/

?>
