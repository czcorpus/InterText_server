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

# Session initialization
ini_set('session.gc_maxlifetime', '31104000');
session_name('InterText_SID');
session_start();
$cookie=session_get_cookie_params();
$ctimeout = time()+60*60*24*30*12*5;
setcookie(session_name(),session_id(),$ctimeout);

### General defaults
# set default timezone
date_default_timezone_set('Europe/Prague');
# MySQL access for InterText database:
$DB_SERVER = "localhost";
$DB_USER = "intertext";
$DB_PASSWORD = "intertext";
$DB_DATABASE = "intertext";
# MySQL access for user database
$U_SERVER = "localhost";
$U_USER = "intertext";
$U_PASSWORD = "intertext";
$U_DATABASE = "intertext";
$U_TABLE = 'users';
$U_USERNAME = 'username';
$U_USERPASS = 'password';
$U_USERTYPE = 'status';
$U_USERID = 'id';
$U_FIRSTNAME = 'name';
$U_SURNAME = 'surname';
# USER levels <=> values in database, column $U_USERTYPE
$USER_ADMIN = 0; # main administrator
$USER_RESP = 1; # supervisor / section coordinator / responsible
$USER_EDITOR = 2; # editor
# ordering of alignmnets (do not change unless you know what and why!)
$ALORDER = array(
	'ver1asc' => 'v1.version_name ASC',
	'ver1desc' => 'v1.version_name DESC',
	'ver2asc' => 'v2.version_name ASC',
	'ver2desc' => 'v2.version_name DESC',
	'edasc' => 'a.editor ASC',
	'eddesc' => 'a.editor DESC',
	'respasc' => 'a.resp ASC',
	'respdesc' => 'a.resp DESC',
	'statasc' => 'a.status ASC, a.id DESC',
	'statdesc' => 'a.status DESC, a.id DESC'
);
# default order in the list of alignments
$DEFAULT_TORDER = 'asc'; # primary sorting by text name ('asc', 'desc' or '' for no ordering by text name)
$DEFAULT_ALORDER = 'ver1asc';
# names of modes
$MODES=array(
	'1'=>"manual status update", 
	'6'=>'manual &amp; roll (-5)',
	'7'=>'manual &amp; roll (-2)',
	'8'=>'manual &amp; roll (act.)',
	'2'=>'auto update status',
	'3'=>'auto &amp; roll (-5)',
	'4'=>'auto &amp; roll (-2)',
	'5'=>'auto &amp; roll (act.)'
);
# default mode
$DEFAULT_MODE=4;
# available limits of positions per page
$LIMITS = array('10','20','50','100');
# default limit (positions per page)
$DEFAULT_LIMIT=20;
# Highlight non-1:1 alignments by default?
$DEFAULT_HIGHLIGHT = 1; # 1=true, 0=false
# Hide controls by default?
$DEFAULT_HIDE = 0; # 1=true, 0=false
# Default permission to edit texts
$DEFAULT_EDIT_PERMISSION = 1; # 1=true; 0=false
# Default permission to change structure of central/pivot text versions
$DEFAULT_C_CHSTRUCT_PERMISSION = 0; # 1=true; 0=false
# Central/pivot text versions (protected of structure changes by the c_chstruct attribute)
$C_VERSION = 'cs-.*'; # Czech versions for InterCorp
# default alignment method and profile (for automatic alignment)
$DEFAULT_METHOD='hunalign';
$DEFAULT_PROFILE='_none_';
#$DEFAULT_METHOD='tca2';
#$DEFAULT_PROFILE='no-cs';
# Default XML elements containing alignable text
$DEFAULT_TEXT_ELEMENTS = 'head s verse';
# Back links
$TXTMGR_URL = '';
$TXTMGR_TEXT = '';
# Logout URL
$LOGOUT_URL = 'aligner.php';
# Global permissions
# $SET['newalign']: permission to CREATE new alignments
# $SET['delalign']: permission to DELETE alignments
# $SET['realign']: permission to run automatic alignment again from any position
# $SET['chalmethod']: permission to change method for authomatic re-alignment
# $SET['export']: permission to export texts and/or alignment
$SET['newalign'] = false;
$SET['delalign'] = false;
$SET['realign'] = true;
$SET['chalmethod'] = true;
$SET['export'] = true;
# Maximal user level for upload of new alignments from the client InterText editor application
# (the user will also be allowed to download any text version from the database!)
$UPLOAD_MAXUSERLEVEL_CLIENT = $USER_ADMIN;
# Maximal user level for download of isolated text versions 
$DOWNLOAD_TXTVER_MAXUSERLEVEL = $USER_ADMIN;
# Filter text versions freely accessible for download (limited to user with the UPLOAD permission above)
$DOWNLOAD_TXTVER_FILTER = '.*'; # all versions / "cs-.*": only Czech versions for InterCorp
# Tags allowed in text:
$TAGS_IN_TEXT="i|b|u|emph";
# Use logging of text editation changes?
$LOG_EDIT_CHANGES = true;
# Log all changes in alignments!
$LOG_ALIGN_CHANGES = false;
# Enforce single level (re)numbering of alignable elements
# (and keep original IDs of all other elements!)
$FORCE_SIMPLE_NUMBERING = false;
# ignore unconfirmed alignments when checking for merge conflicts
$MERGE_UNCONFIRMED_FREELY = false;
# deny users to remove text from elements
$DENY_EMPTY_UPDATES = true;
# disable fulltext search (necessary for MySQL <= 5.5)
$DISABLE_FULLTEXT = false;

