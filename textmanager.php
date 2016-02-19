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

$myurl= $_SERVER['PHP_SELF']; # 'textmanager.php';
define('WARNING_FORMAT',"<p class=\"warning\">%s</p>");

require 'settings.php';
# Only ADMIN has access to textmanager.php
if ($USER['type']!=$USER_ADMIN) { Header('Location: aligner.php'); exit; }

require 'lib_intertext.php';
$system = new InterText;

if (IsSet($_REQUEST['req'])) $req = preg_replace('/[^a-z_]/','',$_REQUEST['req']);
else $req = 'list';
if (IsSet($_REQUEST['txt'])) { $txt = preg_replace('/[^a-zA-Z0-9_\-]/','', $_REQUEST['txt']);setcookie('InterText_tmpageoffset',0,$ctimeout); }
else $txt = '';

if (IsSet($_REQUEST['tmpagesize'])) { 
  $tmpagesize = preg_replace('/[^0-9]/','',$_REQUEST['tmpagesize']);
  setcookie('InterText_tmpagesize',$tmpagesize,$ctimeout); 
} else { if (IsSet($_COOKIE['InterText_tmpagesize'])) $tmpagesize = $_COOKIE['InterText_tmpagesize']; else $tmpagesize = 30; }

if (IsSet($_REQUEST['tmpageoffset'])) { 
  $tmpageoffset = preg_replace('/[^0-9]/','',$_REQUEST['tmpageoffset']);
  setcookie('InterText_tmpageoffset',$tmpageoffset,$ctimeout); 
} else { if (IsSet($_COOKIE['InterText_tmpageoffset'])) $tmpageoffset = $_COOKIE['InterText_tmpageoffset']; else $tmpageoffset = 0; }

if (IsSet($_REQUEST['tf_tname']) || IsSet($_REQUEST['tf_vname'])) {
  setcookie('InterText_tmpageoffset',0,$ctimeout);$tmpageoffset = 0;
}

if (IsSet($_REQUEST['tf_tname'])) $_SESSION['tf_tname'] = trim($_REQUEST['tf_tname']);
if (IsSet($_REQUEST['tf_vname'])) $_SESSION['tf_vname'] = $_REQUEST['tf_vname'];

# Processing requests that have to be processed before the HTML starts
switch ($req) {
case "update_ids":
	$id = preg_replace('/[^0-9]/','',$_REQUEST["id"]);
	$system->update_eids($txt,$id);
	break;
case "export":
	$id = preg_replace('/[^0-9]/','',$_REQUEST["id"]);
	$format = preg_replace('/[^a-zA-Z_0-9\-:]/','',$_REQUEST['format']);
	$doc = $system->txtver_by_id($id);
	$fullname = $doc['text_name'].".".$doc['version_name'];
	$out = $system->export_document($txt,$id,0,$format);
	if ($out) {
		Header("Content-type: text/xml; charset=UTF-8");
		Header("Content-Disposition: attachment; filename=\"$fullname.xml\"");
		print $out;
	} else {
		$_SESSION['WARNING'] = $_ERROR;
		Header("Location: $myurl");
	}
	return;
	break;
case 'delete_ver':
	$id = preg_replace('/[^0-9]/','',$_REQUEST["id"]);
	$system->delete_document($txt,$id);
	Header("Location: $myurl");
	return;
	break;
case 'upload_form':
	# Handle upload
	if (count($_FILES)>0) {
		$file = $_FILES['file'];
		$tname = trim(preg_replace('/[^_a-zA-Z0-9\-]/','',stripslashes($_REQUEST['tname'])));
		$vname = trim(preg_replace('/[^_a-zA-Z0-9\-]/','',stripslashes($_REQUEST['vname'])));
		$text_elements = trim(preg_replace('/[^_a-zA-Z0-9\-: ]/','',stripslashes($_REQUEST['text_elements'])));
		$text_elements = preg_replace('/ +/',' ',$text_elements);
		if (IsSet($_REQUEST['validate'])) $validate=true;
		else $validate = false;
		if (strlen($tname)==0) $WARNING = "ERROR: No text name given!";
		elseif (strlen($vname)==0) $WARNING = "ERROR: No version name given!";
		elseif (!strlen($file['tmp_name'])) $WARNING = "ERROR: No file to load!";
		if (!IsSet($WARNING)) {
			if ($system->import_document($tname,$vname,$file['tmp_name'],$text_elements,false,$file['name'],$validate)) $req='list';
			else $WARNING=$_ERROR;
		}
	}
}

