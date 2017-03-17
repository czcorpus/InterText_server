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

#setlocale(LC_COLLATE,'cs_CZ.utf8');

$myurl= $_SERVER['PHP_SELF']; # 'aligner.php';
define('WARNING_FORMAT',"<p class=\"warning\">%s</p>");

require 'settings.php';
require 'lib_intertext.php';
$system = new InterText;

$batch = array();

if (IsSet($_REQUEST['batch'])) $batch = explode(',', preg_replace('/[^0-9,]/','',$_REQUEST['batch']));

if (IsSet($_REQUEST['aid'])) { 
	$aid = preg_replace('/[^0-9]/','',$_REQUEST['aid']);
	if ($aid!=$_COOKIE['InterText_aid']) setcookie('InterText_offset',1,$ctimeout);
	setcookie('InterText_aid',$aid,$ctimeout); 
} else { if (IsSet($_COOKIE['InterText_aid'])) $aid = $_COOKIE['InterText_aid']; else $aid = 0; }

if (IsSet($_REQUEST['alpagesize'])) { 
  $alpagesize = preg_replace('/[^0-9]/','',$_REQUEST['alpagesize']);
  setcookie('InterText_alpagesize',$alpagesize,$ctimeout); 
} else { if (IsSet($_COOKIE['InterText_alpagesize'])) $alpagesize = $_COOKIE['InterText_alpagesize']; else $alpagesize = 30; }

if (IsSet($_REQUEST['alpageoffset'])) { 
  $alpageoffset = preg_replace('/[^0-9]/','',$_REQUEST['alpageoffset']);
  setcookie('InterText_alpageoffset',$alpageoffset,$ctimeout); 
} else { if (IsSet($_COOKIE['InterText_alpageoffset'])) $alpageoffset = $_COOKIE['InterText_alpageoffset']; else $alpageoffset = 0; }

if (IsSet($_REQUEST['f_tname']) || IsSet($_REQUEST['f_v1name']) || IsSet($_REQUEST['f_v2name'])
    || IsSet($_REQUEST['f_editor'])|| IsSet($_REQUEST['f_resp'])|| IsSet($_REQUEST['f_status'])) {
  setcookie('InterText_alpageoffset',0,$ctimeout);$alpageoffset = 0;
}

if (IsSet($_REQUEST['txt'])) { $txt = preg_replace('/[^a-zA-Z0-9_\-]/','',$_REQUEST['txt']); setcookie('InterText_txt',$txt,$ctimeout); setcookie('InterText_alpageoffset',0,$ctimeout); }
else { if (IsSet($_COOKIE['InterText_txt'])) $txt = $_COOKIE['InterText_txt']; else $txt = ''; }

if (IsSet($_REQUEST['txtver'])) { $txtver = preg_replace('/[^0-9]/','',$_REQUEST['txtver']); setcookie('InterText_txtver',$txtver,$ctimeout); setcookie('InterText_alpageoffset',0,$ctimeout);}
else { if (IsSet($_COOKIE['InterText_txtver'])) $txtver = $_COOKIE['InterText_txtver']; else $txtver = 0; }

if (IsSet($_REQUEST['limit'])) { $limit = preg_replace('/[^0-9]/','',$_REQUEST['limit']); setcookie('InterText_limit',$limit,$ctimeout); }
else { if (IsSet($_COOKIE['InterText_limit'])) $limit = $_COOKIE['InterText_limit']; else $limit = $DEFAULT_LIMIT; }

if (IsSet($_REQUEST['offset'])) { $offset = preg_replace('/[^0-9]/','',$_REQUEST['offset']); setcookie('InterText_offset',$offset,$ctimeout); }
else { if (IsSet($_COOKIE['InterText_offset'])) $offset = $_COOKIE['InterText_offset']; else $offset = 1; }

/*if (IsSet($_REQUEST['autofilter'])) { $autofilter = preg_replace('/[^0-9]/','',$_REQUEST['autofilter']); setcookie('InterText_autofilter',$autofilter,$ctimeout); }
else { if (IsSet($_COOKIE['InterText_autofilter'])) $autofilter = $_COOKIE['InterText_autofilter']; else $autofilter = 0; }*/

if (IsSet($_REQUEST['req'])) $req = preg_replace('/[^a-z_]/','',$_REQUEST['req']);
else $req = 'list';

if (IsSet($_REQUEST['pos'])) $pos = preg_replace('/[^0-9]/','',$_REQUEST['pos']);

if (IsSet($_REQUEST['mode'])) { $mode = preg_replace('/[^0-9]/','',$_REQUEST['mode']); setcookie('InterText_mode',$mode,$ctimeout); }
else { if (IsSet($_COOKIE['InterText_mode'])) $mode = $_COOKIE['InterText_mode']; else $mode = $DEFAULT_MODE; }
if ($mode>=2 && $mode<=5) $system->auto_status_update=true; else $system->auto_status_update=false;

if (IsSet($_REQUEST['alorder'])) { 
	$alorder = preg_replace('/[^a-z0-9]/','',$_REQUEST['alorder']);
	setcookie('InterText_alorder',$alorder,$ctimeout);
	if (substr($alorder,0,3)!='ver') setcookie('InterText_torder','no',$ctimeout);
	Header("Location: $myurl"); 
} else { 
	if (IsSet($_COOKIE['InterText_alorder'])) $alorder = $_COOKIE['InterText_alorder']; 
	else $alorder = $DEFAULT_ALORDER;
}

if (IsSet($_REQUEST['torder'])) { 
	$torder = preg_replace('/[^a-z]/','',$_REQUEST['torder']);
	if ($torder!='no' && substr($alorder,0,3)!='ver')
		$_SESSION['WARNING'] = 'Primary sorting by text name can only be used in combination with sorting by name of some version.';
	else 
		setcookie('InterText_torder',$torder,$ctimeout);
	Header("Location: $myurl");
	return;
} else { 
	if (IsSet($_COOKIE['InterText_torder'])) $torder = $_COOKIE['InterText_torder']; 
	else $torder = $DEFAULT_TORDER;
}

if (IsSet($_REQUEST['f_tname'])) $_SESSION['f_tname'] = trim($_REQUEST['f_tname']);
if (IsSet($_REQUEST['f_v1name'])) $_SESSION['f_v1name'] = $_REQUEST['f_v1name'];
if (IsSet($_REQUEST['f_v2name'])) $_SESSION['f_v2name'] = $_REQUEST['f_v2name'];

if (IsSet($_REQUEST['f_editor'])) { 
	$_SESSION['f_editor'] = preg_replace('/[^0-9\*]/','',$_REQUEST['f_editor']);
} else { if (!IsSet($_SESSION['f_editor'])) $_SESSION['f_editor']='*'; }

if (IsSet($_REQUEST['f_resp'])) { 
	$_SESSION['f_resp'] = preg_replace('/[^0-9\*]/','',$_REQUEST['f_resp']);
} else { if (!IsSet($_SESSION['f_resp'])) $_SESSION['f_resp']='*'; }

if (IsSet($_REQUEST['f_status'])) { 
	$_SESSION['f_status'] = preg_replace('/[^0-9\*]/','',$_REQUEST['f_status']);
} else { if (!IsSet($_SESSION['f_status'])) $_SESSION['f_status']='*'; }

if (IsSet($_SESSION['WARNING'])) {
 $WARNING = $_SESSION['WARNING']; unset($_SESSION['WARNING']);
} else $WARNING = '';

if (!IsSet($_COOKIE['InterText_highlight'])) setcookie('InterText_highlight',$DEFAULT_HIGHLIGHT,$ctimeout);
if (!IsSet($_COOKIE['InterText_hide'])) setcookie('InterText_hide',$DEFAULT_HIDE,$ctimeout);

function set_offset($mode,$position,$force=false) {
	global $offset;
	switch ($mode) {
	case '8':
	case '5':
		$newpos = $position;
		break;
	case '7':
	case '4':
		$newpos = $position-2;
		break;
	case '6':
	case '3':
		$newpos = $position-5;
		break;
	default:
		if ($force) $newpos=$position;
		else $newpos = $offset;
	}
	if ($newpos<1) $newpos = 1;
	$_SESSION['position'] = $position;
	Header("Location: $myurl?offset=$newpos");
	return;
}

function print_changelog($changelog) {
	global $PERMISSIONS, $USERS, $CHANGE;
	$ret = "<table class=\"changelog-table\">\n";
	foreach ($changelog as $change) {
		if ($PERMISSIONS[$change['txtver_id']]['chtext'] && !$PERMISSIONS['readonly']) 
			$text = "<a href=\"aligner.php?req=revert&amp;chid={$change['id']}\" onclick=\"return revertConfirm()\">{$change['old_contents']}</a>";
		else $text = $change['old_contents'];
		switch ($change['chtype']) {
		case 'E':
			$comm = "CHANGED";
			break;
		case 'M':
			$comm = "MERGED with the following element";
			break;
		case 'S':
			$comm = "SPLIT into several (following) elements";
			break;
		case 'I':
			$comm = "CREATED by splitting preceding element";
			break;
		case 'D':
			$comm = "DELETED in the merger";
			break;
		case 'X':
			$comm = "DELETED with a par-break in the merger";
			break;
		case 'R':
			$comm = "PARENT/PARAGRAPH break DELETED";
			break;
		case 'N':
			$comm = "PARENT/PARAGRAPH break INSERTED";
			break;
		}
		$ret .= "<tr><td class=\"changetype_{$change['chtype']} comment\"><span title=\"{$CHANGE[$change['chtype']]}\">$comm [by {$USERS[$change['userid']]['name']} on {$change['ts']}]<span></td></tr>\n";
		if ($change['chtype']!='I' && $change['chtype']!='R' && $change['chtype']!='N')
			$ret.="<tr><td class=\"changetext\">{$text}</td></tr>\n";
		if ($change['chtype']=='M') {
			$ret .= '<tr><td class="mergehist"><span class="comment">History of changes of the merged-in/appended element:</span>';
			$ret .= print_changelog($change['assoc']);
			$ret .= "</td></tr>\n";
		}
	}
	$ret .= "</table>\n";
	return $ret;
}

function print_alignment_changelog($al, $changelog) {
  global $PERMISSIONS, $USERS, $CHANGE_ALIGN;
  $ret = "<table class=\"alchangelog-table\">\n";
    $ret .= "<tr>";
    $ret .= "<th>change</th>";
    $ret .= "<th>position</th>";
    $ret .= "<th>p/min</th>";
    $ret .= "<th>user</th>";
    $ret .= "<th>time</th>";
    $ret .= "</tr>\n";
  $lastchange = array();
  foreach ($changelog as $change) {
    switch ($change['chtype']) {
    case 'S':
      $icon = "arrow-up.png";
      break;
    case 'P':
      $icon = "arrow-down.png";
      break;
    case 'U':
      $icon = "arrow-up-double.png";
      break;
    case 'D':
      $icon = "arrow-down-double.png";
      break;
    case 'M':
      $icon = "go-up.png";
      break;
    case 'I':
      $icon = "go-down.png";
      break;
    }
    $sp = false;
    if ($change['userid']==$lastchange['userid'] && abs($change['position']-$lastchange['position'])>5) {
      $start = strtotime($lastchange['ts']);
      $end = strtotime($change['ts']);
      $mins = round(abs($end - $start) / 60,2);
      if ($mins>0)
        $sp = round(($change['position']-$lastchange['position'])/$mins);
    }
    $icon = "<img title=\"{$CHANGE_ALIGN[$change['chtype']]}\" alt=\"{$change['chtype']}\" src=\"icons/$icon\"/>";
    if ($change['version_id']) {
      if ($change['version_id'] == $al['ver1_id'])
        $icon .= ' '.$al['ver1_name'];
      elseif ($change['version_id'] == $al['ver2_id'])
        $icon .= ' '.$al['ver2_name'];
      else
        $icon .= ' ???';
    }
    $ret .= "<tr class=\"changetype_{$change['chtype']}\">";
    $ret .= "<td>$icon</td>";
    $ret .= "<td class=\"right\"><a href=\"{$myurl}?req=setpos&amp;aid={$al['id']}&amp;pos={$change['position']}\">{$change['position']}</a></td>";
    $ret .= "<td class=\"right\">{$sp}</td>";
    $ret .= "<td>{$USERS[$change['userid']]['name']}</td>";
    $ret .= "<td>{$change['ts']}</td>";
    $ret .= "</tr>\n";
    $lastchange = $change;
  }
  $ret .= "</table>\n";
  return $ret;
}

