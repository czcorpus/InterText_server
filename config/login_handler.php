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

if (!$DB = @mysqli_connect($U_SERVER,$U_USER,$U_PASSWORD,$U_DATABASE))
	die("Cannot connect to the users database server: ".mysqli_error($DB));
if (!mysqli_set_charset($DB,"utf8"))
	die("Cannot set encoding: ".mysqli_error($DB));

$USER['username'] = $_SESSION['username'];
$USER['passwd'] = $_SESSION['passwd'];

### Check username and password ###
$cond = "($U_USERNAME='".mysqli_real_escape_string($DB,$USER['username'])."' COLLATE utf8_bin AND $U_USERPASS='".mysqli_real_escape_string($DB,$USER['passwd'])."' COLLATE utf8_bin)";
$query = "SELECT $U_USERID as id, $U_USERTYPE as type, $U_FIRSTNAME as firstname, $U_SURNAME as surname FROM $U_TABLE WHERE $cond";
if (!$dbresult = mysqli_query($DB,$query))
	die("Cannot verify the user in the user database: ".mysqli_error($DB));
if (mysqli_num_rows($dbresult) != 1) {
	if ($_REQUEST['req']=='login') $WARNING="Invalid username or password.";
	$USER['username']=''; $USER['passwd']=''; $USER['type']='';
} else {
	$vysledek = mysqli_fetch_assoc($dbresult);
	$USER['type'] = $vysledek['type'];
	$USER['id'] = $vysledek['id'];
	$USER['firstname'] = $vysledek['firstname'];
	$USER['surname'] = $vysledek['surname'];
	$USER['name'] = "{$vysledek['surname']}, {$vysledek['firstname']}";
	# Load all users into a field
	$query = "SELECT $U_USERID as id, $U_USERTYPE as type, $U_FIRSTNAME as firstname, $U_SURNAME as surname, $U_USERNAME as username FROM $U_TABLE ORDER BY $U_SURNAME, $U_FIRSTNAME";
	if (!$dbresult = mysqli_query($DB,$query))
		die("Cannot read users database: ".mysqli_error($DB));
	$USERS['']=array('surname'=>'*** nobody ***', 'name'=>'*** nobody ***');
	while ($user =  mysqli_fetch_assoc($dbresult)) {
		$user['name'] = "{$user['surname']}, {$user['firstname']}";
		$USERS[$user['id']] = $user; 
		if ($user['type']==$USER_RESP) $RESPS[$user['id']] = $user;
		if ($user['type']==$USER_ADMIN) $ADMINS[$user['id']] = $user;
	}
}
mysqli_close($DB);
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
?>