function texts_count($versions) {
  $cnt = 0;
  $last = -1;
  foreach ($versions as $v) {
    if ($v['text_id']!=$last) {$cnt++;$last=$v['text_id'];}
  }
  return $cnt;
}

function texts_slice($versions, $from, $size) {
  $cnt = 0;
  $last = -1;
  $ret = array();
  foreach ($versions as $v) {
    if ($v['text_id']!=$last) {$cnt++;$last=$v['text_id'];}
    if ($cnt>$from && $cnt<=$from+$size) {array_push($ret, $v);}
  }
  return $ret;
}

#### HTML PAGE START ####
require 'header.php';

switch ($req) {
case 'upload_form':
?>
<div id="menubar">
<a href="<?php print $myurl; ?>">[back]</a>
&nbsp;<span id="logout"><a href="help.php#newtext" title="help" target="_blank">[help]</a><a href="?req=logout">[logout]</a></span>
</div>
<div id="contents">
<?php
# Print a warning
if ($WARNING!='') printf(WARNING_FORMAT,$WARNING);
?>
<div id="form-div">
<h1>Upload new text</h1>
<form enctype="multipart/form-data" action="<?php print $myurl; ?>" method="post" onSubmit="this.form.submit();document.getElementById('form-div').style.display='none'; document.getElementById('info').style.display='block';" class="upload">
	<fieldset>
	<input type="hidden" name="MAX_FILE_SIZE" value="102400000" />
	<input type="hidden" name="req" value="upload_form" />
	<table class="form">
<?php
	//$txtname = preg_replace('/[^a-zA-Z0-9_\-]/','',$_REQUEST['tname']);
	$txtid = preg_replace('/[^0-9]/','',$_REQUEST['txtid']);
	if (!IsSet($text_elements)) $text_elements = $DEFAULT_TEXT_ELEMENTS;
	if ($txtid!=0 || $tname!='') { 
		//$text = $system->text_by_id($txtid); 
		if ($txtid!='') $tname = $system->textname_by_id($txtid);
?>
	<tr>
		<td><strong>Text name:</strong></td>
		<td><?php print $tname; ?><input name="txtid" type="hidden" value="<?php print $txtid; ?>" /><input name="tname" type="hidden" value="<?php print $tname; ?>" /></td>
	</tr>
<?php
	} else { ?>
	<tr>
		<td><label for="tname"><strong>Text name:</strong></label></td>
		<td><input name="tname" type="text" size="50" value="<?php print $tname; ?>"/><input name="txtid" type="hidden" value="0" /></td>
	</tr>
<?php
	}
?>
	<tr>
		<td><label for="vname"><strong>Version name:</strong></label></td>
		<td><input name="vname" type="text" size="50" value="<?php print $vname; ?>" /></td>
	</tr>
	<tr>
		<td><label for="file"><strong>File:</strong></label></td>
		<td><input name="file" type="file" size="50" /></td>
	</tr>
	<tr>
		<td><label for="text_elements"><strong>Text elements:</strong></label></td>
		<td><input name="text_elements" type="text" size="50" value="<?php print $text_elements; ?>" /></td>
	</tr>
	<tr>
		<td></td>
		<td>(Space separated list of XML element names containing text to be aligned.)</td>
	</tr>
	<tr>
		<td></td>
		<td><input type="checkbox" name="validate"<?php if ($validate) print ' checked="checked"'; ?>/> force DTD validation</td>
	</tr>
	<tr><td colspan="2">
		<input type="submit" value="Upload text" />
	</td></tr>
	</table>
	</fieldset>
</form>
</div>
<div id="info">
<p><strong>Process:</strong> Uploading file...</p>
</div>
<?php
	break;
# By default, print a table with all texts and their versions
default:

  $filter = array();
  if ($_SESSION['tf_tname']!='')
    $filter['tname'] = $_SESSION['tf_tname'];
  if ($_SESSION['tf_vname']!='')
    $filter['vname'] = $_SESSION['tf_vname'];

	$texts = $system->list_texts($tmpageoffset, $tmpagesize, $filter);

	# Print menu bar
	print "<div id=\"menubar\">\n";
  print "&nbsp;[show: <form method=\"get\" class=\"inline\"><select name=\"tmpagesize\" onchange=\"this.form.submit();\">";
  foreach (array(0, 10, 20, 30, 50, 100) as $i) {
    if ($i) $lbl = $i." p/p"; else $lbl='all';
    if ($i==$tmpagesize) $sel = ' selected="selected"'; else $sel ='';
    print "<option value=\"$i\"$sel>$lbl</option>";
  }
  print "</select></form>]\n";
	print "<a href=\"users.php\" title=\"user management\">[users]</a>";
  print "<a href=\"aligner.php?txt=\" title=\"user management\">[all alignments]</a>";
	print "<a href=\"$myurl?req=upload_form\" title=\"add new text\">[new text]</a>";
	print "<span id=\"logout\"><a href=\"help.php#textmanager\" title=\"help\" target=\"_blank\">[help]</a><a href=\"aligner.php?req=logout\">[logout]</a></span>\n";
	print "</div>";

	print "<div id=\"contents\">\n";

	# Print a warning
	if ($WARNING!='') printf(WARNING_FORMAT,$WARNING);

	print "<h1>Available texts</h1>\n\n";

  $pager = '';

  $tmcount = $system->texts_count($filter);

  if ($tmpagesize>0 && $tmcount>$tmpagesize) {
    //$texts = texts_slice($texts, $tmpageoffset, $tmpagesize);
    $curtextscount = texts_count($texts);
    $pager .= "<p class=\"pager\">";
    if ($tmpageoffset>0) {
      $i = $tmpageoffset - $tmpagesize;
      if ($i<0) $i=0;
      $pager .= "<a href=\"?tmpageoffset=$i\">&lt;&lt; previous</a> | ";
    }
    $pager .= "showing texts ".($tmpageoffset+1)."-".($tmpageoffset+$curtextscount). " of ".$tmcount;
    if ($tmpageoffset+$curtextscount<$tmcount) {
      $i = $tmpageoffset+$curtextscount;
      $pager .= " | <a href=\"?tmpageoffset=$i\">next &gt;&gt;</a>";
    }
    $pager .= "</p>\n";
  }

  print $pager;

	$lasttxtid=FALSE;
	print "<table class=\"texts\"><tr><th>Text</th><th>versions</th><th>&nbsp;</th></tr>\n";
?>
<tr id="filter">
<form action="<?php print $myurl; ?>" method="post" id="filter_form">
<td><input type="text" size="20" name="tf_tname" value="<?php print htmlentities($_SESSION['tf_tname'], ENT_COMPAT, 'UTF-8'); ?>" autofocus="autofocus"/></td>
<td>
<select name="tf_vname" onchange="this.form.submit();">
  <option value="">&lt;any&gt;</option>
<?php
    $versions = $system->list_versions($txt);
    foreach ($versions as $version) {
      //if (!IsSet($version['id']) || $version['id']!=$txtver['id']) {
        print "\t\t\t<option value=\"{$version['version_name']}\"";
        if ($version['version_name']==$_SESSION['tf_vname']) print ' selected="selected"';
        print ">{$version['version_name']}</option>\n";
      //}
    }
?>
</select></td>
<td>
<input type="submit" value="Filter!"/>
</td>
</form>
</tr>
<?php
	foreach($texts as $version) {
		if (!($version['text_id']==$lasttxtid)) {
			if ($lasttxtid) print "\t<span class=\"add_version\"><a href=\"$myurl?req=upload_form&amp;txtid=$lasttxtid\" title=\"add new text version\" class=\"img\"><img src=\"icons/document-new.png\" alt=\"[ADD VERSION]\"/></a></span>\n</td><td><a href=\"aligner.php?aid=0&amp;txt=$tid&amp;txtver=0\" title=\"show all alignments\">all</a></td></tr>\n";
			$lasttxtid = $version['text_id'];
			print "<tr><td class=\"text_name\"><a href=\"javascript:toggle('{$version['text_id']}','on')\" title=\"toggle version view\" id=\"t{$version['text_id']}\">{$version['text_name']}</a></td>\n";
			print "<td class=\"text_versions\" id=\"{$version['text_id']}\">\n";
		}
		$id = $version['version_id'];
		$tid = $version['text_id'];
		$fullname = trim($version['text_name'].'.'.$version['version_name']);
		print "\t<span class=\"version\">\n";
		print "\t\t<a href=\"aligner.php?aid=0&amp;txt=$tid&amp;txtver=$id\" title=\"show alignments\">{$version['version_name']}</a><span>:</span>\n";
		print "\t\t<span class=\"export\" id=\"\"><a onclick=\"toggle_block(this,'exp_{$tid}_$id','on');\" title=\"export text\" class=\"img\"><img src=\"icons/document-save.png\" alt=\"[EXPORT]\" /></a></span>\n";
		print "\t\t<span class=\"update\"><a href=\"$myurl?req=update_ids&amp;txt=$tid&amp;id=$id\" title=\"update IDs\" class=\"img\"><img src=\"icons/format-list-ordered.png\" alt=\"[UPDATE]\" /></a></span>\n";
		if (!$version['uniq_ids']) {
			print "\t\t<span class=\"nouniqids\" title=\"missing uniq ids on alignable elements\"><img src=\"icons/flag-red.png\" alt=\"no uniq ids\" /></span>\n";
		} 
		if ($version['text_changed']) {
			print "\t\t<span class=\"chtext\" title=\"text changed\"><img src=\"icons/flag-yellow.png\" alt=\"text changed\" /></span>\n";
		}
		if ($version['alignment_count']==0)
			print "\t\t<span class=\"delete\"><a href=\"$myurl?req=delete_ver&amp;txt=$tid&amp;id=$id\" title=\"delete\" onClick=\"return deleteConfirm(this,'$fullname')\" class=\"img\"><img src=\"icons/edit-delete-shred.png\" alt=\"[DELETE]\" /></a></span>\n";
	$format = "<select name=\"format\" onChange=\"this.form.submit();this.selectedIndex=0;\"><option value=\"\">choose format</option><option value=\"orig\">original (plain IDs)</option><option value=\"orig:ic\">original (IC long IDs)</option></select>";
		print "\t\t<div id=\"exp_{$tid}_$id\" class=\"exportbar\"><form class=\"inline\" method=\"post\"><input type=\"hidden\" name=\"txt\" value=\"$tid\"/><input type=\"hidden\" name=\"req\" value=\"export\"/><input type=\"hidden\" name=\"id\" value=\"$id\"/>$format</form></div>\n";
		print "\t</span>\n";
	}
	if (count($texts)) print "\t<span class=\"add_version\"><a href=\"$myurl?req=upload_form&amp;txtid=$lasttxtid\" title=\"add new text version\" class=\"img\"><img src=\"icons/document-new.png\" alt=\"[ADD VERSION]\" /></a></span>\n</td><td><a href=\"aligner.php?aid=0&amp;txt=$tid&amp;txtver=0\" title=\"show all alignments\">all</a></td></tr>\n";
	print "</table>\n";
  print $pager;

}
?>
</div>
</body>
</html>