# FIXME?: clean this dirt from the double redesign... we do not really need $txt anymore... do we?
if ($aid>0) {
	$al = $system->alignment_info($aid);
	if ($al) $txt = $al['text_id'];
	else $aid = 0;
}

# Set permissions
if ($aid>0) $PERMISSIONS = set_alignment_settings($al);
else $PERMISSIONS = $SET;

# Process quick requests
switch ($req) {
case "swap":
	$ids = preg_replace('/[^0-9,]/','',$_REQUEST['id']);
  $idlist = explode(',',$ids);
  $_SESSION['WARNING'] = '';
  foreach ($idlist as $id) {
    $al = $system->alignment_info($id);
    $alname = $al['text_name'].' ('.$al['ver1_name'].'-'.$al['ver2_name'].')';
    if ($al['status']!=ALSTAT_OPEN) {
      $_SESSION['WARNING'].=$alname.': ERROR: Alignment not open. Change status first.<br/>';
      continue;
    }
    if ($USER['type']==$USER_ADMIN) {
      $system->swap_versions($id);
    } else {
      $_SESSION['WARNING']=$alname.': ERROR: Permission denied.<br/>';
    }
  }
  Header("Location: $myurl?batch=".join(',',$batch)."#al$id");
	return;
	break;	
case "cheditor":
	$ids = preg_replace('/[^0-9,]/','',$_REQUEST['id']);
	$user = preg_replace('/[^0-9]/','',$_REQUEST['user']);
  $_SESSION['WARNING'] = '';
  $idlist = explode(',',$ids);
  foreach ($idlist as $id) {
    $al = $system->alignment_info($id);
    $alname = $al['text_name'].' ('.$al['ver1_name'].'-'.$al['ver2_name'].')';
    if ($al['status']!=ALSTAT_OPEN) {
      $_SESSION['WARNING'].=$alname.': ERROR: Alignment not open. Change status first.<br/>';
      continue;
    }
    if ($USER['type']==$USER_ADMIN || ($USER['type']==$USER_RESP && $al['resp']==$USER['id'])) {
      $system->alignment_cheditor($id,$user);
    } else {
      $_SESSION['WARNING'].=$alname.': ERROR: Permission denied.<br/>';
    }
  }
  Header("Location: $myurl?batch=".join(',',$batch)."#al$id");
	return;
	break;
case "chresp":
	$ids = preg_replace('/[^0-9,]/','',$_REQUEST['id']);
	$user = preg_replace('/[^0-9]/','',$_REQUEST['user']);
  $_SESSION['WARNING'] = '';
	if ($user=='---') {
		Header("Location: $myurl?batch=".join(',',$batch)."#al$id");
		break;
	}
  $idlist = explode(',',$ids);
  foreach ($idlist as $id) {
    $al = $system->alignment_info($id);
    $alname = $al['text_name'].' ('.$al['ver1_name'].'-'.$al['ver2_name'].')';
    if ($al['status']!=ALSTAT_OPEN) {
      $_SESSION['WARNING'].=$alname.': ERROR: Alignment not open. Change status first.<br/>';
      continue;
    }
    if ($USER['type']==$USER_ADMIN || ($USER['type']==$USER_RESP && $al['resp']==$USER['id'])) {
      $system->alignment_chresp($id,$user);
    } else {
      $_SESSION['WARNING'].=$alname.': ERROR: Permission denied.<br/>';
    }
  }
  Header("Location: $myurl?batch=".join(',',$batch)."#al$id");
	return;
	break;
case "chalstat":
	$ids = preg_replace('/[^0-9,]/','',$_REQUEST['id']);
	$stat = preg_replace('/[^0-9]/','',$_REQUEST['value']);
  $_SESSION['WARNING'] = '';
  $idlist = explode(',',$ids);
  foreach ($idlist as $id) {
    $al = $system->alignment_info($id);
    $alname = $al['text_name'].' ('.$al['ver1_name'].'-'.$al['ver2_name'].')';
    if ($USER['type']==$USER_ADMIN || (($USER['type']==$USER_RESP && $al['resp']==$USER['id'] && ($stat==ALSTAT_OPEN || $stat==ALSTAT_FINISHED)))) {
      if (!$system->alignment_chstat($id,$stat)) 
        $_SESSION['WARNING'] .= $alname.': '.$_ERROR.'<br/> ';
    } else {
      $_SESSION['WARNING'].=$alname.': ERROR: Permission denied.<br/>';
    }
  }
	Header("Location: $myurl?batch=".join(',',$batch)."#al$id");
	return;
	break;
case "chtext":
	$ids = preg_replace('/[^0-9,]/','',$_REQUEST['id']);
	if (IsSet($_REQUEST['chtext']) && $_REQUEST['chtext']) $value = 1; else $value = 0;
  $_SESSION['WARNING'] = '';
  $idlist = explode(',',$ids);
  foreach ($idlist as $id) {
    $al = $system->alignment_info($id);
    $alname = $al['text_name'].' ('.$al['ver1_name'].'-'.$al['ver2_name'].')';
    if ($al['status']!=ALSTAT_OPEN) {
      $_SESSION['WARNING'].=$alname.': ERROR: Alignment not open. Change status first.<br/>';
      continue;
    }
    if ($USER['type']==$USER_ADMIN || ($USER['type']==$USER_RESP && $al['resp']==$USER['id'])) {
      $system->alignment_chtext($id,$value);
    } else {
      $_SESSION['WARNING'].=$alname.': ERROR: Permission denied.<br/>';
    }
  }
  Header("Location: $myurl?batch=".join(',',$batch)."#al$id");
	return;
	break;
case "chcstruct":
	$ids = preg_replace('/[^0-9,]/','',$_REQUEST['id']);
	if (IsSet($_REQUEST['chcstruct']) && $_REQUEST['chcstruct']) $value = 1; else $value = 0;
  $_SESSION['WARNING'] = '';
  $idlist = explode(',',$ids);
  foreach ($idlist as $id) {
    $al = $system->alignment_info($id);
    $alname = $al['text_name'].' ('.$al['ver1_name'].'-'.$al['ver2_name'].')';
    if ($al['status']!=ALSTAT_OPEN) {
      $_SESSION['WARNING'].=$alname.': ERROR: Alignment not open. Change status first.<br/>';
      continue;
    }
    if ($USER['type']==$USER_ADMIN) {
      $system->alignment_chcstruct($id,$value);
    } else {
      $_SESSION['WARNING']=$alname.': ERROR: Permission denied.<br/>';
    }
  }
  Header("Location: $myurl?batch=".join(',',$batch)."#al$id");
	return;
	break;
case "moveto":
	$id = preg_replace('/[^0-9]/','',$_REQUEST['id']);
	$newpos = preg_replace('/[^0-9]/','',$_REQUEST['newpos']);
	if ($PERMISSIONS['readonly']) return;
	if (($ret=$system->move_to($txt,$id,$newpos))===true) {
		set_offset($mode,$newpos);
	} else {
		$_SESSION['WARNING']="ERROR: $ret";
		Header("Location: $myurl");
		return;
	}
	return;
	break;
case "get_profiles":
	$al = preg_replace('/[^a-zA-Z_0-9\-]/','',$_REQUEST['al']);
	$profiles = $system->autoalignment_profiles($al);
	foreach($profiles as $p) print "<option value=\"$p\">$p</option>\n";
	return;
	break;
case "toggle_highlight":
	if ($_COOKIE['InterText_highlight']) setcookie('InterText_highlight',0,$ctimeout);
	else setcookie('InterText_highlight',1,$ctimeout);
	Header("Location: $myurl");
	return;
	break;
case "toggle_vis":
	if ($_COOKIE['InterText_hide']) setcookie('InterText_hide',0,$ctimeout);
	else setcookie('InterText_hide',1,$ctimeout);
	Header("Location: $myurl");
	return;
	break;
case "toggle_searchbar":
	if ($_SESSION['searchbar']) $_SESSION['searchbar']=FALSE;
	else $_SESSION['searchbar']=TRUE;
	Header("Location: $myurl");
	return;
	break;
case "chstat":
	$status = preg_replace('/[^0-9]/','',$_REQUEST['val']);
	if (!$PERMISSIONS['readonly']) $system->change_status($aid,$pos,$status);
	Header("Location: $myurl#$pos");
	return;
	break;
case "mark":
	$mark = preg_replace('/[^0-9]/','',$_REQUEST['mark']);
	$al = $system->alignment_info($aid);
	if (!$PERMISSIONS['readonly'] || $USER['id']==$al['resp']) $system->change_mark($aid,$pos,$mark);
	Header("Location: $myurl#".($pos-1));
	return;
	break;
case "delete":
	if (!$PERMISSIONS['delalign']) {
		$WARNING="You do not have permission to delete alignments.";
		break;
	}
	$ids = preg_replace('/[^0-9,]/','',$_REQUEST['id']);
  $idlist = explode(',',$ids);
  foreach ($idlist as $id) {
    $system->delete_alignment($id);
  }
  Header("Location: $myurl");
	return;
	break;
case "export_alignment":
	$format = preg_replace('/[^a-zA-Z_0-9\-:]/','',$_REQUEST['format']);
	if (!$PERMISSIONS['export']) {
		$WARNING="You do not have permission to export this alignment.";
		break;
	}
	$al = $system->alignment_info($aid);
	$txt = $al['text_id'];
	$v1_id = $al['ver1_id']; $v2_id = $al['ver2_id'];
	if (!$al['v1uniq_ids']) { $system->update_eids($txt,$v1_id); $al = $system->alignment_info($aid); }
	if (!$al['v2uniq_ids']) { $system->update_eids($txt,$v2_id); $al = $system->alignment_info($aid); } 
	if (!$al['v1uniq_ids'] OR !$al['v2uniq_ids']) {
		$_SESSION['WARNING']="ERROR: Renumbering IDs failed!";
		Header("Location: $myurl");
		return; break;
	}
	$fullname = $al['text_name'].".".$al['ver1_name'].'.'.$al['ver2_name'].'.alignment';
	Header("Content-type: text/xml; charset=UTF-8");
	Header("Content-Disposition: attachment; filename=\"$fullname.xml\"");
	print $system->export_alignment($aid,$format);
	return;
	break;
case "export_text":
	$ver = preg_replace('/[^0-9]/','',$_REQUEST['ver']);
	$format = preg_replace('/[^a-zA-Z_0-9\-:]/','',$_REQUEST['format']);
	if (!$PERMISSIONS['export']) {
		$WARNING="You do not have permission to export this document.";
		break;
	}
	$al = $system->alignment_info($aid);
	$txt = $al['text_id'];
	if ($ver==1) { $vid = $al['ver1_id']; $vername=$al['ver1_name']; }
	else { $vid = $al['ver2_id']; $vername=$al['ver2_name']; }
	$fullname = $al['text_name'].".".$vername;
	if ($format!='') {
		$out = $system->export_document($txt,$vid,$aid,$format);
		if ($out) {
			Header("Content-type: text/xml; charset=UTF-8");
			Header("Content-Disposition: attachment; filename=\"$fullname.xml\"");
			print $out;
		} else {
			$_SESSION['WARNING'] = $_ERROR;
			Header("Location: $myurl");
		}
	}
	else Header("Location: $myurl");
	return;
	break;
case "status":
	$status = preg_replace('/[^0-9]/','',$_REQUEST['val']);
	if (!$ret = $system->get_pos_by_status($aid,$status)) {
		$_SESSION['WARNING'] = "No links with such status.";
		Header("Location: $myurl");
	} else set_offset($mode,$ret,true);
	return;
	break;
case "nextmark":
	$dirup = TRUE;
case "prevmark":
	if ($_SESSION['position']>=$offset && $_SESSION['position']<=($offset+$limit-1)) 
		$mypos = $_SESSION['position'];
	else $mypos = $offset;
	if (!$dirup) $dirup=FALSE;
	if (!$ret = $system->get_next_mark($aid,$mypos,$dirup)) $WARNING = "No more marks in this direction.";
	else {
		set_offset($mode,$ret,true);
		return;
	}
	break;
case "setpos":
	$al = $system->alignment_info($aid);
	if ($pos>$al['link_count']) {
		$_SESSION['WARNING']='Such position does not exist in this alignment.';
		Header("Location: $myurl");
		return;
	} else set_offset($mode,$pos,true);
	break;
case "pos_dec":
	$id = preg_replace('/[^0-9]/','',$_REQUEST['item']);
	if ($PERMISSIONS['readonly']) return;
	$system->alitem_pos_dec($txt,$aid,$id,$pos,$USER['id']);
	$pos--;
	set_offset($mode,$pos);
	return;
	break;
case "pos_inc":
	$id = preg_replace('/[^0-9]/','',$_REQUEST['item']);
	if ($PERMISSIONS['readonly']) return;
	$system->alitem_pos_inc($txt,$aid,$id,$pos,$USER['id']);
	set_offset($mode,$pos);
	return;
	break;
case "ver_dec":
	$ver = preg_replace('/[^0-9]/','',$_REQUEST['ver']);
	if ($PERMISSIONS['readonly']) return;
	$system->alver_dec($txt,$aid,$ver,$pos,$USER['id']);
	$pos--;
	set_offset($mode,$pos);
	return;
	break;
case "ver_inc":
	$ver = preg_replace('/[^0-9]/','',$_REQUEST['ver']);
	if ($PERMISSIONS['readonly']) return;
	$system->alver_inc($txt,$aid,$ver,$pos,$USER['id']);
	set_offset($mode,$pos);
	return;
	break;
case "both_dec":
	if ($PERMISSIONS['readonly']) return;
	$system->alpos_dec($txt,$aid,$pos,$USER['id']);
	$pos--;
	set_offset($mode,$pos);
	return;
	break;
case "both_inc":
	if ($PERMISSIONS['readonly']) return;
	$system->alpos_inc($txt,$aid,$pos,$USER['id']);
	set_offset($mode,$pos);
	return;
	break;
case "confirm_status":
	if ($PERMISSIONS['readonly']) return;
	$system->update_manual_status($txt,$aid,$pos);
	$pos++;
	if (IsSet($_REQUEST['roll']))  {
		setcookie('InterText_offset',$pos,$ctimeout);
		Header("Location: $myurl");
	} else Header("Location: $myurl#$pos");
	return;
	break;
case "update_element":
	$id = preg_replace('/[^0-9]/','',$_POST["id"]);
	$text = stripslashes(rawurldecode($_POST["new_value"]));
	$orig = stripslashes(rawurldecode($_POST["orig_value"]));
	$text = preg_replace('/\r/','',$text);
	$text = htmlspecialchars($text,ENT_NOQUOTES);
	$text = preg_replace("/&lt;(\/?)($TAGS_IN_TEXT)(.*?)&gt;/i",'<$1$2$3>',$text);
	$vid = $system->get_ver_by_id($txt,$id);
	if ($PERMISSIONS['readonly']) {
		print json_encode(array('is_error'=>true,'error_text'=>'Permission denied. Text not updated!',"html"=>$orig));
		return;
	}
	if ($DENY_EMPTY_UPDATES && preg_match('/^\s*$/', $text)) {
		print json_encode(array('is_error'=>true,'error_text'=>'Empty elements are not accepted. You are obviously trying something nasty. Text not updated!',"html"=>$orig));
		return;
	}
	#print json_encode(array('is_error'=>true,'error_text'=>"Failure: -$vid-","html"=>$orig)); return;
	if ($PERMISSIONS[$vid]['chstruct'] AND preg_match('/\n\n/',$text)) {
		if ($system->split_element($txt,$id,explode("\n\n",$text),$USER['id']))
			print json_encode(array('is_error'=>true,'error_text'=>'reload',"html"=>"...reloading page..."));
		else
			print json_encode(array('is_error'=>true,'error_text'=>'Failure, text not updated! '.$_ERROR,"html"=>$orig));
		return;
	}
	$text = preg_replace('/\n+/',' ',$text);
	if($PERMISSIONS[$vid]['chtext'] AND $system->update_element_text($txt,$id,$text,$USER['id']))
		print json_encode(array('is_error'=>false,'error_text'=>'',"html"=>$text));
	else
		print json_encode(array('is_error'=>true,'error_text'=>'Failure, text not updated! '.$_ERROR,"html"=>$orig));
	return;
	break;
case "merge":
	if ($PERMISSIONS['readonly']) return;
	$id = preg_replace('/[^0-9]/','',$_REQUEST["id"]);
	$vid = $system->get_ver_by_id($txt,$id);
	if (!$PERMISSIONS[$vid]['chstruct']) {
		$_SESSION['WARNING']="FAILURE: You do not have permission to change the structure of this text version!";
		Header("Location: $myurl#$pos");
		return;
	}
	elseif (!$system->merge_elements($txt,$id,$USER['id'])) {
		$_ERROR = preg_replace("/alignment '([^']*)' \(([0-9]*)\) at positions ([0-9]*)\/([0-9]*)/","<a href=\"{$myurl}?aid=\${2}&amp;offset=\${3}\" target=\"_blank\">alignment '\${1}' at positions \${3}/\${4}</a>",$_ERROR);
		$_SESSION['WARNING'] = $_ERROR;
	}
	Header("Location: $myurl#$pos");
	return;
	break;
case "delpar":
	if ($PERMISSIONS['readonly']) return;
	$id = preg_replace('/[^0-9]/','',$_REQUEST["id"]);
	$vid = $system->get_ver_by_id($txt,$id);
	if (!$PERMISSIONS[$vid]['chstruct']) {
		$_SESSION['WARNING']="FAILURE: You do not have permission to change the structure of this text version!";
		Header("Location: $myurl#$pos");
		return;
	}
	elseif (!$system->merge_parents($txt,$id,$USER['id'])) {
		$_SESSION['WARNING'] = $_ERROR;
	}
	Header("Location: $myurl#$pos");
	return;
	break;
case "newpar":
	if ($PERMISSIONS['readonly']) return;
	$id = preg_replace('/[^0-9]/','',$_REQUEST["id"]);
	$vid = $system->get_ver_by_id($txt,$id);
	if (!$PERMISSIONS[$vid]['chstruct']) {
		$_SESSION['WARNING']="FAILURE: You do not have permission to change the structure of this text version!";
		Header("Location: $myurl#$pos");
		return;
	}
	elseif (!$system->split_parent($txt,$id,$USER['id'])) {
		$_SESSION['WARNING'] = $_ERROR;
	}
	Header("Location: $myurl#$pos");
	return;
	break;
case 'search':
	$version = preg_replace('/[^0-9]/','',$_POST["version"]);
	$type = stripslashes($_POST["type"]);
	$string = stripslashes($_POST["string"]);
	$dir = stripslashes($_POST["dir"]);
	$_SESSION['search_string'] = $string;
	$_SESSION['search_type'] = $type;
	$_SESSION['search_version'] = $version;
	if ($_SESSION['position']>=$offset && $_SESSION['position']<=($offset+$limit-1)) 
		$mypos = $_SESSION['position'];
	else $mypos = $offset;
	$_ERROR='';
	if ($dir=='') $dir='up';
	$ret = $system->alignment_search($aid,$version,$string,$mypos,$dir,$type);
	if ($ret) {
		$_SESSION['WARNING']=$_ERROR;
		set_offset($mode,$ret,true);
		return;
	} else $WARNING = $_ERROR;
	break;
case 'changelog':
	$id = preg_replace('/[^0-9]/','',$_REQUEST["id"]);
	$al = $system->alignment_info($aid);
	$txt = $al['text_id'];
	$changelog = $system->get_changelog($txt,$id);
	print print_changelog($changelog);
	return;
	break;
case 'revert';
	$chid = preg_replace('/[^0-9]/','',$_REQUEST["chid"]);
	$al = $system->alignment_info($aid);
	$ch = $system->get_change($aid,$chid);
	if (!$PERMISSIONS['readonly'] && $PERMISSIONS[$ch['txtver_id']]['chtext']) {
		if ($system->revert_change($aid,$chid,$USER['id'])===FALSE) {
			$_SESSION['WARNING']=$_ERROR;
		}
	}
	Header("Location: $myurl#$pos");
	return;
	break;
case "toggle_permchangelog":
	if ($_SESSION['changelog']) $_SESSION['changelog']=FALSE;
	else $_SESSION['changelog']=TRUE;
	Header("Location: $myurl");
	return;
	break;
} # end of CASE

