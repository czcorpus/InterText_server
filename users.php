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

$myurl= $_SERVER['PHP_SELF']; # 'users.php';
define('WARNING_FORMAT',"<p class=\"warning\">%s</p>");

require 'init.php';
//# Only ADMIN has access to users.php
//if ($USER['type']!=$USER_ADMIN) { Header('Location: aligner.php'); exit; }

require 'lib_intertext.php';
$system = new InterText;

if (IsSet($_REQUEST['req'])) $req = preg_replace('/[^a-z_]/','',$_REQUEST['req']);

if ($USER['type']!=$USER_ADMIN) {
	if ($req=='') $req='edit';
	$id=$USER['id']; 
	if ($req!='save' && $req!='edit') {
		Header('Location: aligner.php'); exit;
	}
} else $id = preg_replace('/[^0-9]/','',$_REQUEST['id']);

# Processing requests that have to be processed before the HTML starts
switch ($req) {
case 'delete':
	$system->user_delete($id);
	Header('Location: '.$myurl);
	break;
case 'save':
	$u = array();
	$u['id'] = $id;
	if ($USER['type']==$USER_ADMIN) {
		$u['type'] = preg_replace('/[^0-9]/','',$_REQUEST['type']);
		$u['firstname'] = $_REQUEST['firstname'];
		$u['surname'] = $_REQUEST['surname'];
		$u['username'] = $_REQUEST['username'];
	} else {
		$u['type'] = $USER['type'];
		$u['firstname'] = $USER['firstname'];
		$u['surname'] = $USER['surname'];
		$u['username'] = $USER['username'];
	}
	$u['password'] = $_REQUEST['passwd'];
	if ($u['username']=='')
		$WARNING = 'Username cannot be empty!';
	if ($u['surname']=='')
		$u['surname']=$u['username'];
	if ($u['firstname']=='')
		$u['firstname']='???';
	if ($u['id']==0 && $u['password']=='')
		$WARNING = 'You have to enter a password for a new user!';
	if ($u['password']!='' && $_REQUEST['passwd1']!=$u['password'])
		$WARNING = 'Passwords do not match!';
	foreach ($USERS as $uid => $cu) {
		if ($uid==$u['id']) continue;
		if ($cu['username']==$u['username'])
			$WARNING = 'This username is already in use. Choose another one.';
	}
	if ($WARNING!='')
		break;
	if ($u['password']!='')
		$u['password'] = md5($u['password']);
	$system->user_save($u);
	Header('Location: '.$myurl);
	exit;
}

#### HTML PAGE START ####
require 'header.php';

switch ($req) {
case 'edit':
	if (IsSet($_REQUEST['id']))
		$u = $USERS[$id];
	else
		$u = array('id'=>0, 'type'=>$USER_EDITOR);
case 'save':
	# Print menu bar
	print "<div id=\"menubar\">\n";
	if ($TXTMGR_URL!='') print "<a href=\"$TXTMGR_URL\">[text manager]</a>\n";
	else print "&nbsp;";
	print "<span id=\"logout\"><a href=\"help.php\" title=\"help\" target=\"_blank\">[help]</a><a href=\"aligner.php?req=logout\">[logout]</a></span>\n";
	print "</div>";

	print "<div id=\"contents\">\n";

	# Print a warning
	if ($WARNING!='') printf(WARNING_FORMAT,$WARNING);

	$sel = ' selected="selected"';
?>
<div id="form-div">
<h1>User details</h1>
<form method="post" onSubmit="checkuserform(this)" class="">
	<fieldset>
	<input type="hidden" name="req" value="save" />
	<input type="hidden" name="id" value="<?php print $u['id']; ?>" />
	<table class="form">
<?php if ($USER['type']==$USER_ADMIN) { ?>
	<tr>
		<td><strong>First name:</strong></td>
		<td><input name="firstname" type="text" value="<?php print $u['firstname']; ?>" /></td>
	</tr>
	<tr>
		<td><strong>Surname:</strong></td>
		<td><input name="surname" type="text" value="<?php print $u['surname']; ?>" /></td>
	</tr>
	<tr>
		<td><strong>Role:</strong></td>
		<td>
		<select name="type">
			<option value="<?php print $USER_ADMIN; ?>"<?php if ($u['type']==$USER_ADMIN) print $sel; ?>>administrator</option>
			<option value="<?php print $USER_RESP; ?>"<?php if ($u['type']==$USER_RESP) print $sel; ?>>supervisor</option>
			<option value="<?php print $USER_EDITOR; ?>"<?php if ($u['type']==$USER_EDITOR) print $sel; ?>>editor</option>
		</select>
		</td>
	</tr>
	<tr>
		<td><strong>Username:</strong></td>
		<td><input name="username" type="text" value="<?php print $u['username']; ?>" /></td>
	</tr>
<?php } ?>
	<tr>
		<td><strong>Password:</strong></td>
		<td><input name="passwd" type="password" value="" /></td>
	</tr>
	<tr>
		<td></td>
		<td><input name="passwd1" type="password" value="" /></td>
	</tr>
	<tr><td colspan="2">
		(enter password twice for security; if no password is entered, the previous one will be kept)
	</td></tr>
	<tr><td colspan="2">
		<input type="submit" value="Save" />
	</td></tr>
	</table>
	</fieldset>
</form>
</div>
<?php
	if ($USER['type']==$USER_ADMIN)
		print '<p><a href="'.$myurl.'">&lt;&lt; Back to the list</a></p>';
	else
		print '<p><a href="aligner.php">&lt;&lt; Back</a></p>';
	break;
default:
	$users = $USERS;

	# Print menu bar
	print "<div id=\"menubar\">\n";
	print "<a href=\"textmanager.php\" title=\"text manager\">[text manager]</a>";
	print "<a href=\"$myurl?req=edit\" title=\"add new user\">[new user]</a>";
	print "<span id=\"logout\"><a href=\"help.php\" title=\"help\" target=\"_blank\">[help]</a><a href=\"aligner.php?req=logout\">[logout]</a></span>\n";
	print "</div>";

	print "<div id=\"contents\">\n";

	# Print a warning
	if ($WARNING!='') printf(WARNING_FORMAT,$WARNING);

	print "<h1>Users</h1>\n\n";

#	$lasttxtid=FALSE;
	print "<table class=\"texts\"><tr><th>Name</th><th>username</th><th>role</th><th>&nbsp;</th></tr>\n";
	$i = 1;
	foreach($users as $u) {
		if ($u['id']==0) continue;
		if ($i % 2) $c="even"; else $c="odd";
		if ($u['type']==$USER_ADMIN) $role='administrator';
		elseif ($u['type']==$USER_RESP) $role='supervisor';
		elseif ($u['type']==$USER_EDITOR) $role='editor';
		print "<tr class=\"$c\"><td><a href=\"$myurl?req=edit&amp;id={$u['id']}\">{$u['name']}</a></td><td>{$u['username']}</td><td>$role</td>
	<td><span class=\"delete\"><a href=\"$myurl?req=delete&amp;id={$u['id']}\" title=\"delete\" onClick=\"return udeleteConfirm(this,'{$u['name']}')\" class=\"img\"><img src=\"icons/edit-delete-shred.png\" alt=\"[DELETE]\" /></a></span></td></tr>";
		$i++;
	}
	print "</table>\n";

}
?>
</div>
</body>
</html>
