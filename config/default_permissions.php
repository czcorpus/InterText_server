<?php
/*  Copyright (c) 2010-2017 Pavel Vondřička (Pavel.Vondricka@korpus.cz)
 *  Copyright (c) 2010-2017 Charles University in Prague, Faculty of Arts,
 *                          Institute of the Czech National Corpus
 *
 *  This file is part of InterText Server.
 *
 *  InterText Server is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  InterText Server is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with InterText Server.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

#### Default settings for ADMIN ####
if ($USER['type']==$USER_ADMIN) {
	$SET['newalign'] = true;
	$SET['delalign'] = true;
	$SET['realign'] = true;
	$SET['chalmethod'] = true;
	$SET['export'] = true;
	$SET['client_upload'] = true;
	$TXTMGR_URL = 'textmanager.php';
	$TXTMGR_TEXT = 'textmanager';
}
#####################################

# Settings and permissions for particular alignment / text version / user
function set_alignment_settings($alinfo) {
	global $SET,$TXTMGR_URL,$TXTMGR_TEXT,$USER,$C_VERSION,$USER_ADMIN;
	###### INPUT array keys for $alinfo array: 
	# text_name: name of the text
	# ver1_id: ID of text version 1
	# ver1_name: name of text version 1
	# ver2_id: ID of text version 2
	# ver2_name: name of text version 2
	###### OUTPUT array for each version:
	# $SET[<version_id>]['chtext']: permisssion to change text (=edit element contents)
	# $SET[<version_id>]['chstruct']: permisssion to change text structure (=split/merge elements)
	# $SET['reaonly']: do not allow any changes to the alignment, read-only access

	# Here, some defaults can be set per user, too
	global $DEFAULT_METHOD, $DEFAULT_PROFILE, $DEFAULT_HIGHLIGHT, $DEFAULT_MODE, $DEFAULT_LIMIT;

	if ($USER['type']<=$UPLOAD_MAXUSERLEVEL_CLIENT)
		$SET['client_upload'] = true;
	else
		$SET['client_upload'] = false;

	if ($USER['type']==$USER_ADMIN) {
		# admin can edit everything, always!
		$SET['readonly'] = FALSE;
		$SET[$alinfo['ver1_id']]['chtext'] = true;
		$SET[$alinfo['ver1_id']]['chstruct'] = true;
		$SET[$alinfo['ver2_id']]['chtext'] = true;
		$SET[$alinfo['ver2_id']]['chstruct'] = true;
	} else {
		# Deny everything by default for others
		$SET[$alinfo['ver1_id']]['chtext'] = false;
		$SET[$alinfo['ver1_id']]['chstruct'] = false;
		$SET[$alinfo['ver2_id']]['chtext'] = false;
		$SET[$alinfo['ver2_id']]['chstruct'] = false;
		$SET['readonly'] = TRUE;
	}

	if ($alinfo['editor']==$USER['id'] || $alinfo['resp']==$USER['id']) {
		if ($alinfo['chtext']) {
			$SET[$alinfo['ver1_id']]['chtext'] = true;
			$SET[$alinfo['ver2_id']]['chtext'] = true;
			if ($alinfo['c_chstruct'] || !preg_match("/^$C_VERSION$/",$alinfo['ver1_name'])) 
				$SET[$alinfo['ver1_id']]['chstruct'] = true;
			if ($alinfo['c_chstruct'] || !preg_match("/^$C_VERSION$/",$alinfo['ver2_name'])) 
				$SET[$alinfo['ver2_id']]['chstruct'] = true;
		}
		if ($alinfo['status']==ALSTAT_OPEN) $SET['readonly'] = FALSE;
	}

	### Complete "Access denied" termination
	#require 'header.php';
	#print "<p class=\"warning\">Access denied!</p>\n<p><a href=\"$TXTMGR_URL\">&lt;&lt; $TXTMGR_TEXT</a></p></body>\n</html>\n";
	#exit;

	return $SET;
}
?>