if ($_COOKIE['InterText_hide']) $hide = ' hidden'; else $hide = false;

#if (IsSet($_REQUEST['pos'])) {
#	Header("Location: $myurl#$pos");
#	return;
#}

#### HTML PAGE START ####
require 'header.php';

define('SINGLE_UP',"<a href=\"$myurl?req=pos_dec&amp;aid=$aid&amp;item=%s&amp;pos=%s\" class=\"img\"><img src=\"icons/arrow-up.png\" alt=\"element up\" title=\"push first element up\"/></a>");
define ('MOVE_UP',"<a href=\"$myurl?req=ver_dec&amp;aid=$aid&amp;ver=%s&amp;pos=%s\" class=\"img\"><img src=\"icons/arrow-up-double.png\" alt=\"text up\"  title=\"move text up\"/></a>");
define('MOVE_DOWN',"<a href=\"$myurl?req=ver_inc&amp;aid=$aid&amp;ver=%s&amp;pos=%s\" class=\"img\"><img src=\"icons/arrow-down-double.png\" alt=\"text down\" title=\"move text down\"/></a>");
define('SINGLE_DOWN',"<a href=\"$myurl?req=pos_inc&amp;aid=$aid&amp;item=%s&amp;pos=%s\" class=\"img\"><img src=\"icons/arrow-down.png\" alt=\"element down\" title=\"push last element down\"/></a>");
define ('BOTH_UP',"<a href=\"$myurl?req=both_dec&amp;aid=$aid&amp;pos=%s\" class=\"img\"><img src=\"icons/go-up.png\" alt=\"both up\"  title=\"move both up\"/></a>");
define('BOTH_DOWN',"<a href=\"$myurl?req=both_inc&amp;aid=$aid&amp;pos=%s\" class=\"img\"><img src=\"icons/go-down.png\" alt=\"both down\" title=\"move both down\"/></a>");

# Print controls
function controls($firstcolumn,$vid,$flid,$llid,$pos) {
	global $hide,$PERMISSIONS;
	$jvbind = '';
	$ret = '';
	if ($firstcolumn) {
		$p = 'l';
		$ret .= sprintf("<tr class=\"upper\"><td>".SINGLE_UP."</td><td>".MOVE_UP."</td></tr>",$flid,$pos,$vid,$pos);
		$ret .= sprintf("<tr class=\"lower\"><td>".SINGLE_DOWN."</td><td>".MOVE_DOWN."</td></tr>",$llid,$pos,$vid,$pos);
	} else {
		$p = 'r';
		$ret .= sprintf("<tr class=\"upper\"><td>".MOVE_UP."</td><td>".SINGLE_UP."</td></tr>",$vid,$pos,$flid,$pos);
		$ret .= sprintf("<tr class=\"lower\"><td>".MOVE_DOWN."</td><td>".SINGLE_DOWN."</td></tr>",$vid,$pos,$llid,$pos);
	}
	if ($hide) $jvbind = " onMouseOver=\"showElement('$pos-$vid')\" onMouseOut=\"hideElement('$pos-$vid')\"";
	$ret = "<td class=\"{$p}controls\"$jvbind>\n<table class=\"control$hide\" id=\"$pos-$vid\">$ret</table>\n</td>\n";
	return $ret;
}

