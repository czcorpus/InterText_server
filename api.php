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

#setlocale(LC_COLLATE,'cs_CZ.utf8');

if ($_SERVER['HTTPS']!='') $prot='https://'; else $prot='http://';
$myurl = $prot.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']; # 'api.php';
$edurl = str_replace('api.php', 'aligner.php', $myurl);
$CLI_MODE=true;

require 'init.php';
require 'lib_intertext.php';
$system = new InterText;

$OK = 0;
$ERR_UNAUTH_USER = 1;
$ERR_UNKNOWN_CMD = 2;
$ERR_PERM_DENIED = 3;
$ERR_NOT_FOUND   = 4;
$ERR_TEXT_CHANGED= 5;
$ERR_OTHER       = 65535;

$ERRORLIST = array(
	$OK => 'OK',
	$ERR_UNAUTH_USER => 'Unauthorized user.',
	$ERR_UNKNOWN_CMD => 'Unknown request.',
	$ERR_PERM_DENIED => 'Permission denied.',
	$ERR_NOT_FOUND   => 'Not found.',
	$ERR_TEXT_CHANGED=> 'Text has been changed. Re-sync!',
	$ERR_OTHER       => 'Request failed.'
);

function reply($code, $body='', $message='') {
	global $ERRORLIST, $req;
	$ts = date('c');
	if ($message=='') $message = $ERRORLIST[$code];
	//$message .= " ($req)";
	header ("Content-Type: text/xml"); 
	print "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<reply>
	<result>
		<number>$code</number>
		<text>$message</text>
	</result>
	<body ts=\"$ts\">
$body\t</body>
</reply>\n";
	exit;
}

function trans_usertype($type) {
	global $USER_ADMIN, $USER_RESP, $USER_EDITOR;
	if ($type==$USER_ADMIN)
		return 'admin';
	elseif ($type==$USER_RESP)
		return 'resp';
	elseif ($type==$USER_EDITOR)
		return 'editor';
	else
		return 'unknown';
}

$req = $_REQUEST['req'];
if ($USER['username']=='' && $req!='logout' && $req!='version') reply($ERR_UNAUTH_USER);

//error_log("INTERTEXT API debug: req $req");

