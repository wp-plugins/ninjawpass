<?php

/*
 +---------------------------------------------------------------------+
 | NinjaWPass                                                          |
 |                                                                     |
 | (c)2012-2013 NinTechNet                                             |
 | <wordpress@nintechnet.com>                                          |
 +---------------------------------------------------------------------+
 | http://nintechnet.com/                                              |
 +---------------------------------------------------------------------+
 | REVISION: 2013-07-03 23:08:25                                       |
 +---------------------------------------------------------------------+
 | This program is free software: you can redistribute it and/or       |
 | modify it under the terms of the GNU General Public License as      |
 | published by the Free Software Foundation, either version 3 of      |
 | the License, or (at your option) any later version.                 |
 |                                                                     |
 | This program is distributed in the hope that it will be useful,     |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of      |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the       |
 | GNU General Public License for more details.                        |
 +---------------------------------------------------------------------+
*/

if (! defined('WP_UNINSTALL_PLUGIN') ) { die('Forbidden'); }

ninjawp_uninstall();

/* ================================================================== */
function ninjawp_uninstall() {

	// Delete DB table :
	delete_option( 'ninjawp_options' );

}
/* ================================================================== */

// EOF
?>