# Print one half-row in table
function halfrow($position,$row,$vid) {
	global $myurl,$firstcolumn,$txt,$aid,$PERMISSIONS,$hide,$system;
	$maxstat=0; $maxmark=0; $jvbind = ''; $controls = ''; $ret='';
	if (IsSet($row[$vid])) {
		$el=array(); unset($fitem);
		for($i=0;$i<count($row[$vid]);$i++) {
			$item = $row[$vid][$i];
			if ($item['link_status']>$maxstat) $maxstat=$item['link_status'];
			if ($item['link_mark']>$maxmark) $maxmark=$item['link_mark'];
			if ($item['position']==1) $img="<img src=\"icons/arrow.png\" class=\"newline\" alt=\"1.pos\"/><img src=\"icons/arrow.png\" class=\"newelement\" alt=\"&gt;\"/>"; else $img="<img src=\"icons/arrow.png\" class=\"newelement\" alt=\"&gt;\"/>";
			if (!$PERMISSIONS['readonly']) {
				$defchange = ''; $extch='';
				if ($PERMISSIONS[$vid]['chstruct']) {
					if ($item['position']==1) { $defchange = 'del'; $extch=' or delete paragraph break';}
					else { $defchange = 'new'; $extch=' or insert paragraph break';}
				}
				$img = "<a href=\"javascript:moveDialog($aid,$txt,{$item['link_id']},'$defchange',{$item['id']})\" title=\"move to another position$extch\">$img</a>";
			}
			if (!$_SESSION['changelog'] && intval($item['changes'])>0) $cl=''; else $cl=' invisible';
			$ch ='<span id="chlb_'.$item['id'].'" class="changelogbutton'.$cl.'"><a href="#" onclick="showChangelog(this,'.$aid.','.$item['id'].',\'on\')" title="changes"><img src="icons/changelog.png" alt="[ch]" class="chlb"/></a></span>';
			$img.=$ch;
			if (!$PERMISSIONS['readonly'] && $PERMISSIONS[$vid]['chstruct'] && $i!=count($row[$vid])-1) {
				if ($hide) $jvbind = "onMouseOver=\"showElement('m{$item['id']}')\" onMouseOut=\"hideElement('m{$item['id']}')";
				$merger = "<a href=\"$myurl?req=merge&amp;aid=$aid&amp;txt=$txt&amp;id={$item['id']}\" $jvbind\" onClick=\"return mergeConfirm(this,'{$item['id']}')\"><img class=\"merger$hide\" id=\"m{$item['id']}\" src=\"icons/merge.png\" alt=\"merge\" title=\"merge with the following element (sentence)\"/></a>";
			} else $merger='';
			if (!$PERMISSIONS['readonly'] && $PERMISSIONS[$vid]['chtext']) $editable='editable'; else $editable='';
			$comp = "<div class=\"element\">{$img}<span class=\"$editable\" id=\"{$item['id']}\" title=\"{$item['element_id']}\">".$item['contents']."</span>".$merger."</div><div class=\"changelog\" id=\"changes_{$item['id']}\">";
			if ($_SESSION['changelog'] && intval($item['changes'])>0)
				$comp .= print_changelog($system->get_changelog($txt,$item['id']));
			$comp .= "</div>";
			$el[] = $comp;
			if (!IsSet($fitem)) $fitem=$item; 
		}
		$contents = "<td class=\"contents\"><ul>\n".join("\n",$el)."\n</ul></td>\n";
		if (!$PERMISSIONS['readonly']) $controls = controls($firstcolumn,$vid,$fitem['link_id'],$item['link_id'],$position);
	} else { 
		$contents = "<td class=\"contents\">&nbsp;</td>\n";
		if ($firstcolumn) $p='l'; else $p='r';
		if (!$PERMISSIONS['readonly']) $controls = "<td class=\"{$p}controls\">&nbsp;</td>";
	}
	if ($firstcolumn) $ret = $contents.$controls; else $ret = $controls.$contents;
	$firstcolumn = FALSE;
	return array($maxstat,$maxmark,$ret);
}

# Print common controls for moving a position up/down
function print_center_control($position) {
	global $hide;
	if ($hide) $jvbind = " onMouseOver=\"showElement('b$position')\" onMouseOut=\"hideElement('b$position')\"";
	else $jvbind = "";
	$ret = sprintf("<tr><td>".BOTH_UP."</td></tr><tr><td>".BOTH_DOWN."</td></tr>",$position,$position);
	print "<td class=\"ccontrols\"$jvbind><table class=\"control$hide\" id=\"b$position\">$ret</table></td>\n";
}

# Re-align
if ($req=='realign' AND $aid>0 AND $PERMISSIONS['realign']) {
	if ($_REQUEST['action']=='run') {
		$_SESSION['realign'] = true;
		$_SESSION['al_ver1'] = 0;
		$_SESSION['al_ver2'] = 0;
		$_SESSION['al_default_status'] = STATUS_AUTOMATIC;
		if ($PERMISSIONS['chalmethod']) {
			$_SESSION['al_method'] = $_REQUEST['method'];
			$_SESSION['al_profile'] = $_REQUEST['profile'];
		} else {
			$al = $system->alignment_info($aid);
			$txt = $al['text_id'];
			if ($al['method']=='') $_SESSION['al_method'] =  $DEFAULT_METHOD;
			else $_SESSION['al_method'] = $al['method'];
			if ($al['profile']=='') $_SESSION['al_profile'] =  $DEFAULT_PROFILE;
			else $_SESSION['al_profile'] = $al['profile'];
			
		}
		$_SESSION['al_file'] = '';
		$rpos = $system->get_pos_by_status($aid,STATUS_AUTOMATIC);
		if ($rpos) $system->delete_alignment_from_pos($aid,$rpos);
?>
<div id="menubar">
<a href="<?php print $myurl; ?>">[back]</a>
&nbsp;<span id="logout"><a href="?req=logout">[logout]</a></span>
</div>
<div id="contents">
<?php
		include 'progress.php';
		print "</div>\n</body>\n</html>\n";
		return;
	} else {
		$oldpos = $offset;
		$rpos = $system->get_pos_by_status($aid,STATUS_AUTOMATIC);
		if ($rpos===false) {
			$WARNING = "Warning: No unconfirmed alignment to re-align.";
		} {
			$al = $system->alignment_info($aid);
			$txt = $al['text_id'];
			if ($rpos) $pos = $offset = $rpos;
			$selected = ' selected="selected"';
			if ($al['method']=='') $al['method'] =  $DEFAULT_METHOD;
			if ($al['profile']=='') $al['profile'] =  $DEFAULT_PROFILE;
			$profsel = '';
			$profiles = $system->autoalignment_profiles($al['method']);
			foreach($profiles as $p) {
				if ($al['profile']==$p) $sel=$selected; else $sel='';
				$profsel .= "<option value=\"$p\"$sel>$p</option>\n";
			}
?>
<div id="menubar">
<a href="<?php print $myurl; ?>">[back]</a>
&nbsp;<span id="logout"><a href="help.php#realign" title="help" target="_blank">[help]</a><a href="?req=logout">[logout]</a></span>
</div>
<div id="contents">
<div id="form-div">
<h1>Run new automatic alignment from position '<?php print $rpos; ?>':</h1>
<form action="<?php print $myurl; ?>" method="post" onSubmit="this.submit();document.getElementById('form-div').style.display='none'; document.getElementById('info').style.display='block';" class="upload">
	<fieldset>
	<input type="hidden" name="req" value="realign" />
	<input type="hidden" name="action" value="run" />
	<input type="hidden" name="aid" value="<?php print $aid; ?>" />
	<table class="form">
<?php if ($PERMISSIONS['chalmethod']) { ?>
	<tr>
		<td><label for="method"><strong>Method for automatic alignment:</strong></label></td>
		<td><select name="method" onChange="loadProfiles('<?php print $myurl; ?>?req=get_profiles&al='+this.value);">
				<option value="plain"<?php if ($al['method']=='plain') print $selected; ?>>plain alignment (1:1)</option>
				<option value="tca2"<?php if ($al['method']=='tca2') print $selected; ?>>TCA2 automatic alignment</option>
				<option value="hunalign"<?php if ($al['method']=='hunalign') print $selected; ?>>HUNALIGN automatic alignment</option>
		</select> <label for="profile">using profile:
		<select name="profile" id="profile_sel">
		<?php print $profsel; ?>
		</select>
		</td>
	</tr>
<?php } ?>
	<tr><td>&nbsp;</td><td><input type="checkbox" name="keep"> do not close log after finishing</td></tr>
	<tr><td colspan="2">
		<input type="submit" value="Re-align"/>
	</td></tr>
	</table>
	</fieldset>
</form>
<p><a href="<?php print $myurl; ?>">&lt;&lt; cancel</a></p>
</div>
<div id="info">
<p><strong>Process:</strong> Starting alignment...</p>
</div>
<?php
		}
	} # end of re-align form
} # end of re-align