########### LOGIN HANDLER ##########
if ($_REQUEST['req']=='logout') {
	$_SESSION['username']=''; $_SESSION['passwd']=''; $USER['lang']='';
	if (!$CLI_MODE) { Header ("Location: $LOGOUT_URL"); exit; } else { return; }
} 

if (mktime()>$_SESSION['last_use']+60*30 AND !$_SESSION['no_logintimeout']) {
  $_SESSION['username']=''; $_SESSION['passwd']=''; $USER['lang']='';
}

if ($_REQUEST['req']=='login') {
  $_SESSION['username']=$_REQUEST['login']; $_SESSION['passwd']=md5($_REQUEST['passwd']);
  if (IsSet($_REQUEST['no_logintimeout']))
    $_SESSION['no_logintimeout']=1;
  else
    $_SESSION['no_logintimeout']=0;

}

if (!$CONNECTION = @MySQL_Connect($U_SERVER,$U_USER,$U_PASSWORD)) 
	die("Cannot connect to the users database server: ".mysql_error());
if (!$result = mysql_select_db($U_DATABASE))
	die("Cannot open user database: ".mysql_error());
// if (!$result = mysql_query("SET CHARACTER SET utf8"))
// 	die("Cannot set encoding: ".mysql_error());
if (!mysql_set_charset("utf8"))
	die("Cannot set encoding: ".mysql_error());

$USER['username'] = $_SESSION['username'];
$USER['passwd'] = $_SESSION['passwd'];

### Check username and password ###
$cond = "($U_USERNAME='".mysql_real_escape_string($USER['username'])."' COLLATE utf8_bin AND $U_USERPASS='".mysql_real_escape_string($USER['passwd'])."' COLLATE utf8_bin)";
$query = "SELECT $U_USERID as id, $U_USERTYPE as type, $U_FIRSTNAME as firstname, $U_SURNAME as surname FROM $U_TABLE WHERE $cond";
if (!$dbresult = mysql_query($query))
	die("Cannot verify the user in the user database: ".mysql_error());