# Process requests
switch ($req) {
case 'logout':
	reply($OK);
	break;
case 'login':
	reply($OK, "\t\t<userid>{$USER['id']}</userid>\n");
	break;
case 'version':
	reply($OK, "\t\t<version>2</version>\n");
	break;
case 'al_list':
	$body="\t<alignments>\n";
	if ($USER['type']==$USER_ADMIN) $uid=0; else $uid=$USER['id'];
	$alignments = $system->get_alignments_by_uid($uid,'t.name ASC, v1.version_name ASC, v2.version_name ASC');
	foreach ($alignments as $al) {
		$al['v1_lchng'] = $system->get_document_lastchange($al['text_id'], $al['v1_id'], $USER['id']);
		//$al['v1_lchng'] = str_replace(' ', 'T', $al['v1_lchng']);
    if ( $al['v1_lchng']!='') $al['v1_lchng'] = date('c',strtotime($al['v1_lchng']));
		$al['v2_lchng'] = $system->get_document_lastchange($al['text_id'], $al['v2_id'], $USER['id']);
		//$al['v2_lchng'] = str_replace(' ', 'T', $al['v2_lchng']);
    if ( $al['v2_lchng']!='') $al['v2_lchng'] = date('c',strtotime($al['v2_lchng']));
		$body.= "\t\t<al id=\"{$al['id']}\">
			<text>{$al['text_name']}</text>
			<v1 changed=\"{$al['v1_lchng']}\">{$al['v1_name']}</v1>
			<v2 changed=\"{$al['v2_lchng']}\">{$al['v2_name']}</v2>
			<responsible>{$al['resp']}</responsible>
			<editor>{$al['editor']}</editor>
			<remote>{$al['remote_user']}</remote>
			<perm_central_chstruct>{$al['c_chstruct']}</perm_central_chstruct>
			<perm_chtext>{$al['chtext']}</perm_chtext>
			<status>{$ALSTAT[$al['status']]}</status>
		</al>\n";
	}
	$body.="\t</alignments>\n";
	reply($OK,$body);
	break;
case 'al_update_attrs':
	$aid = preg_replace('/[^0-9]/','', $_REQUEST['id']);
	$astatus = preg_replace('/[^a-z ]/','', $_REQUEST['status']);
	$aruser = preg_replace('/[^0-9]/','', $_REQUEST['r_user']);
	$aeditor = preg_replace('/[^0-9]/','', $_REQUEST['editor']);
	$aresp = preg_replace('/[^0-9]/','', $_REQUEST['resp']);
	$apedit = preg_replace('/[^0-1]/','', $_REQUEST['pedit']);
	$apcchstr = preg_replace('/[^0-1]/','', $_REQUEST['pcchstr']);
	if ($aruser==0) $aruser='';
	if ($aeditor==0) $aeditor='';
	if ($aresp==0) $aresp='';
	$stat = array_search($astatus, $ALSTAT);
	if ($aid=='') reply($ERR_NOT_FOUND);
	$al = $system->alignment_info($aid);
	if ($al===FALSE) reply($ERR_NOT_FOUND);
	if (!($USER['type']==$USER_ADMIN || ($USER['type']==$USER_RESP && $al['resp']==$USER['id'])))
		reply($ERR_PERM_DENIED);
	if ($USER['type']==$USER_RESP && ($stat!=ALSTAT_OPEN && $stat!=ALSTAT_FINISHED))
		reply($ERR_PERM_DENIED);
	/*if ($al['status']!=ALSTAT_OPEN && $stat!=ALSTAT_OPEN)
		reply($ERR_PERM_DENIED);*/
	if ($USER['type']==$USER_RESP && $apcchstr!=$al['c_chstruct'])
		reply($ERR_PERM_DENIED);

	if ($stat!=$al['status'] && !$system->alignment_chstat($aid,$stat))
		reply($ERR_OTHER, '', 'Problem when changing status. '.$_ERROR);
	if ($stat==ALSTAT_REMOTE) {
		if ($aruser!=$al['remote_user'] && !$system->alignment_chruser($aid,$aruser))
			reply($ERR_OTHER, '', 'Cannot change remote user. '.$_ERROR);
	}
	if ($aeditor!=$al['editor'] && !$system->alignment_cheditor($aid,$aeditor))
		reply($ERR_OTHER, '', 'Cannot change editor. '.$_ERROR);
	if ($aresp!=$al['resp'] && !$system->alignment_chresp($aid,$aresp))
		reply($ERR_OTHER, '', 'Cannot change resp. '.$_ERROR);
	if ($apedit!=$al['chtext'] && !$system->alignment_chtext($aid,$apedit))
		reply($ERR_OTHER, '', 'Cannot change edit permission. '.$_ERROR);
	if ($apcchstr!=$al['c_chstruct'] && !$system->alignment_chcstruct($aid,$apcchstr))
		reply($ERR_OTHER, '', 'Cannot change cchstruct permission. '.$_ERROR);
	reply($OK);
	break;
case 'al_download':
	$aid = preg_replace('/[^0-9]/','', $_REQUEST['id']);
	if ($aid=='') {
		reply($ERR_NOT_FOUND);
	}
	$al = $system->alignment_info($aid);
	if ($al===false) {
		reply($ERR_NOT_FOUND);
	}
	$PERM = set_alignment_settings($al);
	if (!$PERM['export']) {
		reply($ERR_PERM_DENIED);
	}
	$txt = $al['text_id'];
	$v1_id = $al['ver1_id']; $v2_id = $al['ver2_id'];
	$al['v1_lchng'] = $system->get_document_lastchange($al['text_id'], $v1_id);
	//$al['v1_lchng'] = str_replace(' ', 'T', $al['v1_lchng']);
  if ( $al['v1_lchng']!='') $al['v1_lchng'] = date('c',strtotime($al['v1_lchng']));
	$data_doc2 = $system->export_document($txt,$v2_id);
	$al['v2_lchng'] = $system->get_document_lastchange($al['text_id'], $v2_id);
	//$al['v2_lchng'] = str_replace(' ', 'T', $al['v2_lchng']);
  if ( $al['v2_lchng']!='') $al['v2_lchng'] = date('c',strtotime($al['v2_lchng']));
	$data_doc1 = $system->export_document($txt,$v1_id);
	if (!$data_doc1 OR !$data_doc2) {
		reply($ERR_OTHER, '', 'Request failed - '.$_ERROR);
	}
	$data_alignment = $system->export_alignment($aid);
	//$data_alignment = base64_encode(gzcompress($data_alignment));
	//$data_doc1 = base64_encode(gzcompress($data_doc1));
	//$data_doc2 = base64_encode(gzcompress($data_doc2));
	$data_alignment = base64_encode($data_alignment);
	$data_doc1 = base64_encode($data_doc1);
	$data_doc2 = base64_encode($data_doc2);
	$body = "<info>
	<id>{$al['id']}</id>
	<text>{$al['text_name']}</text>
	<v1>
		<name>{$al['ver1_name']}</name>
		<ctime>{$al['v1_lchng']}</ctime>
		<perm_chtext>".$PERM[$v1_id]['chtext']."</perm_chtext>
		<perm_chstruct>".$PERM[$v1_id]['chstruct']."</perm_chstruct>
	</v1>
	<v2>
		<name>{$al['ver2_name']}</name>
		<ctime>{$al['v2_lchng']}</ctime>
		<perm_chtext>".$PERM[$v2_id]['chtext']."</perm_chtext>
		<perm_chstruct>".$PERM[$v2_id]['chstruct']."</perm_chstruct>
	</v2>
</info>
<alignment>$data_alignment</alignment>
<doc1>$data_doc1</doc1>
<doc2>$data_doc2</doc2>
";
	reply($OK,$body);
	break;
case 'al_lock':
	$aid = preg_replace('/[^0-9]/','', $_REQUEST['id']);
	if ($aid=='') {
		reply($ERR_NOT_FOUND);
	}
	$al = $system->alignment_info($aid);
	if ($al===false) {
		reply($ERR_NOT_FOUND);
	}
	$PERM = set_alignment_settings($al);
	if ($PERM['readonly']) {
		reply($ERR_PERM_DENIED);
	}
	$system->alignment_remote_lock($aid,$USER['id']);
	reply($OK);
	break;
case 'al_unlock':
	$aid = preg_replace('/[^0-9]/','', $_REQUEST['id']);
	$stat = preg_replace('/[^a-z ]/','', $_REQUEST['stat']);
	if ($aid=='') {
		reply($ERR_NOT_FOUND);
	}
	$al = $system->alignment_info($aid);
	if ($al===false) {
		reply($ERR_NOT_FOUND);
	}
	$PERM = set_alignment_settings($al);
	if (!($al['status']==ALSTAT_REMOTE && $al['remote_user']==$USER['id'])) {
		reply($ERR_PERM_DENIED);
	}
	if (!in_array($stat, $ALSTAT))
		reply($ERR_NOT_FOUND);
	else
		$stat = array_search($stat, $ALSTAT);
	if (!($USER['type']==$USER_ADMIN || (($USER['type']==$USER_RESP && ($stat==ALSTAT_OPEN || $stat==ALSTAT_FINISHED))) || $stat==ALSTAT_OPEN)) {
		reply($ERR_PERM_DENIED);
	}
	$system->alignment_remote_unlock($aid,$USER['id'],$stat);
	reply($OK);
	break;
case 'doc_lastchange':
	$text = $_REQUEST['text'];
	$vname = $_REQUEST['ver'];
	$txtid = $system->text_id_by_name($text);
	if ($txtid===FALSE)
		reply($ERR_NOT_FOUND);
	$vinfo = $system->txtver_info($txtid,$vname);
	if ($vinfo===FALSE)
		reply($ERR_NOT_FOUND);
	$lchng = $system->get_document_lastchange($txtid, $vinfo['id']);
  if ( $lchng!='') $lchng = date('c',strtotime($lchng));
	$body = "\t\t<ctime>{$lchng}</ctime>\n";
	reply($OK,$body);
	break;
case 'doc_changelist':
  $text = $_REQUEST['text'];
  $vname = $_REQUEST['ver'];
	if (IsSet($_REQUEST['aid']))
		$aid = preg_replace('/[^0-9]/','', $_REQUEST['aid']);
	else if (IsSet($_REQUEST['id'])) // backwards compatibility with the oldest pre-release (remove me later!)
		$aid = preg_replace('/[^0-9]/','', $_REQUEST['id']);
	else if (IsSet($_REQUEST['ver2'])) { // protocol version 2 support without aid
    $ver2 = $_REQUEST['ver2'];
    if (!($al=$system->get_alignment_by_name($text,$vname,$ver2)))
      $al=$system->get_alignment_by_name($text,$ver2,$vname);
    if (!$exa) 
      $aid = false;
    else
      $aid = $exa['id'];
  } else
		$aid = false;

	$since = preg_replace('/[^0-9\-T: +Z]/','', strtr($_REQUEST['since'],' ','+'));
	//$since = preg_replace('/\+.+$/','',strtr($since,'T',' '));
  $since = date('c',strtotime($since));
	if ($aid) {
		$al = $system->alignment_info($aid);
		$text = $al['text_name'];
	}
	$txtid = $system->text_id_by_name($text);
	if ($txtid===FALSE)
		reply($ERR_NOT_FOUND);
	$vinfo = $system->txtver_info($txtid,$vname);
	if ($vinfo===FALSE)
		reply($ERR_NOT_FOUND);

	//$chl = $system->get_changes_since($aid,$ver,$since, true, $USER['id']);*/
	$chl = $system->get_changes_since($vinfo,$since, true, $USER['id']);
	$ts = date('c');
	if ($chl===false)
		reply($ERR_NOT_FOUND);

	$lchng = $system->get_document_lastchange($txtid, $vinfo['id']);
	//$lchng = str_replace(' ', 'T', $lchng);
  if ( $lchng!='') $lchng = date('c',strtotime($lchng));

	$data = "<info>\n\t<ctime>{$lchng}</ctime>\n";
	if ($aid) {
		$PERM = set_alignment_settings($al);
		$vid = $vinfo['id'];
		$data.="\t<al_elements>{$vinfo['text_elements']}</al_elements>
	<perm_chtext>".$PERM[$vid]['chtext']."</perm_chtext>
	<perm_chstruct>".$PERM[$vid]['chstruct']."</perm_chstruct>\n";
	}
	$data.="</info>\n<changelist text=\"$text\" version=\"$vname\" ts=\"$ts\">\n";
	foreach ($chl as $ch) {
		$parbr = '';
		if ($ch['parbr']!='')
			$parbr = " parbr=\"{$ch['parbr']}\"";
		$data.="\t<change n=\"{$ch['n']}\" repl=\"{$ch['repl']}\"$parbr>{$ch['contents']}</change>\n";
	}
	$data .= "</changelist>\n";
	reply($OK, $data);
	break;
case 'get_elements':
	//$aid = preg_replace('/[^0-9]/','', $_REQUEST['id']);
	$text = $_REQUEST['text'];
	$ver = $_REQUEST['ver'];
	$items = $_REQUEST['items'];
	$data = "<elements>\n";
	$list = explode(',',$items);
	foreach ($list as $num) {
		$ret = $system->get_textelement_by_linkcount($text,$ver,$num);
		if ($ret===false || count($ret)!=1)
			reply($ERR_NOT_FOUND);
		//if (count($ret)==1) {
			$ret = $ret[0];
			$parbr = '';
			if ($ret['position']==1)
				$parbr = " parbr=\"o\"";
			$data .= "\t<element n=\"$num\"$parbr>{$ret['contents']}</element>\n";
		//}
	}
	$data .= "</elements>\n";
	reply($OK, $data);
	break;
case 'canupload':
	if ($USER['type']>$UPLOAD_MAXUSERLEVEL_CLIENT)
		reply($ERR_PERM_DENIED);
	else {
		// test conflicts here
		reply($OK);
	}
	break;
case 'canmerge':
	$lastsync = preg_replace('/[^0-9\-T: +Z]/','', strtr($_REQUEST['lastsync'],' ','+'));
  if ($lastsync!='') $lastsync = date('c',strtotime($lastsync));
 	$exaid = preg_replace('/[^\-0-9]/','', $_REQUEST['exaid']);
	$tname = $_REQUEST['text'];
	$ver = $_REQUEST['ver'];
  $ver2 = $_REQUEST['ver2'];
	$link = preg_replace('/[^0-9]/','', $_REQUEST['l']);
	$count = preg_replace('/[^0-9]/','', $_REQUEST['n']);
	$txt = $system->text_id_by_name($tname);
	if ($txt===FALSE) {
		reply($ERR_NOT_FOUND);}
	$vinfo = $system->txtver_info($txt, $ver);
	if ($vinfo===FALSE)
		reply($ERR_NOT_FOUND);
	$chl = $system->get_changes_since($vinfo,$lastsync, true, $USER['id']);
	if (count($chl)>0) {
		reply($OK,"\t\t<result>-1</result>\n\t\t<message>Text has been changed.</message>\n");
		break;
	}
	$ret = $system->get_textelement_by_linkcount($tname ,$ver,$link,$lastsync);
	if (!$ret || count($ret)!=1) {
		reply($ERR_NOT_FOUND);
	}
	$ret = $ret[0];
  // since protocol version 2, exaid not required anymore! (for faster response without listing alignments)
  if (!IsSet($_REQUEST['exaid'])) {
    if (!($exa=$system->get_alignment_by_name($tname,$ver,$ver2)))
      $exa=$system->get_alignment_by_name($tname,$ver2,$ver);
    if (!$exa) 
      reply($ERR_NOT_FOUND);
    $exaid = $exa['id'];
  }
	if ($system->merge_possible($txt, $exaid, $ret['eid'], $count)) {
		$response = "\t\t<result>1</result>\n\t\t<message></message>\n";
		reply($OK, $response);
	} else {
		$_ERROR = preg_replace("/alignment '([^']*)' \(([0-9]*)\) at positions ([0-9]*)\/([0-9]*)/","<a href=\"{$edurl}?aid=\${2}&amp;offset=\${3}\" target=\"_blank\">alignment '\${1}' at positions \${3}-\${4}</a>",$_ERROR);
		$response = "\t\t<result>0</result>\n\t\t<message>{$_ERROR}</message>\n";
		reply($OK, $response);
	}
	break;
case "update":
 	$aid = preg_replace('/[^\-0-9]/','', $_REQUEST['aid']);
	if ($aid > 0) { 
		$al = $system->alignment_info($aid);
		if ($al===false) {
			reply($ERR_NOT_FOUND,'','1');
		}
		$PERM = set_alignment_settings($al);
		if (!($al['status']==ALSTAT_REMOTE && $al['remote_user']==$USER['id'])) {
			reply($ERR_PERM_DENIED);
		}
	} else {
		if ($UPLOAD_MAXUSERLEVEL_CLIENT > $USER['type'])
			reply($ERR_PERM_DENIED);
	}
	$tname = $_REQUEST['text'];
	$txt = $system->text_id_by_name($tname);
	if ($txt===FALSE)
		reply($ERR_NOT_FOUND,'','2');
	//$txt = $al['text_id'];
	$ver = $_REQUEST['ver'];
	$text = stripslashes(rawurldecode($_REQUEST["newtext"]));
	$text = preg_replace('/\r/','',$text);
	$link = preg_replace('/[^0-9]/','', $_REQUEST['l']);
	//$lastsync = preg_replace('/\+.+$/','',strtr(preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync']),'T',' '));
  //$lastsync = preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync']);
  //$lastsync = date('c',strtotime($lastsync));
  $lastsync = preg_replace('/[^0-9\-T: +Z]/','', strtr($_REQUEST['lastsync'],' ','+'));
  if ($lastsync!='') $lastsync = date('c',strtotime($lastsync));
	$vinfo = $system->txtver_info($txt,$ver);
	if ($vinfo===FALSE)
		reply($ERR_NOT_FOUND,'','3');
	$vid = $vinfo['id'];
	$chl = $system->get_changes_since($vinfo,$lastsync, true, $USER['id']);
	if (count($chl)>0) {
		reply($ERR_TEXT_CHANGED,'',"INTERTEXT API debug: ".count($chl)." changes in {$vinfo['id']} since $lastsync {$_REQUEST['lastsync']}.");
		break;
	}
	$ret = $system->get_textelement_by_linkcount($tname,$ver,$link);
	if (!$ret || count($ret)!=1) {
		reply($ERR_NOT_FOUND,'','4');
	}
	$ret = $ret[0];
	$id = $ret['eid'];
	//$vid = $system->get_ver_by_id($txt,$id);
	#print json_encode(array('is_error'=>true,'error_text'=>"Failure: -$vid-","html"=>$orig)); return;
	if (preg_match('/\n\n/',$text)) {
		if ($aid>0 && !$PERM[$vid]['chstruct']) {
			reply($ERR_PERM_DENIED);
		}
		if ($system->split_element($txt,$id,explode("\n\n",$text),$USER['id'], true))
			reply($OK);
		else
			reply($ERR_OTHER, '', $_ERROR);
		return;
	}
	if((!($aid>0) || $PERM[$vid]['chtext']) AND $system->update_element_text($txt,$id,$text, $USER['id'],'E','NULL', true))
		reply($OK);
	else {
			if ($aid>0 && !$PERM[$vid]['chtext'])
				reply($ERR_PERM_DENIED);
			else
				reply($ERR_OTHER, '', $_ERROR);
	}
	break;
case "merge":
	$aid = preg_replace('/[^\-0-9]/','', $_REQUEST['aid']);
	$tname = $_REQUEST['text'];
	$txt = $system->text_id_by_name($tname);
	if ($txt===FALSE)
		reply($ERR_NOT_FOUND);
	$ver = $_REQUEST['ver'];
	$link = preg_replace('/[^0-9]/','', $_REQUEST['l']);
// 	if ($aid=='') {
// 		reply($ERR_NOT_FOUND);
// 	}
	if ($aid>0) {
		$al = $system->alignment_info($aid);
		if ($al===false) {
			reply($ERR_NOT_FOUND);
		}
	}
	$vinfo = $system->txtver_info($txt,$ver);
	if ($vinfo===FALSE)
		reply($ERR_NOT_FOUND);
	$vid = $vinfo['id'];
	//$lastsync = preg_replace('/\+.+$/','',strtr(preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync']),'T',' '));
  //$lastsync = preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync']);
  //$lastsync = date('c',strtotime($lastsync));
  $lastsync = preg_replace('/[^0-9\-T: +Z]/','', strtr($_REQUEST['lastsync'],' ','+'));
  if ($lastsync!='') $lastsync = date('c',strtotime($lastsync));
	$chl = $system->get_changes_since($vinfo,$lastsync, true, $USER['id']);
	if (count($chl)>0) {
		reply($ERR_TEXT_CHANGED);
		break;
	}
	if ($aid>0) {
		$PERM = set_alignment_settings($al);
		if (!($al['status']==ALSTAT_REMOTE && $al['remote_user']==$USER['id'])) {
			reply($ERR_PERM_DENIED);
		}
		if (!$PERM[$vid]['chstruct']) {
			reply($ERR_PERM_DENIED);
		}
	} else {
		if ($UPLOAD_MAXUSERLEVEL_CLIENT > $USER['type'])
			reply($ERR_PERM_DENIED);
	}
	//$txt = $al['text_id'];
	$ret = $system->get_textelement_by_linkcount($tname,$ver,$link);
	if (!$ret || count($ret)!=1) {
		reply($ERR_NOT_FOUND);
	}
	$ret = $ret[0];
	$id = $ret['eid'];
	//$vid = $system->get_ver_by_id($txt,$id);
	if ($system->merge_elements($txt,$id,$USER['id'], true, $aid)) {
		reply($OK);
	} else {
		$_ERROR = preg_replace("/alignment '([^']*)' \(([0-9]*)\) at positions ([0-9]*)\/([0-9]*)/","<a href=\"{$edurl}?aid=\${2}&amp;offset=\${3}\" target=\"_blank\">alignment '\${1}' at positions \${3}-\${4}</a>",$_ERROR);
		reply($ERR_OTHER, '', $_ERROR);
	}
	break;
case "delpar":
	$aid = preg_replace('/[^\-0-9]/','', $_REQUEST['aid']);
	$tname = $_REQUEST['text'];
	$txt = $system->text_id_by_name($tname);
	if ($txt===FALSE)
		reply($ERR_NOT_FOUND);
	$ver = $_REQUEST['ver'];
	$vinfo = $system->txtver_info($txt,$ver);
	if ($vinfo===FALSE)
		reply($ERR_NOT_FOUND);
	$vid = $vinfo['id'];
	$link = preg_replace('/[^0-9]/','', $_REQUEST['l']);
// 	if ($aid=='') {
// 		reply($ERR_NOT_FOUND);
// 	}
	if ($aid>0) {
		$al = $system->alignment_info($aid);
		if ($al===false) {
			reply($ERR_NOT_FOUND);
		}
		$PERM = set_alignment_settings($al);
		if (!($al['status']==ALSTAT_REMOTE && $al['remote_user']==$USER['id'])) {
			reply($ERR_PERM_DENIED);
		}
		if (!$PERM[$vid]['chstruct']) {
			reply($ERR_PERM_DENIED);
		}
	} else {
		if ($UPLOAD_MAXUSERLEVEL_CLIENT > $USER['type'])
			reply($ERR_PERM_DENIED);
	}
	//$lastsync = preg_replace('/\+.+$/','',strtr(preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync']),'T',' '));
  //$lastsync = preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync']);
  //$lastsync = date('c',strtotime($lastsync));
  $lastsync = preg_replace('/[^0-9\-T: +Z]/','', strtr($_REQUEST['lastsync'],' ','+'));
  if ($lastsync!='') $lastsync = date('c',strtotime($lastsync));
	$chl = $system->get_changes_since($vinfo,$lastsync, true, $USER['id']);
	if (count($chl)>0) {
		reply($ERR_TEXT_CHANGED);
		break;
	}
	//$txt = $al['text_id'];
	$ret = $system->get_textelement_by_linkcount($tname,$ver,$link);
	if (!$ret || count($ret)!=1) {
		reply($ERR_NOT_FOUND);
	}
	$ret = $ret[0];
	$id = $ret['eid'];
	//$vid = $system->get_ver_by_id($txt,$id);
	if ($system->merge_parents($txt,$id,$USER['id'], true)) {
		reply($OK);
	} else {
		reply($ERR_OTHER, '', $_ERROR);
	}
	break;
case "newpar":
	$aid = preg_replace('/[^\-0-9]/','', $_REQUEST['aid']);
	$tname = $_REQUEST['text'];
	$txt = $system->text_id_by_name($tname);
	if ($txt===FALSE)
		reply($ERR_NOT_FOUND);
	$ver = $_REQUEST['ver'];
	$vinfo = $system->txtver_info($txt,$ver);
	if ($vinfo===FALSE)
		reply($ERR_NOT_FOUND);
	$vid = $vinfo['id'];
	$link = preg_replace('/[^0-9]/','', $_REQUEST['l']);
// 	if ($aid=='') {
// 		reply($ERR_NOT_FOUND);
// 	}
	if ($aid>0) {
		$al = $system->alignment_info($aid);
		if ($al===false) {
			reply($ERR_NOT_FOUND);
		}
		if (!($al['status']==ALSTAT_REMOTE && $al['remote_user']==$USER['id'])) {
			reply($ERR_PERM_DENIED);
		}
		$PERM = set_alignment_settings($al);
		if (!$PERM[$vid]['chstruct']) {
			reply($ERR_PERM_DENIED);
		}
	} else {
		if ($UPLOAD_MAXUSERLEVEL_CLIENT > $USER['type'])
			reply($ERR_PERM_DENIED);
	}
	//$lastsync = preg_replace('/\+.+$/','',strtr(preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync']),'T',' '));
  //$lastsync = preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync']);
  //$lastsync = date('c',strtotime($lastsync));
  $lastsync = preg_replace('/[^0-9\-T: +Z]/','', strtr($_REQUEST['lastsync'],' ','+'));
  if ($lastsync!='') $lastsync = date('c',strtotime($lastsync));
	$chl = $system->get_changes_since($vinfo,$lastsync, true, $USER['id']);
	if (count($chl)>0) {
		reply($ERR_TEXT_CHANGED);
		break;
	}
	//$txt = $al['text_id'];
	$ret = $system->get_textelement_by_linkcount($tname,$ver,$link);
	if (!$ret || count($ret)!=1) {
		reply($ERR_NOT_FOUND);
	}
	$ret = $ret[0];
	$id = $ret['eid'];
	//$vid = $system->get_ver_by_id($txt,$id);
	if ($system->split_parent($txt,$id,$USER['id'], true)) {
		reply($OK);
	} else {
		reply($ERR_OTHER, '', $_ERROR);
	}
	break;
case "close_update":
	$tname = $_REQUEST['text'];
	$txt = $system->text_id_by_name($tname);
	if ($txt===FALSE)
		reply($ERR_NOT_FOUND);
	$ver = $_REQUEST['ver'];
	$vinfo = $system->txtver_info($txt,$ver);
	if ($vinfo===FALSE)
		reply($ERR_NOT_FOUND);
	$vid = $vinfo['id'];
	if ($system->close_updates($txt, $vid, $USER['id']))
		reply($OK);
	else
		reply($ERR_NOT_FOUND);
	break;
case "upload_doc":
	$tname = $_REQUEST['text'];
	$vname = $_REQUEST['ver'];
	$elements = $_REQUEST['elements'];
	$filename = $_FILES['data']['tmp_name'];
	if ($filename=='')
		reply($ERR_OTHER, '', 'No document uploaded. Please check your server for max. upload size limit in PHP.');
	if ($UPLOAD_MAXUSERLEVEL_CLIENT > $USER['type'])
		reply($ERR_PERM_DENIED);
	$res = $system->import_document($tname,$vname,$filename,$elements);
	if (!$res)
		reply($ERR_OTHER, '', $_ERROR);
	reply($OK);
	break;
case "upload_alignment":
	$tname = $_REQUEST['text'];
	$v1name = $_REQUEST['ver1'];
	$v2name = $_REQUEST['ver2'];
	/*if (IsSet($_REQUEST['data'])) {
		$data = $_REQUEST['data'];
	} else*/
	if ($_FILES['data']['tmp_name']!='') {
		$data = $_FILES['data']['tmp_name'];
	} else
		reply($ERR_OTHER, '', 'No alignment uploaded. Please check your server for max. upload size limit in PHP.');
	if ($UPLOAD_MAXUSERLEVEL_CLIENT > $USER['type'])
		reply($ERR_PERM_DENIED);
	$txt = $system->text_id_by_name($tname);
	if ($txt===FALSE)
		reply($ERR_NOT_FOUND);
	$v1 = $system->txtver_info($txt,$v1name);
	if ($v1===FALSE)
		reply($ERR_NOT_FOUND);
	$v2 = $system->txtver_info($txt,$v2name);
	if ($v2===FALSE)
		reply($ERR_NOT_FOUND);
	$edit = $DEFAULT_EDIT_PERMISSION;
	$method = $DEFAULT_METHOD;
	$profile = $DEFAULT_PROFILE;
	$editor = $USER['id'];
	$resp = $USER['id'];
	$aid = $system->import_alignment($data, false, STATUS_MANUAL, false, false, $method, $profile, $resp,$editor);
	if (!$aid)
		reply($ERR_OTHER, '', $_ERROR);
	$system->alignment_remote_lock($aid,$USER['id']);
	reply($OK);
	break;
case "update_alignment":
	$aid = preg_replace('/[^0-9]/','', $_REQUEST['id']);
	//$lastsync1 = preg_replace('/\+.+$/','',strtr(preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync1']),'T',' '));
	//$lastsync2 = preg_replace('/\+.+$/','',strtr(preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync2']),'T',' '));
  //$lastsync1 = preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync1']);
  //$lastsync1 = date('c',strtotime($lastsync1));
  //$lastsync2 = preg_replace('/[^0-9\-T: \+]/','', $_REQUEST['lastsync2']);
  //$lastsync2 = date('c',strtotime($lastsync2));
  $lastsync1 = preg_replace('/[^0-9\-T: +Z]/','', strtr($_REQUEST['lastsync1'],' ','+'));
  if ($lastsync1!='') $lastsync1 = date('c',strtotime($lastsync1));
  $lastsync2 = preg_replace('/[^0-9\-T: +Z]/','', strtr($_REQUEST['lastsync2'],' ','+'));
  if ($lastsync2!='') $lastsync2 = date('c',strtotime($lastsync2));
	/*if (IsSet($_REQUEST['data'])) {
		$data = $_REQUEST['data'];
	} else*/
	if ($_FILES['data']['tmp_name']!='') {
		$data = $_FILES['data']['tmp_name'];
	} else
		reply($ERR_OTHER, '', 'No alignment uploaded.');
	$al = $system->alignment_info($aid);
	if ($al===false) {
		reply($ERR_NOT_FOUND);
	}
	$PERM = set_alignment_settings($al);
	if (!($al['status']==ALSTAT_REMOTE && $al['remote_user']==$USER['id'])) {
		reply($ERR_PERM_DENIED);
	}
	$txt = $al['text_id']; $v1_id = $al['ver1_id']; $v2_id = $al['ver2_id'];
	$v1info = $system->txtver_by_id($v1_id);
	if ($v1info===FALSE)
		reply($ERR_NOT_FOUND);
	$chl = $system->get_changes_since($v1info,$lastsync1, true, $USER['id']);
	if (count($chl)>0) {
    //error_log("INTERTEXT API debug: ".count($chl)." changes in {$v1info['id']} since $lastsync1.");
		reply($ERR_TEXT_CHANGED);
		break;
	}
	$v2info = $system->txtver_by_id($v2_id);
	if ($v2info===FALSE)
		reply($ERR_NOT_FOUND);
	$chl = $system->get_changes_since($v2info,$lastsync2, true, $USER['id']);
	if (count($chl)>0) {
    //error_log("INTERTEXT API debug: ".count($chl)." changes in {$v2info['id']} since $lastsync2.");
		reply($ERR_TEXT_CHANGED);
  }
  // done by import_alignment
	//if (!$al['v1uniq_ids']) { $system->update_eids($txt,$v1_id); $al = $system->alignment_info($aid); }
	//if (!$al['v2uniq_ids']) { $system->update_eids($txt,$v2_id); $al = $system->alignment_info($aid); }
  // enforce renumbering in case it has been unique but non-standard: we import standard numbered alignment!
  $system->update_eids($txt,$v1_id);
  $system->update_eids($txt,$v2_id);
	$backup = $system->export_alignment($aid);
	$system->delete_alignment_from_pos($aid, 0);
	if ($system->import_alignment($data, $aid, STATUS_MANUAL, FALSE))
		reply($OK);
	else {
		$system->import_alignment($backup, $aid, STATUS_MANUAL, FALSE);
		reply($ERR_OTHER, '', $_ERROR);
	}
	break;
case 'users_list':
	$body = "\t\t<users>\n";
	foreach ($USERS as $usr) {
		if ($usr['id']=='') continue;
		$body.="\t\t\t<user id=\"{$usr['id']}\" type=\"".trans_usertype($usr['type'])."\">{$usr['name']}</user>\n";
	}
	$body .= "\t\t</users>\n";
	reply($OK,$body);
	break;
case 'doc_list':
	if ($USER['type']>$DOWNLOAD_TXTVER_MAXUSERLEVEL)
		reply($ERR_PERM_DENIED);
	$list = $system->list_texts();
	$body = "\t\t<docs>\n";
	foreach ($list as $doc) {
		if (!preg_match("/^$DOWNLOAD_TXTVER_FILTER$/",$doc['version_name'])) continue;
		$body.="\t\t\t<doc text_id=\"{$doc['text_id']}\" id=\"{$doc['version_id']}\">
				<text>{$doc['text_name']}</text>
				<ver>{$doc['version_name']}</ver>
			</doc>\n";
	}
	$body .= "\t\t</docs>\n";
	reply($OK,$body);
	break;
case 'doc_download':
	$tname = $_REQUEST['text'];
	$vname = $_REQUEST['ver'];
	if ($DOWNLOAD_TXTVER_MAXUSERLEVEL>$USER['type'])
		reply($ERR_PERM_DENIED);
	if (!preg_match("/^$DOWNLOAD_TXTVER_FILTER$/",$ver))
		reply($ERR_PERM_DENIED);
	$txt = $system->text_id_by_name($tname);
	$ver = $system->txtver_info($txt, $vname);
	$data_doc = $system->export_document($txt,$ver['id']);

	$lchng = $system->get_document_lastchange($txt, $ver['id']);
	//$lchng = str_replace(' ', 'T', $lchng);
  if ( $lchng!='') $lchng = date('c',strtotime($lchng));

	$chtext = $DEFAULT_EDIT_PERMISSION;
	$chstruct = $DEFAULT_EDIT_PERMISSION;
	if (preg_match("/^$C_VERSION$/",$vname))
		$chstruct = $DEFAULT_C_CHSTRUCT_PERMISSION;

	if (!$data_doc) {
		reply($ERR_OTHER, '', 'Request failed - '.$_ERROR);
	}
	$data_doc = base64_encode($data_doc);

	$body = "<info>
	<al_elements>{$ver['text_elements']}</al_elements>
	<ctime>{$lchng}</ctime>
	<perm_chtext>{$chtext}</perm_chtext>
	<perm_chstruct>{$chstruct}</perm_chstruct>
</info>
<doc>$data_doc</doc>
";
	reply($OK,$body);
	break;
case 'doc_canupload':
	$tname = $_REQUEST['text'];
	$vname = $_REQUEST['ver'];
	if ($UPLOAD_MAXUSERLEVEL_CLIENT > $USER['type'])
		reply($ERR_PERM_DENIED);
	$txt = $system->text_id_by_name($tname);
	if ($txt && $system->txtver_info($txt, $vname))
		reply($ERR_OTHER);
	else
		reply($OK);
	break;
case 'al_canupload':
	$tname = $_REQUEST['text'];
	$vname1 = $_REQUEST['ver1'];
	$vname2 = $_REQUEST['ver2'];
	if ($UPLOAD_MAXUSERLEVEL_CLIENT > $USER['type'])
		reply($ERR_PERM_DENIED);
	$txt = $system->text_id_by_name($tname);
	if ($txt && ($v1=$system->txtver_info($txt, $vname1)) && ($v2=$system->txtver_info($txt, $vname2))) {
		if ($system->alignment_exists($txt, $v1['id'], $v2['id']))
			reply($ERR_OTHER);
		else
			reply($OK);
	} else
		reply($OK);
	break;
default:
	reply($ERR_UNKNOWN_CMD);
}

?>