# Create new alignment
if ($req=='create' AND $txt!='' AND $PERMISSIONS['newalign']) {
	if ($_REQUEST['action']=='run') {
		$process=true;
		$_SESSION['realign'] = false;
		$_SESSION['al_ver1'] = preg_replace('/[^0-9]/','',$_REQUEST["ver1"]);
		$_SESSION['al_ver2'] = preg_replace('/[^0-9]/','',$_REQUEST["ver2"]);
		$_SESSION['al_default_status'] = preg_replace('/[^0-9]/','',$_REQUEST["defstat"]);
		$_SESSION['al_method'] = $_REQUEST['method'];
		$_SESSION['al_profile'] = $_REQUEST['profile'];
		$_SESSION['al_file'] = '';
		if ($_FILES['file']['tmp_name']!='') {
			$filename = "/tmp/intertext.alignment_".$_SESSION['al_ver1']."_to_".$_SESSION['al_ver2'].".xml";
			$_SESSION['al_file'] = $filename;
			if (!move_uploaded_file($_FILES['file']['tmp_name'], $filename)) {
				$WARNING = "ERROR: Uploading file failed!";
				$process=false;
			}
		}
		if ($process) {
?>
<div id="menubar">
<a href="<?php print $myurl; ?>">[back]</a>
&nbsp;<span id="logout"><a href="?req=logout">[logout]</a></span>
</div>
<div id="contents">
<?php
			include 'progress.php';
		}
	}
	if ($_REQUEST['action']!='run' OR !$process) {
			$txtver = $system->txtver_by_id($txtver);
			$versions = $system->list_versions($txt);

			# Print warning, if there is any
			if ($WARNING!='') printf(WARNING_FORMAT,$WARNING);
			$selected = ' selected="selected"';
			$profsel = '';
			$profiles = $system->autoalignment_profiles($DEFAULT_METHOD);
			foreach($profiles as $p) {
				if ($DEFAULT_PROFILE==$p) $sel=$selected; else $sel='';
				$profsel .= "<option value=\"$p\"$sel>$p</option>\n";
			}
?>
<div id="menubar">
<a href="<?php print $myurl; ?>">[back]</a>
&nbsp;<span id="logout"><a href="help.php#newalign" title="help" target="_blank">[help]</a><a href="?req=logout">[logout]</a></span>
</div>
<div id="contents">
<div id="form-div">
<h1>Create new alignment for text version '<?php print "{$txtver['text_name']}.{$txtver['version_name']}"; ?>':</h1>
<form enctype="multipart/form-data" action="<?php print $myurl; ?>" method="post" onSubmit="this.submit();document.getElementById('form-div').style.display='none'; document.getElementById('info').style.display='block';" class="upload">
	<fieldset>
	<input type="hidden" name="MAX_FILE_SIZE" value="102400000" />
	<input type="hidden" name="req" value="create" />
	<input type="hidden" name="action" value="run" />
	<input type="hidden" name="txt" value="<?php print $txt; ?>" />
	<input type="hidden" name="ver1" value="<?php print $txtver['id']; ?>" />
	<table class="form">
	<tr>
		<td><label for="ver2"><strong>Align to version:</strong></label></td>
		<td><select name="ver2">
<?php
		foreach ($versions as $version) {
			if ($version['id']!=$txtver['id'])
			print "\t\t\t<option value=\"{$version['id']}\">{$version['version_name']}</option>\n";
			#else print "-".$text['text_id']."-".$txtver['text_id']."- ignored. ";
		}
?>
		</select></td>
	</tr>
	<tr>
		<td><label for="file"><strong>Import alignment:</strong></label></td>
		<td><input name="file" type="file" size="50" /></td>
	</tr>
	<tr>
		<td><label for="defstat"><strong>Default status:</strong></label></td>
		<td><select name="defstat">
				<option value="1" selected="selected">manual</option>
				<option value="2">automatic</option>
				<option value="3">plain</option>
		</select> (Default status will be set for imported alignments with unknown status.)</td>
	</tr>
	<tr>
		<td><label for="method"><strong>Method for automatic alignment:</strong></label></td>
		<td><select name="method" onChange="loadProfiles('<?php print $myurl; ?>?req=get_profiles&al='+this.value);">
				<option value="plain"<?php if ($DEFAULT_METHOD=='plain') print $selected; ?>>plain alignment (1:1)</option>
				<option value="tca2"<?php if ($DEFAULT_METHOD=='tca2') print $selected; ?>>TCA2 automatic alignment</option>
				<option value="hunalign"<?php if ($DEFAULT_METHOD=='hunalign') print $selected; ?>>HUNALIGN automatic alignment</option>
		</select> <label for="profile">using profile:
		<select name="profile" id="profile_sel">
		<?php print $profsel; ?>
		</select>
		</td>
	</tr>
	<tr><td>&nbsp;</td><td>(Plain alignment will be used as fallback solution for any unaligned elements left.)</td></tr>
	<tr><td>&nbsp;</td><td><input type="checkbox" name="keep"> do not close log after finishing</td></tr>
	<tr><td colspan="2">
		<input type="submit" value="Create alignment"/>
	</td></tr>
	</table>
	</fieldset>
</form>
</div>
<div id="info">st
<p><strong>Process:</strong> Uploading file...</p>
</div>
<?php
	} # end of form for new alignments
} 
# show alignment changelog
elseif ($req=='alchangelog') {
?>
<div id="menubar">
<a href="<?php print $myurl; ?>">[back]</a>
&nbsp;<span id="logout"><a href="?req=logout">[logout]</a></span>
</div>
<div id="contents">
<?php
  if ($USER['type']==$USER_ADMIN || $USER['type']==$USER_RESP) {
    $alchlog = $system->get_alignment_changelog($al);
    print print_alignment_changelog($al, $alchlog);
  } else
    print "Permission denied.";
  print "</div>\n";
} # end of showing alignment changelog
elseif (!$aid) {

	# List all alignments if none chosen
	if ($USER['type']==$USER_ADMIN) $uid=0; else $uid=$USER['id'];
	$alllink = '';
	# primary order by text name?
	$instorder = '';
	if ($torder=='asc') {
		$instorder = 't.name ASC, ';
		$tasc = '<a href="?torder=no"><img src="icons/asc.png" alt="text-nosort" title="no primary sorting by text name" class="selected"/></a>';
	} else $tasc = '<a href="?torder=asc"><img src="icons/asc.png" alt="text-asc" title="primary sort by text name, ascending"/></a>';
	if ($torder=='desc') {
		$instorder = 't.name DESC, ';
		$tdesc = '<a href="?torder=no"><img src="icons/desc.png" alt="text-nosort" title="no primary sorting by text name" class="selected"/></a>';
	} else $tdesc = '<a href="?torder=desc"><img src="icons/desc.png" alt="text-desc" title="primary sort by text name, descending"/></a>';

	$filter = array();
	if ($_SESSION['f_tname']!='')
		$filter['tname'] = $_SESSION['f_tname'];
	if ($_SESSION['f_v1name']!='')
		$filter['v1name'] = $_SESSION['f_v1name'];
	if ($_SESSION['f_v2name']!='')
		$filter['v2name'] = $_SESSION['f_v2name'];
	if ($_SESSION['f_editor']!='*')
		$filter['editor'] = $_SESSION['f_editor'];
	if ($_SESSION['f_resp']!='*')
		$filter['resp'] = $_SESSION['f_resp'];
	if ($_SESSION['f_status']!='*')
		$filter['status'] = $_SESSION['f_status'];

	if ($txt!='') {
		$alignments = $system->get_alignments($txt,$txtver,$instorder.$ALORDER[$alorder], $filter);
		$txtver = $system->txtver_by_id($txtver);
		$txtsel = " of '{$txtver['text_name']}.{$txtver['version_name']}'";
		$alllink = "<a href=\"?txt=\">[show all]</a>\n";
	} else {
		$alignments = $system->get_alignments_by_uid($uid,$instorder.$ALORDER[$alorder], $filter);
		$txtsel="";
	}

	# Print menu bar
	print "<div id=\"menubar\">\n";
  print "&nbsp;[filter:<select id=\"filter_switch\" onChange=\"filterChanged(this);\"><option value=\"0\">manual</option><option value=\"1\">auto</option></select>]";
  print "&nbsp;[show: <form action=\"$myurl\" method=\"get\" class=\"inline\"><select name=\"alpagesize\" onchange=\"this.form.submit();\">";
  foreach (array(0, 10, 20, 30, 50, 100) as $i) {
    if ($i) $lbl = $i." p/p"; else $lbl='all';
    if ($i==$alpagesize) $sel = ' selected="selected"'; else $sel ='';
    print "<option value=\"$i\"$sel>$lbl</option>";
  }
  print "</select></form>]\n";
	if ($TXTMGR_URL!='') print "<a href=\"$TXTMGR_URL\">[text manager]</a>\n";
	print "<a href=\"users.php\" title=\"user management\">[users]</a>";
	print $alllink;
	if ($PERMISSIONS['newalign'] && $txt!='') print "<a href=\"$myurl?req=create&amp;txt=$txt&amp;txtver={$txtver['id']}\" title=\"create new alignment\">[new alignment]</a>\n";
	print "&nbsp;<span id=\"logout\"><a href=\"help.php#almanager\" title=\"help\" target=\"_blank\">[help]</a><a href=\"?req=logout\">[logout]</a></span>\n";
	print "</div>\n";

	print '<div id="contents">'."\n";

	# Print warning, if there is any
	if ($WARNING!='') printf(WARNING_FORMAT,$WARNING);

	# Print list of alignments
	$TEMP_INACTIVE = '<a href="?alorder=%s"><img src="icons/%s.png" alt="%s" title="%s"/></a>';
	$TEMP_ACTIVE = '<img src="icons/%s.png" alt="%s" title="%s" class="selected"/>';
	$v1asc = sprintf($TEMP_INACTIVE,'ver1asc','asc','v1-asc','sort by left version name, ascending');
	$v1desc = sprintf($TEMP_INACTIVE,'ver1desc','desc','v1-desc','sort by left version name, descending');
	$v2asc = sprintf($TEMP_INACTIVE,'ver2asc','asc','v2-asc','sort by right version name, ascending');
	$v2desc = sprintf($TEMP_INACTIVE,'ver2desc','desc','v2-desc','sort by right version name, descending');
	$edasc = sprintf($TEMP_INACTIVE,'edasc','asc','editor-asc','sort by editor name, ascending');
	$eddesc = sprintf($TEMP_INACTIVE,'eddesc','desc','editor-desc','sort by editor name, descending');
	$respasc = sprintf($TEMP_INACTIVE,'respasc','asc','resp-asc','sort by responsible name, ascending');
	$respdesc = sprintf($TEMP_INACTIVE,'respdesc','desc','resp-desc','sort by responsible name, descending');
	$statasc = sprintf($TEMP_INACTIVE,'statasc','asc','status-asc','sort by status, ascending');
	$statdesc = sprintf($TEMP_INACTIVE,'statdesc','desc','status-desc','sort by status, descending');
	switch ($alorder) {
	case 'ver1asc':
		$v1asc = sprintf($TEMP_ACTIVE,'asc','v1-asc','sort by left version name, ascending');
		break;
	case 'ver1desc':
		$v1desc = sprintf($TEMP_ACTIVE,'desc','v1-desc','sort by left version name, descending');
		break;
	case 'ver2asc':
		$v2asc = sprintf($TEMP_ACTIVE,'asc','v2-asc','sort by right version name, ascending');
		break;
	case 'ver2desc':
		$v2desc = sprintf($TEMP_ACTIVE,'desc','v2-desc','sort by right version name, descending');
		break;
	case 'edasc':
		foreach ($alignments as $key => $row) { $sorter[$key] = $USERS[$row['editor']]['name']; }
		array_multisort($sorter, SORT_ASC, SORT_STRING, $alignments);
		$edasc = sprintf($TEMP_ACTIVE,'asc','editor-asc','sort by editor name, ascending');
		break;
	case 'eddesc':
		foreach ($alignments as $key => $row) { $sorter[$key] = $USERS[$row['editor']]['name']; }
		array_multisort($sorter, SORT_DESC, SORT_STRING, $alignments);
		$eddesc = sprintf($TEMP_ACTIVE,'desc','editor-desc','sort by editor name, descending');
		break;
	case 'respasc':
		foreach ($alignments as $key => $row) { $sorter[$key] = $USERS[$row['resp']]['name']; }
		array_multisort($sorter, SORT_ASC, SORT_STRING, $alignments);
		$respasc = sprintf($TEMP_ACTIVE,'asc','resp-asc','sort by responsible name, ascending');
		break;
	case 'respdesc':
		foreach ($alignments as $key => $row) { $sorter[$key] = $USERS[$row['resp']]['name']; }
		array_multisort($sorter, SORT_DESC, SORT_STRING, $alignments);
		$respdesc = sprintf($TEMP_ACTIVE,'desc','resp-desc','sort by responsible name, descending');
		break;
	case 'statasc':
		$statasc = sprintf($TEMP_ACTIVE,'asc','status-asc','sort by status, ascending');
		break;
	case 'statdesc':
		$statdesc = sprintf($TEMP_ACTIVE,'desc','status-desc','sort by status, descending');
		break;
	}
	//print "<h1>List of alignments{$txtsel}</h1>\n";

  $alcount = count($alignments);

	print "<h2>".$alcount." alignments{$txtsel} found:</h2>\n";

  $pager = '';

  if ($alpagesize>0 && IsSet($_REQUEST['findal'])) {
    $findal = preg_replace('/[^0-9]/','',$_REQUEST['findal']);
    $alpageoffset = 0; $cnt = 0;
    foreach ($alignments as $al) {
      if ($al['id']==$findal)
        break;
      $cnt++;
      if ($cnt>=$alpagesize) {
        $alpageoffset = $alpageoffset + $alpagesize;
        $cnt = 0;
      }
    }
  }

  if ($alpagesize>0 && $alcount>$alpagesize) {
    $alignments = array_slice($alignments, $alpageoffset, $alpagesize);
    $pager .= "<p class=\"pager\">";
    if ($alpageoffset>0) {
      $i = $alpageoffset - $alpagesize;
      if ($i<0) $i=0;
      $pager .= "<a href=\"?alpageoffset=$i\">&lt;&lt; previous</a> | ";
    }
    $pager .= "showing alignments ".($alpageoffset+1)."-".($alpageoffset+count($alignments));
    if ($alpageoffset+count($alignments)<$alcount) {
      $i = $alpageoffset+count($alignments);
      $pager .= " | <a href=\"?alpageoffset=$i\">next &gt;&gt;</a>";
    }
    $pager .= "</p>\n";
  }

	$versions = $system->list_versions($txt);

  print $pager;

	print "<table class=\"allist\">\n<tr><th>batch</th><th>{$tasc} Text {$tdesc}</th><th>{$v1asc}{$v1desc} alignment {$v2asc}{$v2desc}</th>";
	if ($USER['type']==$USER_ADMIN || $USER['type']==$USER_RESP) print "<th>{$edasc} editor {$eddesc}</th>";
	else print "<th>edit</th>";
	print "<th>{$respasc} responsible {$respdesc}</th><th>{$statasc} status {$statdesc}</th>";
	if ($PERMISSIONS['delalign']) print "<th>&nbsp;</th>";
	print "</tr>\n";?>
<tr id="filter"><td>
<img src="icons/add.png" onclick="check_all(true)" alt="[+]" title="select all" style="cursor:pointer;"/>
<img src="icons/remove.png" onclick="check_all(false)" alt="[-]" title="unselect all" style="cursor:pointer;"/>
</td>
<form action="<?php print $myurl; ?>" method="post" id="filter_form">
<td><input type="text" size="20" name="f_tname" value="<?php print htmlentities($_SESSION['f_tname'], ENT_COMPAT, 'UTF-8'); ?>" autofocus="autofocus"/></td>
<td>
<select name="f_v1name" onchange="filter(this);">
	<option value="">&lt;any&gt;</option>
<?php
		foreach ($versions as $version) {
			//if (!IsSet($version['id']) || $version['id']!=$txtver['id']) {
				print "\t\t\t<option value=\"{$version['version_name']}\"";
				if ($version['version_name']==$_SESSION['f_v1name']) print ' selected="selected"';
				print ">{$version['version_name']}</option>\n";
			//}
		}
?>
</select>
&lt;=&gt;
<select name="f_v2name" onchange="filter(this);">
	<option value="">&lt;any&gt;</option>
<?php
		foreach ($versions as $version) {
			//if (!IsSet($version['id']) || $version['id']!=$txtver['id']) {
				print "\t\t\t<option value=\"{$version['version_name']}\"";
				if ($version['version_name']==$_SESSION['f_v2name']) print ' selected="selected"';
				print ">{$version['version_name']}</option>\n";
			//}
		}
?>
</select>
</td>
<td>
<select name="f_editor" onchange="filter(this);">
	<option value="*">&lt;any&gt;</option>
<?php
reset($USERS);
foreach ($USERS as $usr) {
	if ($usr['id']==$_SESSION['f_editor']) $sel=' selected="selected"'; else $sel='';
	print "<option value=\"{$usr['id']}\"$sel>{$usr['name']}</option>";
}
?>
</select>
</td>
<td>
<select name="f_resp" onchange="filter(this);">
	<option value="*">&lt;any&gt;</option>
	<option value="">*** nobody ***</option>
<?php
print "<option value=\"\" disabled=\"disabled\">-- administrators --</option>";
foreach ($ADMINS as $usr) {
	if ($usr['id']==$_SESSION['f_resp']) $sel=' selected="selected"'; else $sel='';
	print "<option value=\"{$usr['id']}\"$sel>{$usr['name']}</option>";
}
print "<option value=\"\" disabled=\"disabled\">----- others -----</option>";
foreach ($RESPS as $usr) {
	if ($usr['id']==$_SESSION['f_resp']) $sel=' selected="selected"'; else $sel='';
	print "<option value=\"{$usr['id']}\"$sel>{$usr['name']}</option>";
}
?>
</select>
</td>
<td>
<select name="f_status" onchange="filter(this);">
	<option value="*">&lt;any&gt;</option>
<?php
foreach ($ALSTAT as $key => $value) {
	print "<option value=\"$key\"";
	if ("$key"==$_SESSION['f_status']) print ' selected="selected"';
	print ">$value</option>";
}
?>
</select>
<?php if ($PERMISSIONS['delalign']) print '</td><td>'; ?>
<input type="submit" value="Filter!"/>
</td>
</form>
</tr>
<?php
	$lasttxt='';
	# Prepare the list of supervisors (responsible)
  if ($USER['type']==$USER_ADMIN || $USER['type']==$USER_RESP) {
		$values = '<option value="" selected="selected">choose user</option>';
		$values .= '<option value="---">*** CANCEL CHANGE ***</option>';
		$values .= "<option value=\"\" disabled=\"disabled\">-- administrators --</option>";
		foreach ($ADMINS as $usr) {
			if ($usr['id']!='') 
				$values .= "<option value=\"{$usr['id']}\">{$usr['name']}</option>";
		}
		$values .= "<option value=\"\" disabled=\"disabled\">----- others -----</option>";
		foreach ($RESPS as $usr) {
			if ($usr['id']!='') 
				$values .= "<option value=\"{$usr['id']}\">{$usr['name']}</option>";
		}
		$responsibles = $values;
	}
	# Print the list
	foreach ($alignments as $al) {
		$fullname = "{$al['v1_name']} &lt;=&gt; {$al['v2_name']}";
		$trclass = 'alstat_'.str_replace(' ','_',$ALSTAT[$al['status']]);
		if ($al['text_name']!=$lasttxt) { $txtname=$al['text_name']; $trclass.=' new'; } 
		else $txtname="&nbsp;";
    if (in_array("{$al['id']}", $batch)) $checked = ' checked="checked"'; else $checked = '';
		print "<tr class=\"$trclass\"><td><input id=\"batch_{$al['id']}\" class=\"batch_chbox\" type=\"checkbox\" title=\"include into the batch\"$checked/></td>";
    print "<td><a name=\"al{$al['id']}\"></a>$txtname</td><td>";
		print "<a href=\"$myurl?aid={$al['id']}\">$fullname</a>";
		if ($USER['type']==$USER_ADMIN && $al['status']==ALSTAT_OPEN) print " <a href=\"#\" onClick=\"swapAl(this);\" id=\"swapAl_{$al['id']}\" title=\"swap versions\"><img src=\"icons/swap.png\" alt=\"[S]\" title=\"swap versions (sides)\"/></a> ";
		if ($al['status']==ALSTAT_OPEN) {
			if ($al['c_chstruct']) $chk=' checked="checked"'; else $chk='';
			if ($USER['type']!=$USER_ADMIN) $dis = ' disabled="disabled"'; else $dis = '';
			print "<input type=\"checkbox\" title=\"permission to change structure of the central text version ($C_VERSION)\" name=\"chcstruct\" onChange=\"chCstruct(this);\" id=\"chcstruct_{$al['id']}\"$chk$dis/><img src=\"icons/merge.png\" alt=\"change struct\" title=\"permission to change structure of the central text version ($C_VERSION)\"/>";
		}
		print '</td>';
		if ($al['status']==ALSTAT_OPEN && ($USER['type']==$USER_ADMIN || ($USER['type']==$USER_RESP && $al['resp']==$USER['id']))) {
			$values = ''; reset($USERS);
			foreach ($USERS as $usr) {
				if ($usr['id']==$al['editor']) $sel=' selected="selected"'; else $sel='';
				$values .= "<option value=\"{$usr['id']}\"$sel>{$usr['name']}</option>";
			}
			if ($al['chtext']) $chk=' checked="checked"'; else $chk='';
			print "<td><select name=\"user\" onChange=\"chEditor(this);\" id=\"cheditor_{$al['id']}\">$values</select>\n<input type=\"checkbox\" title=\"permission to edit the texts\" name=\"chtext\" onChange=\"chText(this);\" id=\"chtext_{$al['id']}\"$chk/><img src=\"icons/document-edit.png\" alt=\"edit\" title=\"permission to edit the texts\"/></td>";
			print "<td><span id=\"res{$al['id']}\"><a title=\"commit to another user\" onclick=\"toggle_disp(this,'res{$al['id']}','off');toggle_disp(this,'rel{$al['id']}','on')\">";
			$resp = $USERS[$al['resp']];
			print "{$resp['name']}</a></span><span class=\"invisible\" id=\"rel{$al['id']}\"><select name=\"user\" onChange=\"chrespConfirm(this);\" id=\"chresp_{$al['id']}\">$responsibles</select></span></td>";
		} else {
			if ($USER['type']==$USER_ADMIN || $USER['type']==$USER_RESP) {
				$ed = $USERS[$al['editor']]; $resp = $USERS[$al['resp']];
				print "<td>{$ed['name']}</td><td>{$resp['name']}</td>";
			} else {
				$resp = $USERS[$al['resp']];
				if ($al['chtext']) $chk=' checked="checked"'; else $chk='';
				print "<td><input type=\"checkbox\" title=\"permission to edit the texts\" name=\"chtext\" disabled=\"disabled\"$chk/><img src=\"icons/document-edit.png\" alt=\"edit\" title=\"permission to edit the texts\"/></td><td>{$resp['name']}</td>";
			}
		}
		#elseif ($USER['type']==$USER_ADMIN || $USER['type']==$USER_RESP) {
		#	$resp = $USERS[$al['resp']];
		#	print "<td>{$resp['name']}</td>";
		#}
		if ($USER['type']==$USER_ADMIN || ($USER['type']==$USER_RESP && $al['status']<=ALSTAT_FINISHED)) {
			$values = '';
			foreach ($ALSTAT as $key => $value) {
				if (($USER['type']==$USER_ADMIN || $key==ALSTAT_OPEN || $key==ALSTAT_FINISHED))
# && !($al['status']!=ALSTAT_FINISHED && $al['status']!=ALSTAT_CLOSED && $key==ALSTAT_CLOSED)) 
				{
					$values .= "<option value=\"$key\"";
					if ($key==$al['status']) $values .= ' selected="selected"';
					$values .= ">$value</option>";
				}
			}
			print "<td><input type=\"hidden\" name=\"id\" value=\"{$al['id']}\"/><select name=\"value\" onChange=\"chAlStat(this);\" id=\"chalstat_{$al['id']}\">$values</select></td>";
		} else {
			print "<td>".$ALSTAT[$al['status']]."</td>";
		}
		if ($PERMISSIONS['delalign']) print "<td><span class=\"delete\"><a href=\"$myurl?req=delete&amp;id={$al['id']}\" title=\"delete\" onClick=\"return aldeleteConfirm(this,'$fullname')\" class=\"img\"><img src=\"icons/edit-delete-shred.png\" alt=\"[DELETE]\" /></a></span></td>";
		print "</tr>\n";
		$lasttxt = $al['text_name'];
	}
	print "</table>\n";
  print $pager;

} 
# Show table with the current alignment at the current position
else {

	print '<div id="contents">'."\n";

	# Print warning, if there is any
	if ($WARNING!='') printf(WARNING_FORMAT,$WARNING);

	$v1_id = $al['ver1_id']; $v2_id = $al['ver2_id'];
	$rows = $system->get_aligned_items($txt,$aid,$offset,$limit);
	$keys = array_keys($rows); $last = array_pop($keys);
	if ($offset>1) {
		$back = $offset-$limit; if ($back<1) $back=1;
		$prev="<a href=\"$myurl?aid=$aid&amp;offset=$back\" class=\"img\" accesskey=\"p\"><img src=\"icons/go-previous-view.png\" alt=\"previous page\"></a>"; 
	} else $prev="&nbsp;";
	$pmax = ceil($al['link_count']/$limit);
	if (!$al['link_count'])
		$proc = 0;
	else
		$proc = round($offset/$al['link_count'],3)*100;
	if ($offset+$limit<=$al['link_count']) {
		$fwd = $offset+$limit; 
		$last = $fwd-1;
		$pnum = floor($offset/$limit)+1;
		$next="<a href=\"$myurl?aid=$aid&amp;offset=$fwd\" class=\"img\" accesskey=\"n\"><img src=\"icons/go-next-view.png\" alt=\"next page\"></a>";
		if (!$PERMISSIONS['readonly']) 
			$botcenter = "<a href=\"$myurl?req=confirm_status&amp;aid=$aid&amp;pos=$last&amp;roll=1\"><img src=\"icons/legalmoves.png\" alt=\"confirm and move to next page\" title=\"confirm and move to next page\"></a>";
		else $botcenter = "&nbsp;";
	} else {
		$next="&nbsp;"; $pnum = $pmax;
		if (!$PERMISSIONS['readonly']) 
			$botcenter = "<a href=\"$myurl?req=confirm_status&amp;aid=$aid&amp;pos=$last\" class=\"img\"><img src=\"icons/dialog-ok-apply.png\" alt=\"confirm all\" title=\"confirm all\"></a>";
		else $botcenter = "&nbsp;";
	}
	$center = "<a href=\"$myurl?aid=$aid&amp;offset=1\" title=\"go to start\" class=\"img\"><img src=\"icons/go-first.png\" alt=\"go to start\" /></a> ";
	$center .= "| <a href=\"$myurl?aid=0&findal=$aid#al$aid\" title=\"list of all alignments\" class=\"img\"><img src=\"icons/go-up.png\" alt=\"[LIST]\" /></a> ";
	#else $center .= "| <a href=\"$TXTMGR_URL\" title=\"$TXTMGR_TEXT\" class=\"img\"><img src=\"icons/go-up.png\" alt=\"[BACK]\" /></a> ";
	$center .= "| <a href=\"help.php#aleditor\" title=\"help\" class=\"img\" target=\"_blank\"><img src=\"icons/help-contents.png\" alt=\"help\" /></a> ";
	if ($PERMISSIONS['export']) $center .= "| <a href=\"#\" onclick=\"toggle_block(this,'exportbar','on');\" title=\"export\" class=\"img\"><img src=\"icons/document-save.png\" alt=\"[EXPORT]\" /></a> ";
	if ($PERMISSIONS['realign'] && !$PERMISSIONS['readonly']) $center .= "| <a href=\"$myurl?req=realign&amp;aid=$aid\" title=\"re-align\" class=\"img\"><img src=\"icons/automatic.png\" alt=\"[REALIGN]\" /></a> ";
	if ($_SESSION['changelog']) $class=' active'; else $class='';
	$center .= "| <a href=\"$myurl?req=toggle_permchangelog&amp;aid=$aid\" class=\"$class\" title=\"display all changes\"><img src=\"icons/changelog.png\" alt=\"[ch]\" class=\"chlb\"/></a>";
	if (!$PERMISSIONS['readonly']) {
		if ($hide) $center .= "| <a href=\"$myurl?req=toggle_vis&amp;aid=$aid\" title=\"show controls\" class=\"img\"><img src=\"icons/layer-visible-on.png\" alt=\"[C]\" /></a> ";
		else $center .= "| <a href=\"$myurl?req=toggle_vis&amp;aid=$aid\" title=\"hide controls\" class=\"img\"><img src=\"icons/layer-visible-off.png\" alt=\"[C]\" /></a> ";
	}
	if ($_COOKIE['InterText_highlight']) $class=' class="non11"'; else $class='';
	$center .= "| <a href=\"$myurl?req=toggle_highlight&amp;aid=$aid\" title=\"toggle highlighting of non-1:1 alignments\"$class>non-1:1</a> ";
if (!$PERMISSIONS['readonly']) {
		$mode_options ='';
		foreach ($MODES as $num => $desc) {
			if ($num==$mode) $sel=' selected="selected"'; else $sel='';
			$mode_options .= "<option value=\"$num\"$sel>$desc</option>";
		}
		$center .= "| Mode: <form class=\"inline\" method=\"post\"><input type=\"hidden\" name=\"aid\" value=\"$aid\"/><select name=\"mode\" onChange=\"this.form.submit();\">$mode_options</select></form> ";
	}
	$limit_options ='';
	foreach ($LIMITS as $lim) {
		if ($lim==$limit) $sel=' selected="selected"'; else $sel='';
		$limit_options .= "<option value=\"$lim\"$sel>$lim p/p</option>";
	}
	$center .= "| <form class=\"inline\" method=\"post\"><input type=\"hidden\" name=\"aid\" value=\"$aid\"/><select name=\"limit\" onChange=\"this.form.submit();\">$limit_options</select></form> ";
	$center .= "| <a href=\"$myrurl?req=prevmark\" title=\"go to previous mark\" class=\"img\"><img src=\"icons/go-prev.png\" alt=\"&lt;\"/></a><img src=\"icons/mark.png\"/><a href=\"$myrurl?req=nextmark\" title=\"go to next mark\" class=\"img\"><img src=\"icons/go-next.png\" alt=\"&gt;\"/></a> ";
	if ($_SESSION['searchbar']) $class=' active'; else $class='';
	$center .= "| <a href=\"$myurl?req=toggle_searchbar\" title=\"search\" class=\"img$class\"><img src=\"icons/search.png\" alt=\"search\"/></a> ";
	$center .= "| <a href=\"javascript:gotoDialog($aid)\" title=\"jump to position\" class=\"img\"><img src=\"icons/go-jump.png\" alt=\"goto\"/></a> ";
	$center .= "| <a href=\"$myurl?req=status&amp;aid=$aid&amp;val=".STATUS_AUTOMATIC."\" title=\"go to unchecked items\" class=\"img\"><img src=\"icons/to-check.png\" alt=\"skip to unchecked\" /></a> ";
	$lastpage=$al['link_count']-$limit+1; if ($lastpage<1) $lastpage=1;
  if (($USER['type']==$USER_ADMIN || $USER['type']==$USER_RESP) && $al['alchangelog'])
    $center .= "| <a href=\"$myurl?aid=$aid&amp;req=alchangelog\" title=\"list changes to the alignment\" class=\"img\"><img src=\"icons/journal.png\" alt=\"[j]\" /></a> ";
	$center .= "| <a href=\"$myurl?aid=$aid&amp;offset=$lastpage\" title=\"go to end\" class=\"img\"><img src=\"icons/go-last.png\" alt=\"go to end\" /></a>";
	# Search bar
	if ($_SESSION['searchbar']) {
		$center .= "\n<div class=\"searchbar\" id=\"searchbar\">\n";
		$center .= "<form class=\"inline\" method=\"post\">\n<input type=\"hidden\" name=\"req\" value=\"search\"/>\n";
		$center .= "Find: ";
		$selected = ' selected="selected"'; 
		$subsel=''; $bsubsel=''; $resel=''; $bresel=''; $esegsel=''; $v0sel=''; $v1sel=''; $v2sel=''; $ftsel=''; $cftsel=''; $ssdis='';
		$elidsel=''; $non121sel=''; $largesegssel=''; $changesel=''; $seldis='';
		switch ($_SESSION['search_type']) {
		case 'substr': $subsel = $selected; break;
		case 'bsubstr': $bsubsel = $selected; break;
		case 'regexp': $resel = $selected; break;
		case 'bregexp': $bresel = $selected; break;
		case 'ftext': $ftsel = $selected; break;
		case 'cftext': $cftsel = $selected; break;
		case 'emptyseg': $esegsel = $selected; $ssdis=' disabled="disabled"'; break;
		case 'elid': $elidsel = $selected; break;
		case 'non-one2one': $non121sel = $selected; $ssdis=' disabled="disabled"'; $seldis=' disabled="disabled"'; break;
    case 'largesegs': $largesegssel = $selected; $ssdis=' disabled="disabled"'; $seldis=' disabled="disabled"'; break;
		case 'change': $changesel = $selected; $ssdis=' disabled="disabled"'; break;
		}
		$center .= "<select name=\"type\" onChange=\"stypeChange(this)\"><option value=\"substr\"$subsel>substring (insensitive)</option><option value=\"bsubstr\"$bsubsel>substring (exact)</option>";
    if (!$DISABLE_FULLTEXT)
      $center .= "<option value=\"ftext\"$ftsel>fulltext (all words, insensitive)</option><option value=\"cftext\"$cftsel>fulltext (custom, insensitive)</option>";
    $center .= "<option value=\"regexp\"$resel>regular exp. (insensitive in ascii)</option><option value=\"bregexp\"$bresel>regular exp. (exact)</option><option value=\"elid\"$elidsel>element ID</option><option value=\"emptyseg\"$esegsel>empty segment</option><option value=\"non-one2one\"$non121sel>non-1:1 segment</option><option value=\"largesegs\"$largesegssel>large segments (>2:2)</option><option value=\"change\"$changesel>changed/edited elements</option></select>";
		switch ($_SESSION['search_version']) {
		case '1': $v1sel = $selected; break;
		case '2': $v2sel = $selected; break;
		case '0': $v0sel = $selected; break;
		}
		$center .= "<input type=\"text\" size=\"30\" name=\"string\" value=\"".htmlspecialchars($_SESSION['search_string'])."\" id=\"searchstring\"$ssdis/>";
		$center .= " on <select name=\"version\" id=\"searchversion\"$seldis><option value=\"0\"$v0sel>both sides</option><option value=\"1\"$v1sel>left side</option><option value=\"2\"$v2sel>right side</option></select>";
		$center .= "<input type=\"submit\" src=\"icons/go-next.png\" name=\"dir\" value=\"up\" alt=\"&lt;\" title=\"find next\" id=\"upbutton\" class=\"sbutton right\"/>\n";
		$center .= "<input type=\"submit\" src=\"icons/go-prev.png\" name=\"dir\" value=\"down\" alt=\"&gt;\" title=\"find previous\" id=\"downbutton\" class=\"sbutton left\"/>\n";
		$center .= "</form>";
		$center .= "</div>";
	}
	# Export bar
	$format .= "<select name=\"format\" onChange=\"this.form.submit();this.selectedIndex=0;\"><option value=\"\">choose format</option><option value=\"xml\">original (plain IDs)</option><option value=\"xml:ic\">original (IC long IDs)</option><option value=\"xml:fn\">original (filename long IDs)</option><option value=\"corresp\">corresp attr.</option><option value=\"segs\">ParaConc</option></select>";
	$aformat .= "<select name=\"format\" onChange=\"this.form.submit();this.selectedIndex=0;\"><option value=\"\">choose format</option><option value=\"xml\">TEI XML (plain IDs)</option><option value=\"xml:ic\">TEI XML (IC long IDs)</option><option value=\"xml:fn\">TEI XML (filename long IDs)</option></select>";
	$center .= "\n<div class=\"exportbar\" id=\"exportbar\">\n";
	$center .= "<form class=\"inline\" method=\"post\">Left: <input type=\"hidden\" name=\"aid\" value=\"$aid\"/><input type=\"hidden\" name=\"req\" value=\"export_text\"/><input type=\"hidden\" name=\"ver\" value=\"1\"/>$format</form>\n";
	$center .= "<form class=\"inline\" method=\"post\">Alignment: <input type=\"hidden\" name=\"aid\" value=\"$aid\"/><input type=\"hidden\" name=\"req\" value=\"export_alignment\"/>$aformat</form>\n";
	$center .= "<form class=\"inline\" method=\"post\">Right: <input type=\"hidden\" name=\"aid\" value=\"$aid\"/><input type=\"hidden\" name=\"req\" value=\"export_text\"/><input type=\"hidden\" name=\"ver\" value=\"2\"/>$format</form>\n";
	$center .= "</div>\n";
	$statbar = "<div id=\"statbar\">page $pnum of $pmax ($proc%); total links: {$al['link_count']}<br/>text: <i>{$al['text_name']}</i> left: <i>{$al['ver1_name']}</i> right: <i>{$al['ver2_name']}</i></div>";
	# Print pager
	print "<table class=\"pager\"><tr>";
	print "<td class=\"prev\">$prev</td><td class=\"center\">$center</td><td class=\"next\">$next</td>\n";
	print "</tr></table>\n\n";
	print "<table class=\"alignment\">\n";
	# Print table of alignments
	for ($i=$offset; $i<=$last; $i++) {
		$position = $i; 
		if (IsSet($rows[$i])) $row = $rows[$i]; else $row=array();
		if ($position==$_SESSION['position']) $class="act";
		else 
			if ($position % 2) $class="even"; else $class="odd";
		if ($_COOKIE['InterText_highlight'] AND (count($row[$v1_id])!=1 OR count($row[$v2_id])!=1)) $class.= "-non11";
		print "<tr class=\"$class\"><td class=\"position\"><a name=\"$position\"></a><a href=\"$myurl?req=setpos&amp;aid=$aid&amp;pos=$position\">$position</a></td>\n";
		$firstcolumn = TRUE;
		list($stat1,$mark1,$h1) = halfrow($position,$row,$v1_id);
		list($stat2,$mark2,$h2) = halfrow($position,$row,$v2_id);
			if ($stat1>$stat2) $stat=$stat1; else $stat=$stat2;
		if ($mark1>$mark2) $mark=$mark1; else $mark=$mark2;
		if (!$mark)
			if ($PERMISSIONS['readonly'] && $USER['id']!=$al['resp'])
				print "<td class=\"mark\"><img src=\"icons/nomark.png\" alt='0'/></td>";
			else 
				print "<td class=\"mark\"><a href=\"$myurl?req=mark&amp;aid=$aid&amp;pos=$position&amp;mark=1\" class=\"img\" title=\"mark\"><img src=\"icons/nomark.png\" alt='0'/></a></td>";
		else
			if ($PERMISSIONS['readonly'] && $USER['id']!=$al['resp'])
				print "<td class=\"mark\"><img src=\"icons/mark.png\" alt='1'/></td>";
			else 
				print "<td class=\"mark\"><a href=\"$myurl?req=mark&amp;aid=$aid&amp;pos=$position&amp;mark=0\" class=\"img\" title=\"unmark\"><img src=\"icons/mark.png\" alt='1'/></a></td>";
		print $h1;
		if (!$PERMISSIONS['readonly']) print_center_control($position);
		else print "<td class=\"ccontrols\">&nbsp;</td>\n";
		print $h2;
		switch ($stat) {
			case STATUS_MANUAL: $img = "dialog-ok-apply.png"; $status="manual"; $chstat=STATUS_PLAIN; break;
			case STATUS_AUTOMATIC: $img = "automatic.png"; $status="automatic alignment"; $chstat=STATUS_MANUAL; break;
			case STATUS_PLAIN: $img = "status_unknown.png"; $status="plain link"; $chstat=STATUS_MANUAL; break;
		}
		if (!$PERMISSIONS['readonly']) 
			$status_ind = "<a href=\"$myurl?req=chstat&amp;aid=$aid&amp;pos=$position&amp;val=$chstat\" class=\"img\" title=\"change status; current:$status\"><img src=\"icons/$img\" alt=\"$status\"></a>";
		else $status_ind = "<img src=\"icons/$img\" alt=\"$status\">";
		print "<td class=\"status\">$status_ind</td></tr>\n";
	}
	print "</table>\n";
	# Print pager
	print "<table class=\"pager\"><tr>";
	print "<td class=\"prev\">$prev</td><td class=\"center\">$botcenter</td><td class=\"next\">$next</td>\n";
	print "</tr></table>\n";
	print $statbar;
}