if (mysql_num_rows($dbresult) != 1) {
	if ($_REQUEST['req']=='login') $WARNING="Invalid username or password.";
	$USER['username']=''; $USER['passwd']=''; $USER['type']='';
} else {
	$vysledek = mysql_fetch_assoc($dbresult);
	$USER['type'] = $vysledek['type'];
	$USER['id'] = $vysledek['id'];
	$USER['firstname'] = $vysledek['firstname'];
	$USER['surname'] = $vysledek['surname'];
	$USER['name'] = "{$vysledek['surname']}, {$vysledek['firstname']}";
	# Load all users into a field
	$query = "SELECT $U_USERID as id, $U_USERTYPE as type, $U_FIRSTNAME as firstname, $U_SURNAME as surname, $U_USERNAME as username FROM $U_TABLE ORDER BY $U_SURNAME, $U_FIRSTNAME";
	if (!$dbresult = mysql_query($query))
		die("Cannot read users database: ".mysql_error());
	$USERS['']=array('surname'=>'*** nobody ***', 'name'=>'*** nobody ***');
	while ($user =  mysql_fetch_assoc($dbresult)) {
		$user['name'] = "{$user['surname']}, {$user['firstname']}";
		$USERS[$user['id']] = $user; 
		if ($user['type']==$USER_RESP) $RESPS[$user['id']] = $user;
		if ($user['type']==$USER_ADMIN) $ADMINS[$user['id']] = $user;
	}
}
mysql_close($CONNECTION);
$_SESSION['last_use']=mktime();

### REQUIRE A VALID USER ###
if ($USER['username']=='' && !$CLI_MODE) {
	require 'header.php';
	# Print menubar
	print "<div id=\"menubar\">
	&nbsp;<span id=\"logout\"><i>InterText</i></span>
</div>
<div id=\"contents\">
";
	if ($WARNING!='') printf(WARNING_FORMAT,$WARNING);
	print "<div class=\"login\">
	<form action=\"\" method=\"POST\">
	<fieldset>
	<legend>User Login</legend>
	<input type=\"hidden\" name=\"req\" value=\"login\" />
	<table class=\"login\">
	<tr><td>
	<label for=\"login\">Username:</label></td><td><input type=\"text\" name=\"login\" value=\"\"/></td></tr>
	<tr><td><label for=\"passwd\">Password:</label></td><td><input type=\"password\" name=\"passwd\" value=\"\"/></td></tr>
  <tr><td colspan=\"2\"><input type=\"checkbox\" name=\"no_logintimeout\" value=\"1\"/> Remember me on this computer</td></tr>
	<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"--- Log in ---\"/></td>
	</tr></table>
	</fieldset>
</form>

<p>Do not forget to log out if working on a public computer!</p>
</div>

</div>
</body>
</html>
";
exit;
}
########## END OF LOGIN HANDLER ##########

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


# Format header for exported files
function format_exported_header($tname,$vname,$format,$idformat) {
	$ret = "<?xml version='1.0' encoding='utf-8'?>\n";
	return $ret;
}

# Format IDs for exported files
function format_exported_ids($ename,$id,$tname,$vname,$format='',$idformat='',$filename='') {
	# add text name as ID for our <doc...> root element
	if ($ename=='doc') {
		return $tname;
	} elseif ($id!='') {
		# for other elements with non-empty ID:
		switch ($idformat) {
		# generate InterCorp long ID
		case 'ic':
			$id = strtr($id,'.:-_,',':::::');
			#$tname = strtolower($tname);
			#$vname = strtolower($vname);
			$vname = preg_replace('/-00$/','',$vname);
			$divid = 0;
			return "$vname:$tname:$divid:$id";
		# generate ECPC filename based long ID
		case 'fn':
			$id = strtr($id,'.:-_,',':::::');
			# trim the file extension
			$filename=preg_replace('/\..*$/','',$filename);
			return "$filename-se$id";
		# or just ensure the use of colon as separator otherwise
		case '_alignable_':
		default:
			$id = strtr($id,'.:-_,',':::::');
			return $id;
		}
	} else return '';
}


?>
