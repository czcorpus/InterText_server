<?php
/*  Copyright (c) 2010-2016 Pavel Vondřička (Pavel.Vondricka@korpus.cz)
 *  Copyright (c) 2010-2016 Charles University in Prague, Faculty of Arts,
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

set_time_limit(9999);
require 'settings.php';
require 'lib_intertext.php';
$system = new InterText;

print "Process: Creating alignment...\nProgress: 0\n"; flush(); ob_flush();
if ($_SESSION['realign']) {
	$res = $aid = $_COOKIE['InterText_aid'];
} else {
	#$v1 = $system->txtver_by_id($_COOKIE['InterText_txt'],$_SESSION['al_ver1']);
	#$v2 = $system->txtver_by_id($_COOKIE['InterText_txt'],$_SESSION['al_ver2']);
	#$aid = $system->insert_alignment($v1['text_id'],$v1['id'],$v2['id'],$_SESSION['al_method'],$_SESSION['al_profile'],$USER['id']);
	$aid = $system->insert_alignment($_COOKIE['InterText_txt'],$_SESSION['al_ver1'],$_SESSION['al_ver2'],$_SESSION['al_method'],$_SESSION['al_profile'],$USER['id'],0,$DEFAULT_EDIT_PERMISSION);
	$defstat = $_SESSION['al_default_status'];
	$res = $aid;
}

if (!$res) { print "Process: Creating alignment...<br />ERROR: These versions have already an open alignment!\n\n"; flush(); ob_flush(); }
else {
	# Import alignment from file, if any
	if ($res AND $_SESSION['al_file']!='')
		$res = $system->import_alignment($_SESSION['al_file'],$aid,$defstat,TRUE,FALSE);

	if ($res) {
		# Run automatic aligner as requested
		switch ($_SESSION['al_method']) {
			case "tca2":
				$res = $system->autoalign_tca2($aid,$_SESSION['al_profile']);
				break;
			case "hunalign":
				$res = $system->autoalign_hunalign($aid,$_SESSION['al_profile']);
				break;
			default:
				$res = $system->plain_alignment($aid);
		}
	}

	# Create plain alignment for the rest
	#if ($res) $system->plain_alignment($aid);

	# In case of failure...
	if (!$res) {
		if (!$_SESSION['realign']) $system->delete_alignment($aid);
	} else print "Process: Finished.\n";
}

if ($_SESSION['al_file']!='') unlink($_SESSION['al_file']);
unset($_SESSION['realign']);
unset($_SESSION['al_file']);
unset($_SESSION['al_ver1']);
unset($_SESSION['al_ver2']);
unset($_SESSION['al_method']);
unset($_SESSION['al_profile']);
unset($_SESSION['al_default_status']);

?>