?>
</div>
</body>
<script type="text/javascript">
$( ".editable" ).eip( "aligner.php?req=update_element&amp;aid=<?php print $aid; ?>&amp;txt=<?php print $txt; ?>", { 
		form_type: "textarea",
		edit_event: "dblclick",
		after_save: function( self ) {
				document.getElementById('chlb_'+self.id).style.display='block';
				for( var i = 0; i < 2; i++ ) {
					$( self ).fadeOut( "fast" );
					$( self ).fadeIn( "fast" );
				}
			},
		on_error: function( msg ) {
				if ( msg == "reload" ) {
				window.location.reload();
				} 
				else {
				alert( "Error: " + msg );
				}
			}
	} );

 	function keyPress(e) {
		if (e.keyCode==119) {
<?php if ($offset+$limit<=$al['link_count']) print "window.location=\"$myurl?aid=$aid&offset=$fwd\";"; ?>
		} else if (e.keyCode==118) {
<?php if ($offset>1) print "window.location=\"$myurl?aid=$aid&offset=$back\";"; ?>
		}
	}

  function swapAl(caller)
  {
    var aid = caller.id.slice(7);
    var aids = get_checked();
    var bb = document.getElementById('batch_'+aid);
    if (!bb.checked) {
      window.location="<?php print $myurl; ?>?req=swap&id="+aid+"&batch="+aids.join(',')
      return;
    }
    if (aids.length==1) {
      window.location="<?php print $myurl; ?>?req=swap&id="+aid+"&batch="+aids.join(',')
      return;
    }
    if (!confirm("Do you want to swap all alignments in the batch?")) {
      window.location="<?php print $myurl; ?>?req=swap&id="+aid+"&batch="+aids.join(',')
      return;
    }
    window.location="<?php print $myurl; ?>?req=swap&id="+aids.join(',')+"&batch="+aids.join(',')
  }

  function chCstruct(caller)
  {
    var aid = caller.id.slice(10);
    var value = 0;
    if (caller.checked) 
      value = 1;
    var aids = get_checked();
    var bb = document.getElementById('batch_'+aid);
    if (!bb.checked) {
      window.location="<?php print $myurl; ?>?req=chcstruct&id="+aid+"&batch="+aids.join(',')+"&chcstruct="+value;
      return;
    }
    if (aids.length==1) {
      window.location="<?php print $myurl; ?>?req=chcstruct&id="+aid+"&batch="+aids.join(',')+"&chcstruct="+value;
      return;
    }
    if (!confirm("Do you want to change teh permission for all alignments in the batch?")) {
      window.location="<?php print $myurl; ?>?req=chcstruct&id="+aid+"&batch="+aids.join(',')+"&chcstruct="+value;
      return;
    }
    window.location="<?php print $myurl; ?>?req=chcstruct&id="+aids.join(',')+"&batch="+aids.join(',')+"&chcstruct="+value;
  }

  function chText(caller)
  {
    var aid = caller.id.slice(7);
    var value = 0;
    if (caller.checked) 
      value = 1;
    var aids = get_checked();
    var bb = document.getElementById('batch_'+aid);
    if (!bb.checked) {
      window.location="<?php print $myurl; ?>?req=chtext&id="+aid+"&batch="+aids.join(',')+"&chtext="+value;
      return;
    }
    if (aids.length==1) {
      window.location="<?php print $myurl; ?>?req=chtext&id="+aid+"&batch="+aids.join(',')+"&chtext="+value;
      return;
    }
    if (!confirm("Do you want to change the permission for all alignments in the batch?")) {
      window.location="<?php print $myurl; ?>?req=chtext&id="+aid+"&batch="+aids.join(',')+"&chtext="+value;
      return;
    }
    window.location="<?php print $myurl; ?>?req=chtext&id="+aids.join(',')+"&batch="+aids.join(',')+"&chtext="+value;
  }

  function chEditor(caller)
  {
    var aid = caller.id.slice(9);
    var value = caller.value;
    var aids = get_checked();
    var bb = document.getElementById('batch_'+aid);
    if (!bb.checked) {
      window.location="<?php print $myurl; ?>?req=cheditor&id="+aid+"&batch="+aids.join(',')+"&user="+value;
      return;
    }
    if (aids.length==1) {
      window.location="<?php print $myurl; ?>?req=cheditor&id="+aid+"&batch="+aids.join(',')+"&user="+value;
      return;
    }
    if (!confirm("Do you want to change the editor for all alignments in the batch?")) {
      window.location="<?php print $myurl; ?>?req=cheditor&id="+aid+"&batch="+aids.join(',')+"&user="+value;
      return;
    }
    window.location="<?php print $myurl; ?>?req=cheditor&id="+aids.join(',')+"&batch="+aids.join(',')+"&user="+value;
  }

  function chrespConfirm(caller)
  {
    var aid = caller.id.slice(7);
    var vname = caller.options[caller.selectedIndex].text;
    var value = caller.value;
    if (value=='---')
      return;
    if (confirm("Are you sure you want to commit the alignment to '"+vname+"' ?\n (WARNING: only the new user or administrator can take this action back!)")) {
      var aids = get_checked();
      var bb = document.getElementById('batch_'+aid);
      if (!bb.checked) {
        window.location="<?php print $myurl; ?>?req=chresp&id="+aid+"&batch="+aids.join(',')+"&user="+value;
        return;
      }
      if (aids.length==1) {
        window.location="<?php print $myurl; ?>?req=chresp&id="+aid+"&batch="+aids.join(',')+"&user="+value;
        return;
      }
      if (!confirm("Do you want to commit all alignments in the batch?")) {
        window.location="<?php print $myurl; ?>?req=chresp&id="+aid+"&batch="+aids.join(',')+"&user="+value;
        return;
      }
      window.location="<?php print $myurl; ?>?req=chresp&id="+aids.join(',')+"&batch="+aids.join(',')+"&user="+value;
    }
  }

  function chAlStat(caller) {
    var aid = caller.id.slice(9);
    var bb = document.getElementById('batch_'+aid);
    var aids = get_checked();
    if (!bb.checked) {
      window.location="<?php print $myurl; ?>?req=chalstat&id="+aid+"&batch="+aids.join(',')+"&value="+caller.value;
      //caller.form.submit();
      return;
    }
    if (aids.length==1) {
      window.location="<?php print $myurl; ?>?req=chalstat&id="+aid+"&batch="+aids.join(',')+"&value="+caller.value;
      return;
    }
    if (!confirm("Do you want to change status of all alignments in the batch?")) {
      window.location="<?php print $myurl; ?>?req=chalstat&id="+aid+"&batch="+aids.join(',')+"&value="+caller.value;
      //caller.form.submit();
      return;
    }
    window.location="<?php print $myurl; ?>?req=chalstat&id="+aids.join(',')+"&batch="+aids.join(',')+"&value="+caller.value;
  }

  function get_checked() {
    var bblist = document.getElementsByClassName('batch_chbox');
    var aids = new Array();
    for (var i=0; i<bblist.length; i++) {
      var bbi = bblist[i];
      var bbid = bbi.id.slice(6);
      if (bbi.checked) {
        aids.push(bbid);
      }
    }
    return aids;
  }

  function check_all(value) {
    var bblist = document.getElementsByClassName('batch_chbox');
    for (var i=0; i<bblist.length; i++) {
      var bbi = bblist[i];
      bbi.checked = value;
    }
  }

  function filter(caller) {
    var sw = document.getElementById("filter_switch");
    if (sw.value==1)
      caller.form.submit();
  }

function setCookie(c_name, value, exdays) {
    var exdate = new Date();
    exdate.setDate(exdate.getDate() + exdays);
    var c_value = escape(value) + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString());
    document.cookie = c_name + "=" + c_value;
}

function getCookie(c_name) {
    var i, x, y, ARRcookies = document.cookie.split(";");
    for (i = 0; i < ARRcookies.length; i++) {
        x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
        y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
        x = x.replace(/^\s+|\s+$/g, "");
        if (x == c_name) {
            return unescape(y);
        }
    }
    return null;
}

function filterChanged(caller) {
    var val = caller.value;
    setCookie("InterText_autofilter", val, 30);
}

window.onload = function(e) {
  var fstate = getCookie("InterText_autofilter");
  if (fstate !== null)
    document.getElementById("filter_switch").value = fstate;
};

</script>
</html>
