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

ini_set('error_reporting', 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED');

$ALSTAT = array('0'=>'open', '1'=>'finished', '2'=>'closed', '3'=>'blocked', '4'=>'remote editor');
$STATUS = array('1'=>'man','2'=>'auto','3'=>'plain');
$CHANGE = array('D'=>'deleted (in a merger)', 'X'=>'deleted and merged across parental border', 'M'=>'merged', 'S'=>'split', 'E'=>'edited', 'I'=>'inserted (by splitting)');
$CHANGE_ALIGN = array('S'=>'shift first element up', 'P'=>'pop last element down', 'U'=>'move text version up', 'D'=>'move text version down', 'M'=>'merge segments (move both versions up)', 'I'=>'insert segment (move both versions down)');

define('STATUS_MANUAL',1);
define('STATUS_AUTOMATIC',2);
define('STATUS_PLAIN',3);
define('ALSTAT_OPEN',0);
define('ALSTAT_FINISHED',1);
define('ALSTAT_CLOSED',2);
define('ALSTAT_BLOCKED',3);
define('ALSTAT_REMOTE',4);

if (!IsSet($LOG_ALIGN_CHANGES)) $LOG_ALIGN_CHANGES = false;
if (!IsSet($FORCE_SIMPLE_NUMBERING)) $FORCE_SIMPLE_NUMBERING = false;
if (!IsSet($DISABLE_FULLTEXT)) $DISABLE_FULLTEXT = false;

class InterText
{
	var $DB;
	var $auto_status_update = TRUE;

# Initialization (constructor)
	function InterText () {
		global $DB_SERVER,$DB_USER,$DB_PASSWORD,$DB_DATABASE,$DISABLE_FULLTEXT;
		if (!$this->DB = @mysqli_connect($DB_SERVER,$DB_USER,$DB_PASSWORD,$DB_DATABASE)) 
			$this->_fail("Cannot connect to database: ".mysqli_error($this->DB));
		if (!mysqli_set_charset($this->DB,"utf8"))
			$this->_fail("Cannot set encoding: ".mysqli_error($this->DB));
		$this->autor = array();
		if (!$DISABLE_FULLTEXT) {
      if (!$dbresult = mysqli_query($this->DB,"SELECT VERSION()>5.5"))
        die("Cannot access database: ".mysqli_error($this->DB));
      $ret = mysqli_fetch_row($dbresult);
      if (!$ret[0]) {
        die("Failure: You need MySQL version >= 5.6 in order to use fulltext search in InterText >= 2.2 ! Either upgrade your MySQL or disable fulltext in config.php by adding '\$DISABLE_FULLTEXT = true;';\n");
      }
    }
	}

# Fatal failure handling
	function _fail ($message) {
		die("Failure: $message\n");
	}

# New failure handling
	function _failure ($message) {
		global $_ERROR;
		$_ERROR = "$message\n";
		mysqli_query($this->DB,"ROLLBACK");
		#if (!$result = mysqli_query($this->DB,"ROLLBACK"))
			#$_ERROR .= "Rollback failed as well: ".mysqli_error($this->DB)."\nThe database is corrupt now. Send this message to your administrator immediately and do not try to make any more changes!";
	}

# Import new XML from file
	function import_document($docname,$docversion,$filename,$text_elements,$debug=FALSE,$origfilename='',$validate=false) {
		global $_ERROR;
		global $txt_elements; # OK. this is just to avoid giving one more argument to parse_xml, a dirty lazy trick...
		if ($origfilename=='') $origfilename = $filename;
		$origfilename = preg_replace('/.*?([^\/]*)$/','\1',$origfilename);
		if (preg_match('/^\s*$/',$docname)) {
			$_ERROR = "Error: Invalid document name '$docname'.";
			return FALSE;
		}
		if (preg_match('/^\s*$/',$docversion)) {
			$_ERROR = "Error: Invalid version name '$docversion'.";
			return FALSE;
		}
		$txtid = $this->register_text($docname);
    if ($txtid===FALSE) {
      return FALSE;
    }
		if ($this->txtver_info($txtid,$docversion)) {
			$_ERROR = "Error: Document '$docname' version '$docversion' already exists in database.";
			return FALSE;
		}
		$txt_elements = explode(' ',$text_elements);
		//$_ERROR = "Error: Invalid XML document."; # just a default in case of failure when parsing
		$_ERROR = '';
		$xml = new XMLReader(); 
		libxml_use_internal_errors(TRUE);
		#if (!$xmldata=file_get_contents($filename)) {
		#	$_ERROR = "Error: Error opening input file.";
		#	return FALSE;
		#}
		#$xmldata = html_entity_decode($xmldata,ENT_QUOTES,'UTF-8'); # Latin1 only :-(
		#if (!$xml->xml($xmldata)) {
		if ($validate)
			$libxmlflags = LIBXML_NOENT|LIBXML_DTDLOAD|LIBXML_DTDVALID;
		else
			$libxmlflags = LIBXML_NOENT|LIBXML_DTDLOAD;
		if (!$xml->open($filename,NULL,$libxmlflags)) {
		#if (!$xml->open($filename)) {
			$_ERROR = "Error: Error reading XML.\n";
			return FALSE;
		}
		if ($validate) {
			$xml->setParserProperty(XMLReader::VALIDATE, true);
			if (!$xml->isValid()) {
				$_ERROR .= "Initial validation error.\n";
				$arErrors = libxml_get_errors();
				$xml_errors = "";
				foreach ($arErrors AS $xmlError) $xml_errors .= $xmlError->message;
				if ($xml_errors != "") {
					$_ERROR .= "XML errors:\n".$xml_errors."\n";
				}
				return FALSE;
			}
		}
    if (!$dbresult = mysqli_query($this->DB,"START TRANSACTION")) {
      $this->_failure("Cannot start transaction: ".mysqli_error($this->DB));
      return FALSE;
    }
		$root_id = $this->_insert_element($txtid,0,0,'NULL',1,'__DOCUMENT__',$docname.'.'.$docversion);
		if ($root_id===FALSE)
      return FALSE;
		$verid = $this->insert_version($txtid,$docversion,$root_id,$text_elements,$origfilename);
    if ($verid===FALSE)
      return FALSE;
		if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txtid}_elements` SET txtver_id='$verid' WHERE (id='$root_id')")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
			return FALSE;
    }
		$stat = FALSE;
		# XMLreader's error reporting is strange...let's just let the user choose...
		if (!$debug) $ers = error_reporting(0);
    $_ERROR = '';
		if ($xml->read()) {
			$stat = $this->parse_xml($xml,$root_id,$txtid,$verid,0,$debug,$validate); # and uses $txt_elements as a global variable/argument as well
		} else $_ERROR .= "Error: Initial XML read failed.\n";
		if (!$debug) $ers = error_reporting($ers);
		$xml->close();
		if (!$stat) {
			$arErrors = libxml_get_errors();
			$xml_errors = "";
			foreach ($arErrors AS $xmlError) $xml_errors .= $xmlError->message;
			if ($xml_errors != "") {
				$_ERROR .= "XML errors:\n".$xml_errors."\n";
			}
			$this->delete_document($txtid,$verid);
			return FALSE;
		} else {
      if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
        $this->_failure("Cannot commit transaction: ".mysqli_error($this->DB));
        return FALSE;
      }
		}
		$_ERROR = '';
		# Check existence of IDs on text elements
		foreach($txt_elements as $element) { $ealignables[]="e.element_name='$element'"; $ialignables[]="i.element_name='$element'"; }
		$ealignable = join(' OR ',$ealignables); $ialignable = join(' OR ',$ialignables);
		$query="SELECT e.id FROM `{$txtid}_elements` e WHERE txtver_id=$verid AND ($ealignable) AND TRIM(element_id)=''";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot check database: ".mysqli_error($this->DB)." Query: $query\n");
		if (!mysqli_num_rows($dbresult)) {
			# Check uniqueness of IDs on text elements
			$query="SELECT e.id FROM `{$txtid}_elements` e, `{$txtid}_elements` i WHERE e.txtver_id=$verid AND i.txtver_id=$verid AND ($ealignable) AND ($ialignable) AND e.element_id=i.element_id AND e.id!=i.id";
			if (!$dbresult = mysqli_query($this->DB,$query))
				$this->_fail("Cannot check database: ".mysqli_error($this->DB)." Query: $query\n");
			if (!mysqli_num_rows($dbresult)) $this->set_uniq_ids($txtid,$verid,true);
		}
		return TRUE;
	}

	# Update document from file
	function update_document($docname,$docversion,$filename,$userid=0,$verbose=true,$validate=false) {
		global $_ERROR;
		$_ERROR = '';
		$txtid = $this->text_id_by_name($docname);
		if (!$txtid) {
			$_ERROR = "Error: Document '$docname' not found in database.";
			return FALSE;
		}
		$ver = $this->txtver_info($txtid,$docversion);
		if (!$ver) {
			$_ERROR = "Error: Version '$docversion' not found in database.";
			return FALSE;
		}
		$txt_elements = explode(' ',$ver['text_elements']);
		$_ERROR = "Error: Invalid XML document."; # just a default in case of failure when parsing
		$xml = new XMLReader();
		libxml_use_internal_errors(TRUE);
		if ($validate)
			$libxmlflags = LIBXML_NOENT|LIBXML_DTDLOAD|LIBXML_DTDVALID;
		else
			$libxmlflags = LIBXML_NOENT;
		if (!$xml->open($filename,NULL,$validate)) {
			$_ERROR = "Error: Error reading XML.";
			return FALSE;
		}
		if ($validate) {
			$xml->setParserProperty(XMLReader::VALIDATE, true);
			if (!$xml->isValid()) {
				$_ERROR .= "Initial validation error.\n";
				$arErrors = libxml_get_errors();
				$xml_errors = "";
				foreach ($arErrors AS $xmlError) $xml_errors .= $xmlError->message;
				if ($xml_errors != "") {
					$_ERROR .= "XML errors:\n".$xml_errors."\n";
				}
				return FALSE;
			}
		}
		$stat = FALSE;
		# XMLreader's error reporting is strange...let's just let the user choose...
		if (!$verbose) $ers = error_reporting(0);
		if ($xml->read()) {
			$stat = $this->parse_xml_update($xml,$txtid,$ver['id'],$txt_elements,$userid,$verbose,$validate);
		} else $_ERROR = "Error: Invalid XML file!";
		if (!$stat) {
			$arErrors = libxml_get_errors();
			$xml_errors = "";
			foreach ($arErrors AS $xmlError) $xml_errors .= $xmlError->message;
			if ($xml_errors != "") {
				$_ERROR .= "XML errors:\n".$xml_errors."\n";
			}
		}
		if (!$verbose) $ers = error_reporting($ers);
		$xml->close();
		return $stat;
	}

# Export document
	function export_document($txt,$id,$cor_aid=0,$format='xml',$maxcor_stat=STATUS_AUTOMATIC) {
		global $_ERROR;
		$segs = false;
		list($format,$idformat) = explode(':',$format,2);
		if ($format=='segs') $segs=true;
		elseif ($format=='xml' || $format=='orig') $cor_aid=0;
		elseif ($format!='corresp') {
			$_ERROR = "Error: Unknown format.";
			return FALSE;
		}
		$txtver = $this->txtver_by_id($id);
		if (!$txtver['uniq_ids']) { $this->update_eids($txt,$txtver['id']); $txtver = $this->txtver_by_id($id); }
		if (!$txtver['uniq_ids']) {
			$_ERROR = "ERROR: Renumbering IDs failed!";
			return FALSE;
		}
		$corr=array(); $seg=array();
		if ($cor_aid) {
			$al = $this->alignment_info($cor_aid);
			if (!$al) {
				$_ERROR = "Error: Alignment for correspondence attributes not found.";
				return FALSE;
			}
	   	if (!$al['v1uniq_ids']) { $this->update_eids($txt,$al['ver1_id']); $al = $this->alignment_info($cor_aid); }
     	if (!$al['v2uniq_ids']) { $this->update_eids($txt,$al['ver1_id']); $al = $this->alignment_info($cor_aid); }
     	if (!$al['v1uniq_ids'] OR !$al['v2uniq_ids']) {
     		$_ERROR = "ERROR: Renumbering IDs failed!";
     		return FALSE;
     	}
		}
		if ($segs) {
			$query = "SELECT e.id as id, l.position as pos FROM `{$txt}_elements` e, `{$txt}_links` l WHERE e.txtver_id=$id AND l.alignment_id=$cor_aid AND l.element_id=e.id ORDER BY e.txt_position";
			if (!$dbresult = mysqli_query($this->DB,$query))
				$this->_fail("Cannot access database: ".mysqli_error($this->DB));
			while ($ret = mysqli_fetch_assoc($dbresult)) { $seg[$ret['id']] = $ret['pos']; $lastid=$ret['id']; }
			$res = $this->alignment_info($cor_aid);
			$maxpos = $res['link_count'];
			$cor_aid = 0;
		} elseif ($cor_aid) {
			$query = "SELECT e.id, GROUP_CONCAT(c.element_id ORDER BY c.txt_position SEPARATOR ' ') as corresp, l1.position as pos FROM `{$txt}_elements` e, `{$txt}_links` l1, `{$txt}_links` l2, `{$txt}_elements` c WHERE e.txtver_id=$id AND l1.element_id=e.id AND l1.alignment_id=$cor_aid AND l2.alignment_id=$cor_aid AND l2.version_id!=$id AND l1.status <= $maxcor_stat AND l2.position=l1.position AND c.id=l2.element_id GROUP BY e.id ORDER BY e.txt_position";
			if (!$dbresult = mysqli_query($this->DB,$query))
				$this->_fail("Cannot access database: ".mysqli_error($this->DB));
			while ($ret = mysqli_fetch_assoc($dbresult)) $corr[$ret['id']] = $ret['corresp'];
		}
		$out = format_exported_header($txtver['text_name'],$txtver['version_name'],$format,$idformat);
		#$out.= $this->element_to_xml($txtver['root_id'],$corr);
		$query = "SELECT * FROM `{$txt}_elements` WHERE txtver_id=$id ORDER BY txt_position";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot read database: ".mysqli_error($this->DB));
		$openel=array(); $close=FALSE; $pos=0; $openseg=false;
		$alignables = explode(' ',$txtver['text_elements']);
		while ($e = mysqli_fetch_assoc($dbresult)) {
			while (count($openel) AND $e['parent']!=$openel[count($openel)-1]['id']) {
				$lastel=array_pop($openel);
				# Close open SEG - NO! ParaConc does not understand it, the output must break XML validity for PC compatibility!
				#if ($segs && $openseg) { $out.="</seg>\n"; $openseg=false; }
				if ($e['txt_position']==$lastel['txt_position']+1) $out.="/>";
				else $out.="</{$lastel['element_name']}>";
				$close=FALSE;
			} 
			if ($close) $out.='>'; $close=FALSE;
			$e['element_id'] = format_exported_ids($e['element_name'],$e['element_id'],$txtver['text_name'],$txtver['version_name'],$format,$idformat,$txtver['filename']);
			if ($e['element_id']!='') {
				$e['attributes'] = preg_replace('/\s*id="[^"]*"/','',$e['attributes']);
				$e['attributes'] = ' id="'.$e['element_id'].'"'.$e['attributes'];
			}
			$corresp = $corr[$e['id']];
			if (!$corresp) { $corresp='0'; }
			$alignable = in_array($e['element_name'],$alignables);
			$e['attributes'] = preg_replace('/\s*corresp="[^"]*"/','',$e['attributes']); 
			if ($cor_aid AND $alignable) {
				$e['attributes'].= " corresp=\"$corresp\""; 
			}
			# SEGment start
			if ($segs && $alignable && ($seg[$e['id']]!=$pos || !$openseg)) {
				if ($openseg) $out.="</seg>\n";
				while ($pos<$seg[$e['id']]-1) {  $pos++; $out.="<seg id=\"$pos\"></seg>\n"; }
				$pos=$seg[$e['id']];
				$out.="<seg id=\"$pos\">\n"; 
				$openseg=true; 
			}
			if (substr($e['element_name'],0,2)!='__') {
				if ($e['contents']!='') $out.="<{$e['element_name']}{$e['attributes']}>".$e['contents']."</{$e['element_name']}>";
				else {
					$out.="<{$e['element_name']}{$e['attributes']}";
					$openel[] = $e; $close=TRUE;
				}
				# Fill the rest of empty SEGments after the last element
				if ($e['id']==$lastid && $pos<$maxpos) {
					$out.="</seg>\n"; $openseg = false;
					while ($pos<$maxpos) {  $pos++; $out.="<seg id=\"$pos\"></seg>\n"; }
				}
			}
			elseif ($e['element_name']=='__TEXT__' || $e['element_name']=='__WS__')
				$out.=$e['contents'];
			elseif ($e['element_name']=='__COMMENT__')
				$out.='<!--'.$e['contents'].'-->';
			elseif (substr($e['element_name'],0,6)=='__PI__') {
				list($tmp,$name) = explode(':',$e['element_name'],2);
				$out.="<?$name ".$e['contents'].'?>';
			}
		}
		while (count($openel)) {
			$lastel=array_pop($openel);
			$out.="</{$lastel['element_name']}>";
		}
		return $out."\n";
	}

# Print element (subtree) as XML
	function element_to_xml($txt,$id,$corr=array()) {
		$e = $this->get_element($txt,$id);
		if ($e['element_id']!='') $e['attributes'] = " id=\"{$e['element_id']}\"".$e['attributes'];
		$corresp = $corr[$e['id']];
		if ($corresp) { 
			$e['attributes'] = preg_replace('/\s*corresp="[^"]*"/','',$e['attributes']); 
			$e['attributes'].= " corresp=\"$corresp\""; 
		}
		if (substr($e['element_name'],0,2)!='__') {
			if (count($e['children'])) {
				$out.="<{$e['element_name']}{$e['attributes']}>";
				foreach($e['children'] as $id) $out.=$this->element_to_xml($txt,$id,$corr);
				$out.="</{$e['element_name']}>";
			} 
			elseif ($e['contents']!='') 
				$out.="<{$e['element_name']}{$e['attributes']}>".$e['contents']."</{$e['element_name']}>";
			else 
				$out.="<{$e['element_name']}{$e['attributes']}/>";
		}
		elseif ($e['element_name']=='__DOCUMENT__')
			foreach($e['children'] as $id) $out.=$this->element_to_xml($txt,$id,$corr);
		elseif ($e['element_name']=='__TEXT__' || $e['element_name']=='__WS__')
			$out.=$e['contents'];
		elseif ($e['element_name']=='__COMMENT__')
			$out.="<!--".$e['contents'].'-->';
		elseif (substr($e['element_name'],0,6)=='__PI__') {
			list($tmp,$name) = explode(':',$e['element_name'],2);
			$out.="<?$name ".$e['contents'].' ?>';
		}
		return $out;
	}

# Import alignment
	function import_alignment($data,$aid,$default_status=STATUS_MANUAL,$report=TRUE,$ignoreheader=FALSE,$method='',$profile='',$resp=0, $editor=0,$edit='',$swap=false) {
		global $STATUS,$_ERROR,$DEFAULT_METHOD,$DEFAULT_PROFILE,$DEFAULT_EDIT_PERMISSION;
		if ($method=='') $method = $DEFAULT_METHOD;
		if ($profile=='') $profile = $DEFAULT_PROFILE;
		if ($edit=='') $edit = $DEFAULT_EDIT_PERMISSION;
		if ($aid>0) {
			$al = $this->alignment_info($aid);
			$txt = $al['text_id'];
			$v1_id = $al['ver1_id']; $v2_id = $al['ver2_id'];
			if (!$al) { $_ERROR = "Error: Alignment not found."; return FALSE; }
			if (!$al['v1uniq_ids']) { $this->update_eids($txt,$v1_id); $al = $this->alignment_info($aid); }
			if (!$al['v2uniq_ids']) { $this->update_eids($txt,$v2_id); $al = $this->alignment_info($aid); } 
			if (!$al['v1uniq_ids'] OR !$al['v2uniq_ids']) { $_ERROR = "Error: Cannot update IDs."; return FALSE; }
		}
		if ($report) { print("Process: Initializing import...\nProgress: 0\n"); flush(); ob_flush(); }
		if (substr($data,0,5)!='<?xml')
			$data = file_get_contents($data);
		$total = substr_count($data,'<link '); if (!$total) $total = substr_count($data,'<LINK ');
		if (!$total) {
				if ($report) { print("Process: Initializing import...<br />ERROR: No links found in file!\n"); flush(); ob_flush(); }
				$_ERROR .= "Error: No links found in file!";
				return FALSE;
		}
		$xml = new XMLReader();
		$xml->xml($data);
		//$xml->open($filename);
		while ($xml->name!='linkGrp') {
			$step=@$xml->read();
			if (!$step) {
				if ($report) { print("Process: Initializing import...<br />ERROR: Invalid XML file!\n"); flush(); ob_flush(); }
				$xml->close();
				$_ERROR = 'Error: Invalid XML file.';
				return FALSE;
			}
		}
		while ($xml->moveToNextAttribute()) {
			if ($xml->name=='fromDoc') $src = $xml->value;
			if ($xml->name=='toDoc') $dst = $xml->value;
		}
		if ($swap) {
			$temp=$src; $src=$dst; $dst=$temp;
		}
		list($text,$ver1) = explode('.',$src,3);
		list($textd,$ver2) = explode('.',$dst,3);
		if ($aid) {
			$myal = false;
			$al = $this->alignment_info($aid);
			$txt = $al['text_id'];
			$swap=false;
			if (!$ignoreheader) {
				if ($src==$al['ver1_filename'] && $dst==$al['ver2_filename']) { # filenames match, OK!
				} elseif ($src==$al['ver2_filename'] && $dst==$al['ver1_filename']) { #... just swap...
					$swap=true;
				} else { # check for <text_name>.<version_name>.<anything>
					if ($text!=$textd OR $text!=$al['text_name']) {
						if ($report) { print("Process: Initializing import...<br />ERROR: Alignment file links completely different texts!?\n"); flush(); ob_flush(); }
						$xml->close();
						$_ERROR = "Error: Alignment file concerns other document than requested: '$text' and '$textd'. Requested: '{$al['text_name']}'";
						return FALSE;
					}
					if ($ver1!=$al['ver1_name']) {
						if ($ver1==$al['ver2_name']) $swap=true;
						else {
							if ($report) 
								{ print("Process: Initializing import...<br />ERROR: Alignment links different text versions!\n"); flush(); ob_flush(); }
							$xml->close();
							$_ERROR = 'Error: Alignment links other versions than those requested.';
							return FALSE;
						}
					}
					if ($ver2!=$al['ver2_name'] AND ($swap AND $ver2!=$al['ver1_name'])) {
						if ($report) { print("Process: Initializing import...<br/>ERROR: Alignment links different text versions!\n"); flush(); ob_flush(); }
						$xml->close();
						$_ERROR = 'Error: Alignment links other versions than those requested.';
						return FALSE;
					}
				}
			}
		} else {
			if (!($v1=$this->txtver_by_filename($src)) || !($v2=$this->txtver_by_filename($dst))) {
				if ($text!=$textd) {
					$_ERROR = 'Error: Alignment links different documents. Correct the toDoc and srcDoc attributes of the linkGrp element.';
					return FALSE;
				}
				if (!($txtid=$this->text_id_by_name($text))) {
					$_ERROR = "Error: Document '$text' not found in the database.";
					return FALSE;
				}
				if (!($v1=$this->txtver_info($txtid,$ver1))) {
					$_ERROR = "Error: Version '$ver1' not found in the database.";
					return FALSE;
				}
				if (!($v2=$this->txtver_info($txtid,$ver2))) {
					$_ERROR = "Error: Version '$ver2' not found in the database.";
					return FALSE;
				}
			} else {
				$txtid = $v1['text_id'];
			}
			$aid = $this->insert_alignment($txtid,$v1['id'],$v2['id'],$method,$profile,$resp,$editor,$edit);
			if (!$aid) {
				$_ERROR = "Error: Such alignment already exists.";
				return FALSE;
			} else $myal = true;
			$al = $this->alignment_info($aid);
			$txt = $al['text_id'];
		}
    if (!$dbresult = mysqli_query($this->DB,"START TRANSACTION")) {
      $this->_failure("Cannot start transaction: ".mysqli_error($this->DB));
      return FALSE;
    }
		$v1i = $this->txtver_by_id($al['ver1_id']); $v2i = $this->txtver_by_id($al['ver2_id']);
		foreach(explode(' ',$v1i['text_elements']) as $element) $alignables1[]="element_name='$element'";
		foreach(explode(' ',$v2i['text_elements']) as $element) $alignables2[]="element_name='$element'";
		$alignable1 = join(' OR ',$alignables1);
		$alignable2 = join(' OR ',$alignables2);
		$v1idx=array(); $v2idx=array();$v1list=array(); $v2list=array();
		if (!$dbresult = mysqli_query($this->DB,"SELECT id,element_id FROM `{$txt}_elements` WHERE (txtver_id='{$al['ver1_id']}' AND ($alignable1)) ORDER BY txt_position")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
      if ($report) print("$_ERROR\n");
      return FALSE;
    }
		while ($ret = mysqli_fetch_assoc($dbresult)) { $v1idx[$ret['element_id']]= $ret['id']; $v1list[]= $ret['element_id']; }
		if (!$dbresult = mysqli_query($this->DB,"SELECT id,element_id FROM `{$txt}_elements` WHERE (txtver_id='{$al['ver2_id']}' AND ($alignable2)) ORDER BY txt_position")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
      if ($report) print("$_ERROR\n");
      return FALSE;
    }
		while ($ret = mysqli_fetch_assoc($dbresult)) { $v2idx[$ret['element_id']]= $ret['id']; $v2list[]= $ret['element_id']; }
		$position = 0;
		if ($report) { print("Process: Importing alignment from file...\nProgress: 0\n"); flush(); ob_flush(); }
		while (@$xml->read()) {
			$status = FALSE;
			if ($xml->name=='link') {
				$cnt++; $prog=round(($cnt/$total)*100);
				if ($report AND $prog!=$lastprog) { $lastprog=$prog; print "Progress: $prog\n"; flush(); ob_flush(); }
				while ($xml->moveToNextAttribute()) {
					if ($xml->name=='xtargets') $link = $xml->value;
					if ($xml->name=='status') $status = array_search($xml->value,$STATUS);
					if ($xml->name=='mark') $mark = $xml->value; else $mark=0;
					if ($xml->name=='type') $type = $xml->value;
				}
				if (!$status) $status = $default_status;
				if ($swap) {
					list($v1_grp,$v2_grp) = explode(';',$link);
					list($cnt1,$cnt2) = explode('-',$type);
				} else {
					list($v2_grp,$v1_grp) = explode(';',$link);
					list($cnt2,$cnt1) = explode('-',$type);
				}
				if (intval($cnt1)==0) $v1_eids = array(); else $v1_eids = explode(' ',$v1_grp);
				if (intval($cnt2)==0) $v2_eids = array(); else $v2_eids = explode(' ',$v2_grp);
				# Get database IDs by element IDs
				$v1_ids = array(); $v2_ids = array();
				foreach ($v1_eids as $eid) {
					if (preg_match('/([0-9]+([\.:\-_,][0-9]+)?)$/',$eid,$m)) {
						$eid = strtr($m[1],'.:-_,',':::::');
						if (!array_key_exists($eid,$v1idx)) {
							if ($report) { print "Process: Importing alignment from file...<br />ERROR: Element '$eid' not found in text version '{$al['ver1_name']}'! (at link #$cnt)\n"; flush(); ob_flush(); }
							$xml->close();
							$this->_failure("Error: Element '$eid' not found in  text version '{$al['ver1_name']}'! (at link #$cnt)");
							if ($myal) $this->delete_alignment($aid);
							return FALSE;
						}
						$mydbid = $v1idx[$eid];
						$nextid = array_shift($v1list);
						if ($eid==$nextid) {
							$v1_ids[] = $mydbid; $last1id = $eid;
						} else {
							if ($report) { print "Process: Importing alignment from file...<br />ERROR: Gap found in the alignment. In text version '{$al['ver1_name']}', there are alignable elements between the element '$last1id' and '$eid' which are not aligned by the imported alignment! (at link #$cnt)\n"; flush(); ob_flush(); }
							$xml->close();
							$this->_failure("Error: Gap found in the alignment. In text version '{$al['ver1_name']}', there are alignable elements between the element '$last1id' and '$eid' which are not aligned by the imported alignment! (at link #$cnt) e.g. '$nextid' but '$eid'");
							if ($myal) $this->delete_alignment($aid);
							return FALSE;
						}
					} elseif ($eid!='') {
						if ($report) { print "Process: Importing alignment from file...<br />ERROR: Unrecognized format of identificator '$eid' for text version '{$ver1}'! (at link #$cnt)\n"; flush(); ob_flush(); }
						$xml->close();
						$this->_failure("Error: Unrecognized format of identificator '$eid' for text version '{$ver1}'! (at link #$cnt)");
						if ($myal) $this->delete_alignment($aid);
						return FALSE;
					}
				}
				foreach ($v2_eids as $eid) {
					if (preg_match('/([0-9]+([\.:\-_,][0-9]+)?)$/',$eid,$m)) {
						$eid = strtr($m[1],'.:-_,',':::::');
						if (!array_key_exists($eid,$v2idx)) {
							if ($report) { print "Process: Importing alignment from file...<br />ERROR: Element '$eid' not found in text version '{$al['ver2_name']}'! (at link #$cnt)\n"; flush(); ob_flush(); }
							$xml->close();
							$this->_failure("Error: Element '$eid' not found in text version '{$al['ver2_name']}'! (at link #$cnt)");
							if ($myal) $this->delete_alignment($aid);
							return FALSE;
						}
						$mydbid = $v2idx[$eid];
						$nextid = array_shift($v2list);
						if ($eid==$nextid) {
							$v2_ids[] = $mydbid; $last2id = $eid;
						} else {
							if ($report) { print "Process: Importing alignment from file...<br />ERROR: Gap found in the alignment. In text version '{$al['ver2_name']}', there are alignable elements between the element '$last2id' and '$eid' which are not aligned by the imported alignment! (at link #$cnt)\n"; flush(); ob_flush(); }
							$xml->close();
							$this->_failure("Error: Gap found in the alignment. In text version '{$al['ver2_name']}', there are alignable elements between the element '$last2id' and '$eid' which are not aligned by the imported alignment! (at link #$cnt)");
							if ($myal) $this->delete_alignment($aid);
							return FALSE;
						}
					} elseif ($eid!='') {
						if ($report) { print "Process: Importing alignment from file...<br />ERROR: Unrecognized format of identificator '$eid' for text version '{$ver2}'! (at link #$cnt)\n"; flush(); ob_flush(); }
						$xml->close();
						$this->_failure("Error: Unrecognized format of identificator '$eid' for text version '{$ver2}'! (at link #$cnt)");
						if ($myal) $this->delete_alignment($aid);
						return FALSE;
					}
				}
				$position++; 
				if (!$this->_add_link($txt,$aid,$al['ver1_id'],$al['ver2_id'],$v1_ids,$v2_ids,$position,$status,$mark)) {
          $xml->close();
          if ($report) print("Process: Importing alignment from file...<br/>$_ERROR\n");
          if ($myal) $this->delete_alignment($aid);
          return FALSE;
				}
			}
		} 
		if ($xml->nodeType) {
			if ($report) { print("Process: Importing alignment from file...<br/>ERROR: Invalid XML file!\n"); flush(); ob_flush(); }
      $xml->close();
			$this->_failure('Error: Invalid XML file.');
			if ($myal) $this->delete_alignment($aid);
			return FALSE;
		}
		$xml->close();
    if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
      $this->_failure("Cannot commit transaction: ".mysqli_error($this->DB));
      if ($report) print("$_ERROR\n");
      return FALSE;
    }
		return $aid;
	}

# Export alignment
	function export_alignment($aid,$format='xml',$skipempty=false,$maxstatus=STATUS_PLAIN) {
		global $STATUS, $_ERROR;
		$al = $this->alignment_info($aid);
		if (!$al) { $_ERROR = "Error: Alignment not found."; return FALSE; }
		$txt = $al['text_id'];
		$v1_id = $al['ver1_id']; $v2_id = $al['ver2_id'];
		if (!$al['v1uniq_ids']) { $this->update_eids($txt,$v1_id); $al = $this->alignment_info($aid); }
		if (!$al['v2uniq_ids']) { $this->update_eids($txt,$v2_id); $al = $this->alignment_info($aid); } 
		if (!$al['v1uniq_ids'] OR !$al['v2uniq_ids']) { $_ERROR = "Error: Cannot update IDs."; return FALSE; }
		list($format,$idformat) = explode(':',$format,2);
		if ($format=='xml' || $format=='xml_links') {
			$out = "<?xml version='1.0' encoding='utf-8'?>\n";
			$out.= "<linkGrp toDoc='{$al['text_name']}.{$al['ver2_name']}.xml' fromDoc='{$al['text_name']}.{$al['ver1_name']}.xml'>\n";
			$from=1;
			do {
				$res = $this->get_aligned_items($txt,$aid,$from,200,$maxstatus);
				foreach ($res as $pos => $row) {
					$v1ids=array(); $v2ids=array(); $stat = 0; $maxmark = 0;
					$v1c=count($row[$v2_id]); $v2c=count($row[$v1_id]);
					if ($skipempty && $v1c==0 && $v2c==0)
                                            continue;
					if (IsSet($row[$v1_id]))
						foreach ($row[$v1_id] as $item) {
							$v1ids[]= format_exported_ids('_alignable_',$item['element_id'],$al['text_name'],$al['ver1_name'],$format,$idformat);
							if ($item['link_status']>$stat) $stat = $item['link_status'];
							if ($item['link_mark']>$maxmark) $maxmark = $item['link_mark'];
						}
					if (IsSet($row[$v2_id]))
						foreach ($row[$v2_id] as $item) { 
							$v2ids[]= format_exported_ids('_alignable_',$item['element_id'],$al['text_name'],$al['ver2_name'],$format,$idformat);
							if ($item['link_status']>$stat) $stat = $item['link_status'];
							if ($item['link_mark']>$maxmark) $maxmark = $item['link_mark'];
						}
					if ($maxmark) $mark=" mark='{$maxmark}'"; else $mark='';
					natsort($v1ids); natsort($v2ids);
					$v1l = join(' ',$v1ids); $v2l = join(' ',$v2ids);
					$out.= "<link type='$v1c-$v2c' xtargets='$v2l;$v1l' status='{$STATUS[$stat]}'$mark/>\n";
				}
				$from=$from+200;
			} while ($from<=$al['link_count']);
			$out.= "</linkGrp>\n";
		} else {
			$_ERROR = "Error: Unknown format.";
			return FALSE;
		}
		return $out;
	}

# Add new aligenment link - no checking
	function _add_link($txt,$aid,$v1_id,$v2_id,$v1_eids,$v2_eids,$position,$status=STATUS_MANUAL,$mark=0) {
		# Insert link items
		foreach ($v1_eids as $id) {
      if ($this->_new_link_item($txt,$aid,$v1_id,$id,$position,$status,$mark)===FALSE)
        return FALSE;
    }
		foreach ($v2_eids as $id) {
      if ($this->_new_link_item($txt,$aid,$v2_id,$id,$position,$status,$mark)===FALSE)
        return FALSE;
    }
		return TRUE;
	}

# Plain alignment 1:1
	function plain_alignment($aid,$report=TRUE) {
		if ($report) { print "Process: Prepairing plain alignment...\nProgress: 0\n"; flush(); ob_flush(); }
		$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		$v1 = $al['ver1_id']; $v2 = $al['ver2_id'];
		$v1i = $this->txtver_by_id($v1); $v2i = $this->txtver_by_id($v2);
		foreach(explode(' ',$v1i['text_elements']) as $element) $alignables1[]="element_name='$element'";
		foreach(explode(' ',$v2i['text_elements']) as $element) $alignables2[]="element_name='$element'";
		$alignable1 = join(' OR ',$alignables1);
		$alignable2 = join(' OR ',$alignables2);
		# CHECK TABLE accelerates the query in our version of MySQL by multiple orders!
		mysqli_query($this->DB,"CHECK TABLE `{$txt}_links`");
    if (!$dbresult = mysqli_query($this->DB,"START TRANSACTION")) {
      $this->_failure("Cannot start transaction: ".mysqli_error($this->DB));
      return FALSE;
    }
		# Get list of unaligned elements from text version 1
		if (!$dbresult = mysqli_query($this->DB,"SELECT id FROM `{$txt}_elements` WHERE (txtver_id='$v1' AND ($alignable1) AND id NOT IN (SELECT element_id FROM `{$txt}_links` WHERE alignment_id='$aid' AND version_id='$v1')) ORDER BY txt_position")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
      return FALSE;
    }
		$v1_ids = array(); $v2_ids = array();
		if (mysqli_num_rows($dbresult))
			while ($ret = mysqli_fetch_assoc($dbresult)) $v1_ids[]=$ret['id'];
		# Get list of unaligned elements from text version 2
		if (!$dbresult = mysqli_query($this->DB,"SELECT id FROM `{$txt}_elements` WHERE (txtver_id='$v2' AND ($alignable2) AND id NOT IN (SELECT element_id FROM `{$txt}_links` WHERE alignment_id='$aid' AND version_id='$v2')) ORDER BY txt_position")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
      return FALSE;
    }
		if (mysqli_num_rows($dbresult))
			while ($ret = mysqli_fetch_assoc($dbresult)) $v2_ids[]=$ret['id'];
		# Nothing more to do here? ...return
		if (count($v1_ids)==0 AND count($v2_ids)==0) {
			if ($report) { print "Process: Nothing to align.\n"; flush(); ob_flush(); }
      if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
        $this->_failure("Cannot commit transaction: ".mysqli_error($this->DB)); // Is this a problem...?
      }
			return TRUE;
		}
		# Otherwise align the rest 1:1
		# first: get the position of the last alignment link
		if (!$dbresult = mysqli_query($this->DB,"SELECT max(position) as last FROM `{$txt}_links` WHERE (alignment_id='$aid')")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
      return FALSE;
    }
		if (!mysqli_num_rows($dbresult)) $position=0;
		else {
			$ret = mysqli_fetch_assoc($dbresult);
			$position = $ret['last'];
		}
		# Align 1:1 while there are unaligned elements from text version 1
		$total = count($v1_ids); if (count($v2_ids)>$total) $total=count($v2_ids); $cnt=0;
		if ($report) { print "Process: Aligning unaligned elements...\nProgress: 0\n"; flush(); ob_flush(); }
		while ($e1=array_shift($v1_ids)) {
			$position++; $cnt++; $prog=round(($cnt/$total)*100);
			if ($e2=array_shift($v2_ids)) $el2arr = array($e2); else $el2arr = array();
			if (!$this->_add_link($txt,$aid,$v1,$v2,array($e1),$el2arr,$position,STATUS_PLAIN))
        return FALSE;
			if ($report AND $prog!=$lastprog) { $lastprog=$prog; print "Progress: $prog\n"; flush(); ob_flush(); }
		}
		# Align 0:1 if there are any unaligned elements left from text version 2
		while ($e2=array_shift($v2_ids)) {
			$position++; $cnt++; $prog=round(($cnt/$total)*100);
			if (!$this->_add_link($txt,$aid,$v1,$v2,array(),array($e2),$position,STATUS_PLAIN))
        return FALSE;
			if ($report AND $prog!=$lastprog) { $lastprog=$prog; print "Progress: $prog\n"; flush(); ob_flush(); }
		}
    if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
      $this->_failure("Cannot commit transaction: ".mysqli_error($this->DB));
      return FALSE;
    }
		return TRUE;
	}

# Delete document from database
	function delete_document($txt,$id) {
		$txtver = $this->txtver_by_id($id);
		if (!$dbresult = mysqli_query($this->DB,"SELECT id FROM alignments WHERE (ver1_id=$id OR ver2_id=$id)"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult))
			$this->_fail("Cannot delete document - it is used by one or more alignments!");
		# Now delete the record for the version
		if (!$dbresult = mysqli_query($this->DB,"DELETE FROM `{$txt}_elements` WHERE (txtver_id=$id)"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (!$dbresult = mysqli_query($this->DB,"DELETE FROM `{$txt}_changelog` WHERE (txtver_id=$id)"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (!$dbresult = mysqli_query($this->DB,"DELETE FROM versions WHERE (text_id=$txt AND id=$id)"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		# Any more versions of this text? If not, delete the text record too
		if (!$dbresult = mysqli_query($this->DB,"SELECT id FROM versions WHERE text_id=$txt"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (!mysqli_num_rows($dbresult)) {
			if (!$dbresult = mysqli_query($this->DB,"DELETE FROM texts WHERE id=$txt"))
				$this->_fail("Cannot access database: ".mysqli_error($this->DB));
			if (!$dbresult = mysqli_query($this->DB,"DROP TABLE `{$txt}_elements`, `{$txt}_links`, `{$txt}_changelog`"))
				$this->_fail("Cannot access database: ".mysqli_error($this->DB));
			if (!$dbresult = mysqli_query($this->DB,"DROP TABLE IF EXISTS `{$txt}_align_changelog`"))
				$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		}
	}

# Get info about document version by name
	function txtver_info($txt,$version) {
		#$txtid = $this->text_id_by_name($name);
		$version = mysqli_real_escape_string($this->DB,$version);
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM versions WHERE text_id=$txt AND version_name='$version'"))
			return FALSE; #$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (!mysqli_num_rows($dbresult))
			return FALSE;
		else {
			$res = mysqli_fetch_assoc($dbresult);
			return $res;
		}
	}

# Get info about document version by filename
	function txtver_by_filename($filename) {
		#$txtid = $this->text_id_by_name($name);
		$filename = mysqli_real_escape_string($this->DB,$filename);
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM versions WHERE filename='$filename'"))
			return FALSE; #$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (!mysqli_num_rows($dbresult))
			return FALSE;
		else {
			$res = mysqli_fetch_assoc($dbresult);
			return $res;
		}
	}

# Get info about document version by its id
	function txtver_by_id($id) {
		$txt = mysqli_real_escape_string($this->DB,$txt);
		if (!$dbresult = mysqli_query($this->DB,"SELECT v.*, t.name as text_name, t.id as text_id FROM versions v, texts t WHERE v.text_id=t.id AND v.id='$id'")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
			return FALSE;
		}
		if (!mysqli_num_rows($dbresult)) {
			$this->_failure("Document not found.");
			return FALSE;
		} else {
			$res = mysqli_fetch_assoc($dbresult);
			return $res;
		}
	}

# Get info about alignment
	function alignment_info($id) {
		if (!$dbresult = mysqli_query($this->DB,"SELECT a.*, t.name as text_name, t.id as text_id, v1.version_name as ver1_name, v1.id as ver1_id, v1.filename as ver1_filename, v2.version_name as ver2_name, v2.id as ver2_id, v2.filename as ver2_filename, v1.uniq_ids as v1uniq_ids, v2.uniq_ids as v2uniq_ids FROM alignments as a, texts as t, versions as v1, versions as v2 WHERE a.id=$id AND t.id=v1.text_id AND  v1.id=a.ver1_id AND v2.id=a.ver2_id"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (!mysqli_num_rows($dbresult))
			return FALSE;
		else {
			$res = mysqli_fetch_assoc($dbresult);
			if (!$dbresult = mysqli_query($this->DB,"SELECT max(position) as link_count FROM `{$res['text_id']}_links` WHERE alignment_id=$id"))
				$this->_fail("Cannot access database: ".mysqli_error($this->DB));
			$res1 = mysqli_fetch_assoc($dbresult);
			$res['link_count'] = $res1['link_count'];
      if (!$dbresult = mysqli_query($this->DB,"SHOW TABLES LIKE '{$res['text_id']}_align_changelog'"))
        $this->_fail("Cannot access database: ".mysqli_error($this->DB));
      if (mysqli_num_rows($dbresult)>0)
        $res['alchangelog'] = true;
      else
        $res['alchangelog'] = false;
			return $res;
		}
	}

# Get name of text by id
	function textname_by_id($id) {
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM texts WHERE id=$id"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) {
			$res = mysqli_fetch_assoc($dbresult);
			return $res['name'];
		} else {
			return FALSE;
		}
	}

# Get text id by its name
	function text_id_by_name($name) {
		return $this->register_text($name,TRUE);
	}

# Register text or get text-id
	function register_text($name,$donotcreate=FALSE) {
    global $DISABLE_FULLTEXT;
		$name = mysqli_real_escape_string($this->DB,$name);
		#if (!$dbresult = mysqli_query($this->DB,"SHOW TABLES LIKE '{$name}_versions'"))
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM texts WHERE name='$name'"))
			$this->_fail("Cannot check tables in database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) {
			$res = mysqli_fetch_assoc($dbresult);
			return $res['id'];
		} else {
			if ($donotcreate) return FALSE;
			else {
        if (!$dbresult = mysqli_query($this->DB,"START TRANSACTION")) {
          $this->_failure("Cannot start transaction: ".mysqli_error($this->DB));
          return FALSE;
        }
				if (!$dbresult = mysqli_query($this->DB,"INSERT INTO texts (name) VALUES ('$name')")) {
					$this->_failure("Cannot insert new text into database: ".mysqli_error($this->DB));
					return FALSE;
				}
				$txtid= mysqli_insert_id($this->DB);
# SQL table definitions
// $table_versions = "CREATE TABLE `{$name}_versions` (
//   id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
//   version_name TINYTEXT,
//   root_id BIGINT UNSIGNED,
// 	text_changed BOOL DEFAULT FALSE,
// 	uniq_ids BOOL DEFAULT FALSE,
// 	text_elements TEXT NOT NULL,
// 
//   PRIMARY KEY (id),
// 	INDEX index_name (version_name(20))
// )";
// $table_alignments = "CREATE TABLE `{$name}_alignments` (
// 	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
// 	ver1_id BIGINT UNSIGNED,
// 	ver2_id BIGINT UNSIGNED,
// 	method TINYTEXT NOT NULL,
// 	profile TINYTEXT NOT NULL,
// 	resp INT UNSIGNED,
// 	editor INT UNSIGNED,
// 	c_chstruct BOOLEAN DEFAULT 0,
// 	chtext BOOLEAN DEFAULT 0,
// 
// 	PRIMARY KEY (id),
// 	INDEX index_ver1 (ver1_id),
// 	INDEX index_ver2 (ver2_id),
// 	INDEX index_resp (resp),
// 	INDEX index_editor (editor)
// )";
$table_elements = "CREATE TABLE `{$txtid}_elements` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	txtver_id BIGINT UNSIGNED,
	txt_position BIGINT UNSIGNED,
  parent BIGINT UNSIGNED,
  position BIGINT UNSIGNED,
  element_name TINYTEXT,
  element_id TINYTEXT,
  attributes TEXT,
  contents TEXT,

  PRIMARY KEY (id),
  INDEX index_text (txtver_id),
  INDEX index_txt_position (txt_position),
  INDEX index_parent (parent),
  INDEX index_position (position),
	INDEX index_element_name (element_name(10)),
	INDEX index_element_id (element_id(10))
  ";
  if (!$DISABLE_FULLTEXT)
    $table_elements .= ",\nFULLTEXT index_ft_contents (contents)";
  //else
  //  $table_elements .= "INDEX index_contents (contents)\n)";
  $table_elements .= "\n)";
$table_links = "CREATE TABLE `{$txtid}_links` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  alignment_id BIGINT UNSIGNED,
  version_id BIGINT UNSIGNED,
	element_id BIGINT UNSIGNED,
  position BIGINT UNSIGNED,
	status INT UNSIGNED,
	mark INT UNSIGNED DEFAULT 0,

  PRIMARY KEY (id),
  INDEX index_alignment (alignment_id),
  INDEX index_version (version_id),
	INDEX index_element (element_id),
  INDEX index_position (position),
	INDEX index_status (status),
	INDEX index_mark (mark)
)";
$table_changelog = "CREATE TABLE `{$txtid}_changelog` (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	txtver_id BIGINT UNSIGNED,
	element_id BIGINT UNSIGNED,
	chtype CHAR(1) NOT NULL,
	assoc_id BIGINT UNSIGNED DEFAULT NULL,
	userid INT UNSIGNED,
	ts DATETIME NOT NULL,
	old_contents TEXT,
	open BOOLEAN DEFAULT FALSE,
	
	PRIMARY KEY(id),
	INDEX index_txtver_id (txtver_id),
	INDEX index_element_id (element_id),
	INDEX index_chtype (chtype),
	INDEX index_assoc_id (assoc_id),
	INDEX index_userid (userid),
	INDEX index_ts (ts),
	INDEX index_open (open)
)";
/* FULLTEXT index_ft_old_contents (old_contents) - not used */
/*				if (!$dbresult = mysqli_query($this->DB,$table_versions))
					$this->_fail("Cannot create new text in the database: ".mysqli_error($this->DB));*/
				if (!$dbresult = mysqli_query($this->DB,"SET storage_engine=InnoDB")) {
					$this->_failure("Cannot set default storage engine to InnoDB: ".mysqli_error($this->DB));
          return FALSE;
        }
				if (!$dbresult = mysqli_query($this->DB,$table_elements)) {
					$this->_failure("Cannot create new text elements table in the database: ".mysqli_error($this->DB).". Are you sure you have a supported version of MySQL (>=5.6)?");
          if (!$dbresult = mysqli_query($this->DB,"DELETE FROM texts WHERE id=$txtid")) { // just to be sure for bad upgraders
            $this->_failure("Cannot insert new text into database: ".mysqli_error($this->DB));
            return FALSE;
          }
          return FALSE;
        }
				if (!$dbresult = mysqli_query($this->DB,$table_links)) {
					$this->_failure("Cannot create new text link table in the database: ".mysqli_error($this->DB));
          return FALSE;
        }
				if (!$dbresult = mysqli_query($this->DB,$table_changelog)) {
					$this->_failure("Cannot create new text changelog table in the database: ".mysqli_error($this->DB));
          return FALSE;
        }
        if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
          $this->_failure("Cannot commit transaction: ".mysqli_error($this->DB));
          return FALSE;
        }
				//if (!$dbresult = mysqli_query($this->DB,$table_align_changelog))
				//	$this->_fail("Cannot create new alignment changelog table in the database: ".mysqli_error($this->DB));
				return $txtid;
			}
		}
	}

# Register new text version
	function insert_version($txtid,$name,$root_id,$text_elements,$filename='') {
		$name = mysqli_real_escape_string($this->DB,$name);
		$filename = mysqli_real_escape_string($this->DB,$filename);
		$values = "$txtid,'$name', $root_id, '$text_elements','$filename'";
		if (!$dbresult = mysqli_query($this->DB,"INSERT INTO versions (text_id,version_name,root_id, text_elements, filename) VALUES ($values)")) {
			$this->_failure("Cannot insert new text into database: ".mysqli_error($this->DB));
			return FALSE;
		}
		return mysqli_insert_id($this->DB);
	}

# Test existence of alignment
	function alignment_exists($txt_id,$v1_id,$v2_id) {
		$query="SELECT id FROM alignments WHERE ((ver1_id='$v1_id' AND ver2_id='$v2_id') OR (ver1_id='$v2_id' AND ver2_id='$v1_id'))";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB)." Query: $query\n");
		if (mysqli_num_rows($dbresult))
			return true;
		else
			return false;
	}

# Insert new aligenment
	function insert_alignment($txt_id,$v1_id,$v2_id,$method='',$profile='',$resp=0,$editor=0,$edit=0) {
		global $DEFAULT_C_CHSTRUCT_PERMISSION;
		if ($DEFAULT_C_CHSTRUCT_PERMISSION!=1)
			$DEFAULT_C_CHSTRUCT_PERMISSION = 0;
		if ($this->alignment_exists($txt_id,$v1_id,$v2_id))
			return false; # Alignment already exists!
		$values = "$v1_id, $v2_id, '$method', '$profile',$edit,$DEFAULT_C_CHSTRUCT_PERMISSION"; $add='';
		if ($resp) { $add .= ',resp'; $values .= ','.$resp; }
		if ($editor) { $add .= ',editor'; $values .= ','.$editor; }
		$query = "INSERT INTO alignments (ver1_id,ver2_id,method,profile,chtext,c_chstruct$add) VALUES ($values)";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot insert new alignment into database: ".mysqli_error($this->DB)." Query: $query");
		$mysqlid = mysqli_insert_id($this->DB);
		# Reset c_chstruct permissions when new alignment created for the same text
		$query = "UPDATE alignments a, versions v1, versions v2 SET a.c_chstruct=0 WHERE (a.ver1_id=v1.id AND a.ver2_id=v2.id AND v1.text_id=$txt_id AND v2.text_id=$txt_id)";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot reset c_chstruct permission: ".mysqli_error($this->DB)." Query: $query");
		return $mysqlid;
	}

# Add new link item
	function _new_link_item($txt,$aid,$vid,$eid,$position,$status,$mark=0) {
		$values = "$aid, $vid, $eid, $position, $status, $mark";
		if (!$dbresult = mysqli_query($this->DB,"INSERT INTO `{$txt}_links` (alignment_id,version_id,element_id,position,status,mark) VALUES ($values)")) {
			$this->_failure("Cannot insert new link into database: ".mysqli_error($this->DB));
			return FALSE;
		}
		return mysqli_insert_id($this->DB);
	}

# Parse XML source and insert elements into database
	function parse_xml($xml,$parent_id,$txt,$txtverid,$txtpos,$debug=FALSE,$validate=false) {
		global $txt_elements,$_ERROR, $FORCE_SIMPLE_NUMBERING;
		$position=0; $continue = true;
		do {
			switch ($xml->nodeType) {
			case XMLReader::ELEMENT:
				$position++; $txtpos++; $eid='';
				$name = $xml->name; $eid = ''; $attributes='';
				$empty = $xml->isEmptyElement;
				if($xml->hasAttributes) 
					while($xml->moveToNextAttribute()) {
						if ($xml->name=='id' && !($FORCE_SIMPLE_NUMBERING && !in_array($name,$txt_elements))) {
							if (in_array($name,$txt_elements)) $preg = '/([0-9]+([\.:\-_,][0-9]+)?)$/'; else $preg = '/([0-9]+)$/';
							if (preg_match($preg,trim($xml->value),$m)) 
								$eid = strtr($m[1],'.:-_,',':::::');
						} else {
							$attributes .= " {$xml->name}=\"{$xml->value}\"";
						}
					}
				if (in_array($name,$txt_elements)) {
					# ALIGNABLE ELEMENTS
					$cont = $xml->readInnerXML();
					#if (!$this->check_text($cont,$debug)) return FALSE;
					#if (!$IGNORE_EMPTY_ELEMENTS && !$cont) {
					#	if ($debug) print "Warning: Empty element id '$eid'.\n";
					#}
					if ($this->_insert_element($txt,$txtverid,$txtpos,$parent_id,$position,$name,$eid,$attributes,$cont)===false)
            return FALSE;
					if(!$xml->next()) { 
						$this->_failure($_ERROR."Error: Cannot parse beyond element '{$name}' ID '$eid'.\n");
						$arErrors = libxml_get_errors();
						$xml_errors = "";
						foreach ($arErrors AS $xmlError) $xml_errors .= $xmlError->message;
						if ($xml_errors != "") {
							$_ERROR .= "XML errors:\n".$xml_errors."\n";
						}
						return FALSE;
					}
				} else {
					# NON-ALIGNABLE ELEMENTS
					$my_id = $this->_insert_element($txt,$txtverid,$txtpos,$parent_id,$position,$name,$eid,$attributes,'NULL');
					if ($my_id===FALSE)
            return FALSE;
					if (!$empty) { 
						if (!$xml->read()) {
							$this->_failure($_ERROR."Error: Cannot parse beyond new element: '{$name}' ID '$eid'.\n");
							$arErrors = libxml_get_errors();
							$xml_errors = "";
							foreach ($arErrors AS $xmlError) $xml_errors .= $xmlError->message;
							if ($xml_errors != "") {
								$_ERROR .= "XML errors:\n".$xml_errors."\n";
							}
							return FALSE;
						} 
						$txtpos = $this->parse_xml($xml,$my_id,$txt,$txtverid,$txtpos,$debug);
						if (!$txtpos) {
              $this->_failure($_ERROR."Error: Error parsing subtree of element '{$name}' ID '$eid'.\n");
              return FALSE;
            }
					} else if (!$xml->read()) {
						$this->_failure($_ERROR."Error: Cannot parse beyond empty element: '{$name}'\n");
						return FALSE;
					}
				}
				break;
			case XMLReader::TEXT: 
				$position++; $txtpos++;
				$name= '__TEXT__'; $eid = ''; $attributes='';
				if ($this->_insert_element($txt,$txtverid,$txtpos,$parent_id,$position,$name,$eid,$attributes,$xml->value)===FALSE)
            return FALSE;
				if (!$xml->read()) {
					$this->_failure($_ERROR."Error: Cannot parse text contents: '{$xml->value}'\n");
					return FALSE;
				}
				break;
			case XMLReader::CDATA:
				$position++; $txtpos++;
				$name= '__TEXT__'; $eid = ''; $attributes='';
				if ($this->_insert_element($txt,$txtverid,$txtpos,$parent_id,$position,$name,$eid,$attributes,$xml->readOuterXML())===FALSE)
            return FALSE;
				if (!$xml->read()) {
					$this->_failure($_ERROR."Error: Cannot parse beyond CDATA element: '".$xml->readOuterXML()."'\n");
					return FALSE;
				}
				break;
			case XMLReader::COMMENT:
				$position++; $txtpos++;
				$name= '__COMMENT__'; $eid = ''; $attributes='';
				if ($this->_insert_element($txt,$txtverid,$txtpos,$parent_id,$position,$name,$eid,$attributes,$xml->value)===FALSE)
            return FALSE;
				if (!$xml->read()) $continue = false;
				break;
			case XMLReader::PI:
				$position++; $txtpos++;
				$name= '__PI__:'.$xml->name; $eid = ''; $attributes='';
				if ($this->_insert_element($txt,$txtverid,$txtpos,$parent_id,$position,$name,$eid,$attributes,$xml->value)===FALSE)
            return FALSE;
				if (!$xml->read()) $continue = false;
				break;
			case XMLReader::SIGNIFICANT_WHITESPACE: 
				$txtpos++;
				$name= '__WS__'; $eid = ''; $attributes='';
				if ($this->_insert_element($txt,$txtverid,$txtpos,$parent_id,$position,$name,$eid,$attributes,$xml->value)===FALSE)
            return FALSE;
				if (!$xml->read()) $continue = false;
				break;
			case XMLReader::END_ELEMENT:
				if (!$xml->read()) $continue = false;
				return $txtpos;
				break;
			default:
				if (!$xml->read()) $continue = false;
			}
		} while($xml->nodeType AND $continue AND ($xml->isValid() OR !$validate));
		if ($validate && !$xml->isValid()) {
			$this->_failure($_ERROR."Error: XML broken after node: '".$name."'\n");
			$arErrors = libxml_get_errors();
			$xml_errors = "";
			foreach ($arErrors AS $xmlError) $xml_errors .= $xmlError->message;
			if ($xml_errors != "") {
				$_ERROR .= "XML errors:\n".$xml_errors."\n";
			}
			return FALSE;
		} else
			return $txtpos;
	}

# Parse XML source and update elements by id in the database
	function parse_xml_update($xml,$txt,$txtverid,$txt_elements,$userid=0,$verbose=true,$validate=false) {
		global $_ERROR;
		$position=0;
		do {
			switch ($xml->nodeType) {
			case XMLReader::ELEMENT:
				$name = $xml->name; $eid = '';
				$empty = $xml->isEmptyElement;
				if($xml->hasAttributes) 
					while($xml->moveToNextAttribute()) {
						if ($xml->name=='id') {
							if (preg_match('/([0-9]+([\.:\-_,][0-9]+)?)$/',trim($xml->value),$m)) 
								$eid = strtr($m[1],'.:-_,',':::::');
						}
					}
				if (in_array($name,$txt_elements)) {
					$cont = trim($xml->readInnerXML());
					$oldel = $this->get_el_by_element_id($eid,$txt,$txtverid);
					if (!$oldel) { $_ERROR="Error: Element ID '$eid' not found in text."; return FALSE; }
					if ($oldel['contents']!=$cont) {
						if ($verbose) print "[$eid] UPDATING:\nold: {$oldel['contents']}\nnew: $cont\n";
						$this->update_element_text($txt,$oldel['id'],$cont,$userid);
					} #elseif ($verbose) print "[$eid]\n";
					#$this->_insert_element($txt,$txtverid,$txtpos,$parent_id,$position,$name,$eid,$attributes,$cont);
					if(!$xml->next()) { $_ERROR='Error: Invalid XML!'; return FALSE; }
				} else {
					if (!$empty) { 
						if (!$xml->read()) { $_ERROR='Error: Invalid XML!'; return FALSE; }
						$ret = $this->parse_xml_update($xml,$txt,$txtverid,$txt_elements,$userid,$verbose);
						if (!$ret) return FALSE;
					}
					else if (!$xml->read()) { $_ERROR='Error: Invalid XML!'; return FALSE; }
				}
				break;
			case XMLReader::END_ELEMENT:
				$xml->read(); 
				return true;
				break;
			default:
				$xml->read();
			}
		} while($xml->nodeType);
		return true;
	}


# Insert element into database
	function _insert_element($txt,$txtverid,$txt_position,$parent_id,$position,$name,$eid='',$attributes='',$contents='NULL') {
		$name = "'".mysqli_real_escape_string($this->DB,$name)."'";
		if ($contents!='NULL') $contents = "'".mysqli_real_escape_string($this->DB,$contents)."'";
		$attributes = mysqli_real_escape_string($this->DB,$attributes);
		$eid = mysqli_real_escape_string($this->DB,$eid);
/*		if (!$dbresult = mysqli_query($this->DB,"SELECT id FROM elements WHERE parent='$parent_id'"))
			$this->_fail("Cannot count elements in database: ".mysqli_error($this->DB));
		$position = mysqli_num_rows($dbresult)+1;*/
		$values = "$txtverid,$txt_position,$parent_id,$position,$name,'$eid','$attributes',$contents";
		if (!$dbresult = mysqli_query($this->DB,"INSERT INTO `{$txt}_elements` (txtver_id,txt_position,parent,position,element_name,element_id,attributes,contents) VALUES ($values)")) {
			$this->_failure("Cannot insert element into database: ".mysqli_error($this->DB));
			return FALSE;
		}
		return mysqli_insert_id($this->DB);
	}

# Delete element and its subelements
#	function delete_subtree($txt,$id) {
#		if (!$dbresult = mysqli_query($this->DB,"SELECT id FROM `{$txt}_elements` WHERE parent='$id'"))
#			$this->_fail("Cannot count elements in database: ".mysqli_error($this->DB));
#		if (mysqli_num_rows($dbresult))
#			while ($child=mysqli_fetch_assoc($dbresult)) $this->delete_subtree($txt,$child['id']);
#		if (!$dbresult = mysqli_query($this->DB,"DELETE FROM `{$txt}_elements` WHERE (id='$id')"))
#				$this->_fail("Cannot access database: ".mysqli_error($this->DB));
#		return TRUE;
#	}

# delete alignment
	function delete_alignment($aid) {
		$al = $this->alignment_info($aid); $txt = $al['text_id'];
		if (!$dbresult = mysqli_query($this->DB,"DELETE FROM `{$txt}_links` WHERE alignment_id='$aid'"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (!$dbresult = mysqli_query($this->DB,"DELETE FROM alignments WHERE id='$aid'"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		$dbresult = @mysqli_query($this->DB,"DELETE FROM `{$txt}_align_changelog` WHERE (alignment_id=$aid)");
		return TRUE;
	}

# Get element by ID from database
	function get_element($txt,$id) {
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE id='$id'"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) {
			$element = mysqli_fetch_assoc($dbresult);
			$element['children'] = array();
			if (!$dbresult = mysqli_query($this->DB,"SELECT id FROM `{$txt}_elements` WHERE parent='$id' ORDER BY position"))
				$this->_fail("Cannot access database: ".mysqli_error($this->DB));
			while ($child=mysqli_fetch_assoc($dbresult)) $element['children'][]=$child['id'];
			return $element;
		} else return;
	}

# Return database ID of element with given XML id (and text version id)
	function get_id_by_element_id($eid,$txt,$vid) {
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (element_id='$eid' AND txtver_id='$vid')"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) {
			$element = mysqli_fetch_assoc($dbresult);
			return $element['id'];
		} else return FALSE;
	}

# Return element with given XML id (and text version id)
	function get_el_by_element_id($eid,$txt,$vid) {
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (element_id='$eid' AND txtver_id='$vid')"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) {
			$element = mysqli_fetch_assoc($dbresult);
			return $element;
		} else return FALSE;
	}

# Make-up SQL filtering conditions
	function make_filter($filter) {
		$filter_cond = '';
		if (IsSet($filter['tname']) && $filter['tname']!='')
			$filter_cond .= " AND LOCATE('".mysqli_real_escape_string($this->DB,$filter['tname'])."',t.name)>0";
		if (IsSet($filter['v1name']) && $filter['v1name']!='')
			$filter_cond .= " AND LOCATE('".mysqli_real_escape_string($this->DB,$filter['v1name'])."',v1.version_name)>0";
		if (IsSet($filter['v2name']) && $filter['v2name']!='')
			$filter_cond .= " AND LOCATE('".mysqli_real_escape_string($this->DB,$filter['v2name'])."',v2.version_name)>0";
		if (IsSet($filter['editor']))
			if ($filter['editor']=='')
				$filter_cond .= " AND a.editor IS NULL";
			else
				$filter_cond .= " AND a.editor={$filter['editor']}";
		if (IsSet($filter['resp']))
			if ($filter['resp']=='')
				$filter_cond .= " AND a.resp IS NULL";
			else
				$filter_cond .= " AND a.resp={$filter['resp']}";
		if (IsSet($filter['status']) && $filter['status']!='')
			$filter_cond .= " AND a.status={$filter['status']}";
		return $filter_cond;
	}

# Get alignments by text version
	function get_alignments($txt,$vid,$order='t.name ASC, v1.version_name ASC',$filter=array()) {
		$ret = array(); $txt = mysqli_real_escape_string($this->DB,$txt);
		$filter_cond = $this->make_filter($filter);
		if ($vid>0) $vcond = "AND (v1.id=$vid OR v2.id=$vid)"; else $vcond = '';
		if (!$dbresult = mysqli_query($this->DB,"SELECT a.*, t.name as text_name, v1.version_name as v1_name, v2.version_name as v2_name FROM texts t, alignments as a, versions as v1, versions as v2 WHERE t.id=$txt AND v1.text_id=$txt AND v2.text_id=$txt $vcond AND v1.id=a.ver1_id AND v2.id=a.ver2_id$filter_cond ORDER BY $order"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		while ($ret[] =  mysqli_fetch_assoc($dbresult));
		array_pop($ret);
		return $ret;
	}

# Get alignment by name
	function get_alignment_by_name($tname,$v1name,$v2name) {
		global $_ERROR;
		if (!$dbresult = mysqli_query($this->DB,"SELECT a.* FROM texts t, alignments as a, versions as v1, versions as v2 WHERE t.name='$tname' AND v1.text_id=t.id AND v1.version_name='$v1name' AND v2.text_id=t.id AND v2.version_name='$v2name' AND v1.id=a.ver1_id AND v2.id=a.ver2_id"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)==1)
			return mysqli_fetch_assoc($dbresult);
		else {error_log("SELECT a.* FROM texts t, alignments as a, versions as v1, versions as v2 WHERE t.name='$tname' AND v1.text_id=t.id AND v1.version_name='$v1name' AND v2.text_id=t.id AND v2.version_name='$v2name' AND v1.id=a.ver1_id AND v2.id=a.ver2_id");
			$_ERROR = "Error: Alignment not found.";
			return false;
		}
	}

# Get alignments by user id
	function get_alignments_by_uid($id,$order='t.name ASC, v1.version_name ASC',$filter=array()) {
		$ret = array();
		$filter_cond = $this->make_filter($filter);
		if ($id) $uidsel = "(a.resp=$id OR a.editor=$id) AND"; else $uidsel = "";
		if (!$dbresult = mysqli_query($this->DB,"SELECT a.*, t.name as text_name, v1.version_name as v1_name, v2.version_name as v2_name, t.id as text_id, v1.id as v1_id, v2.id as v2_id FROM alignments as a, versions as v1, versions as v2, texts as t WHERE $uidsel v1.id=a.ver1_id AND v2.id=a.ver2_id AND t.id=v1.text_id$filter_cond ORDER BY $order"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		while ($ret[] =  mysqli_fetch_assoc($dbresult));
		array_pop($ret);
		return $ret;
	}

# Get alignment's last text change
	function get_document_lastchange($txt, $verid, $userid = -1) {
		$ret = array();
		if ($userid>=0) $excl="AND !(userid='$userid' AND open=1)";
		if (!$dbresult = mysqli_query($this->DB,"SELECT ts FROM `{$txt}_changelog` WHERE txtver_id='$verid' $excl ORDER BY ts DESC LIMIT 1"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		while ($ret[] =  mysqli_fetch_assoc($dbresult));
		array_pop($ret);
		return $ret[0]['ts'];
	}
	
# Get alignments by status
	function get_alignments_by_status($stat,$tname='') {
		$ret = array();
		if ($tname!='') $bytname = "t.name='$tname' AND "; else $bytname='';
		if (!$dbresult = mysqli_query($this->DB,"SELECT a.*, t.name as text_name, t.id as text_id, v1.version_name as v1_name, v1.id as v1_id, v2.version_name as v2_name, v2.id as v2_id FROM alignments as a, versions as v1, versions as v2, texts as t WHERE {$bytname}a.status=$stat AND v1.id=a.ver1_id AND v2.id=a.ver2_id AND t.id=v1.text_id ORDER BY t.name, v1.version_name"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		while ($ret[] =  mysqli_fetch_assoc($dbresult));
		array_pop($ret);
		return $ret;
	}

# Get aligned items
	function get_aligned_items($txt,$aid,$pos,$limit=200,$maxstatus=STATUS_PLAIN) {
		$ret = array();
		if ($limit==0) $limitc='';
		else  $limitc=' AND l.position<='.strval($pos+$limit-1);
		$query = "SELECT l.id as link_id, l.position as link_position, l.status as link_status, l.mark as link_mark,e.*, (SELECT count(id) FROM `{$txt}_changelog` WHERE element_id=l.element_id) as changes FROM `{$txt}_links` as l, `{$txt}_elements` as e WHERE (l.alignment_id=$aid AND e.id=l.element_id AND e.txtver_id=l.version_id AND status <= $maxstatus AND l.position>=$pos $limitc) ORDER BY l.position, e.txt_position";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB)."\nQuery: ".$query);
		while ($row =  mysqli_fetch_assoc($dbresult)) { $ret[$row['link_position']][$row['txtver_id']][] = $row; }
		return $ret;
	}

# Get changelog
	function get_changelog($txt,$id,$since='',$skipopen=false, $userid=0, $is_assoc=false) {
		//$al = $this->alignment_info($aid);
		//$txt = $al['text_id'];
		$ret = array();
		if ($since!='')
			$sincecond = "AND ts>='$since'";
		else
			$sincecond = '';
		if ($skipopen)
			$app = "AND !(userid='$userid' AND open)";
		else
			$app = '';
		$query = "SELECT * FROM `{$txt}_changelog` WHERE (element_id='$id' $sincecond $app) ORDER BY ts DESC";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB)."\nQuery: ".$query);
		while ($row =  mysqli_fetch_assoc($dbresult)) {
			if (!$is_assoc && ($row['chtype']=='D' || $row['chtype']=='X'))
                            break;
			if ($row['chtype']=='M')
				$row['assoc'] = $this->get_changelog($txt, $this->get_id_by_assoc_id($txt, $row['id']), $since, $skipopen, $userid, true);
			$ret[] = $row; 
		}
		return $ret;
	}

# Get alignment changelog
  function get_alignment_changelog($al) {
    $txt = $al['text_id'];
    $ret = array();
    if (!$al['alchangelog'])
      return $ret;
    $query = "SELECT c.*, IF(l.version_id, l.version_id, c.txtver_id) as version_id FROM `{$txt}_align_changelog` as c LEFT JOIN `{$txt}_links` as l ON (l.id=c.link_id) WHERE c.alignment_id='{$al['id']}' ORDER BY ts ASC";
    if (!$dbresult = mysqli_query($this->DB,$query))
      $this->_fail("Cannot access database: ".mysqli_error($this->DB)."\nQuery: ".$query);
    while ($row =  mysqli_fetch_assoc($dbresult)) {
      $ret[] = $row; 
    }
    return $ret;
  }

# Get complete list of changes since a given timestamp
	function get_changes_since($ver,$since,$skipopen=false, $userid=0) {
//	function get_changes_since($aid,$ver,$since,$skipopen=false, $userid=0) {
		$ret = array();
		/*$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		if ($ver==$al['ver1_name'])
			$vid = $al['ver1_id'];
		else if ($ver==$al['ver2_name'])
			$vid = $al['ver2_id'];
		else {//print $ver.' '.$al['ver1_name'].' '.$al['ver2_name'];
			return false;}*/
		/*$txt = text_id_by_name($tname);
		if ($txt===FALSE) return false;
		$ver = $this->txtver_info($txt,$vname);
		if ($ver===FALSE) return false;*/
		$txt = $ver['text_id'];
		$vid = $ver['id'];
		foreach(explode(' ',$ver['text_elements']) as $element) $alignables[]="e.element_name='$element'";
		$alignable = join(' OR ',$alignables);
		if ($skipopen)
			$app = "AND !(c.userid='$userid' AND c.open)";
		else
			$app = '';
		$query = "SELECT e.id as eid, e.contents as contents, e.position as position, (SELECT count(id) FROM `{$txt}_changelog` c WHERE c.element_id=e.id AND c.ts>'$since' $app) as changes FROM `{$txt}_elements` as e WHERE (e.txtver_id='$vid' AND ($alignable)) ORDER BY e.txt_position";
		//$query = "SELECT e.id as eid, e.contents as contents, e.position as position, (SELECT count(id) FROM `{$txt}_changelog` c WHERE c.element_id=l.element_id AND c.ts>'$since' $app) as changes FROM `{$txt}_links` as l, `{$txt}_elements` as e WHERE (l.alignment_id='$aid' AND l.version_id='$vid' AND e.id=l.element_id AND e.txtver_id='$vid') ORDER BY l.position, e.txt_position";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB)."\nQuery: ".$query);
		$i=0;
		while ($row =  mysqli_fetch_assoc($dbresult)) {
			$rec = array();
			if ($row['changes']>0) {
				$chl = $this->get_changelog($txt,$row['eid'],$since,true,$userid);
				if (count($chl)!=0) {
					$rec['n'] = $i;
					$rec['contents'] = $row['contents'];
					$rec['repl'] = $this->count_changelog_repl($chl);
					if ($row['position']==1)
						$rec['parbr'] = 'o';
					else
						$rec['parbr'] = '';
					$ret[] = $rec;
				}
			}
			$i++;
		}
		return $ret;
	}

// # Get a single element by link/count
// 	function get_element_by_linkcount($aid,$ver,$cnt, $lastsync='') {
// 		$ret = array();
// 		$al = $this->alignment_info($aid);
// 		$txt = $al['text_id'];
// 		if ($ver==$al['ver1_name'])
// 			$vid = $al['ver1_id'];
// 		else if ($ver==$al['ver2_name'])
// 			$vid = $al['ver2_id'];
// 		else
// 			return false;
// 		$query = "SELECT e.id as eid, e.contents as contents, e.position as position FROM `{$txt}_links` as l, `{$txt}_elements` as e WHERE (l.alignment_id='$aid' AND l.version_id='$vid' AND e.id=l.element_id AND e.txtver_id='$vid') ORDER BY l.position, e.txt_position LIMIT 1 OFFSET $cnt";
// 		if (!$dbresult = mysqli_query($this->DB,$query))
// 			$this->_fail("Cannot access database: ".mysqli_error($this->DB)."\nQuery: ".$query);
// 		$i=0;
// 		while ($row =  mysqli_fetch_assoc($dbresult)) {
// 			$ret[] = $row;
// 		}
// 		return $ret;
// 	}

# Get a single element by link/count (without using alignment ID)
	function get_textelement_by_linkcount($tname,$ver,$cnt, $lastsync='') {
		$ret = array();
		$txt = $this->text_id_by_name($tname);
		if ($txt===FALSE) return false;
		$ver = $this->txtver_info($txt,$ver);
		if ($ver===FALSE) return false;
		$txt_elements = explode(' ',$ver['text_elements']);
		foreach($txt_elements as $element) $alignables[]="element_name='$element'";
		$alignable = join(' OR ',$alignables);
		$query = "SELECT e.id as eid, e.contents as contents, e.position as position FROM `{$txt}_elements` as e WHERE e.txtver_id='{$ver['id']}' AND ($alignable) ORDER BY e.txt_position LIMIT 1 OFFSET $cnt";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB)."\nQuery: ".$query);
		$i=0;
		while ($row =  mysqli_fetch_assoc($dbresult)) {
			$ret[] = $row;
		}
		return $ret;
	}

# Count in the changelog, how many original elements have been replaced by the current one
	function count_changelog_repl($chl) {
		$cnt = 0; $myval = 1;
		foreach ($chl as $ch) {
			if ($ch['chtype']=="M") {
				$cnt += $this->count_changelog_repl($ch['assoc']);}
			else if ($ch['chtype']=='I')
				$myval=0;
		}
		return $myval+$cnt;
	}

# Get element ID of associated change
	function get_id_by_assoc_id($txt, $assoc_id) {
		$query = "SELECT element_id FROM `{$txt}_changelog` WHERE assoc_id='$assoc_id' ORDER BY ts DESC";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB)."\nQuery: ".$query);
		$row =  mysqli_fetch_assoc($dbresult);
		return $row['element_id'];
	}

# Get text version id by change_id
	function get_change($aid,$chid) {
		$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		$query = "SELECT * FROM `{$txt}_changelog` WHERE id=$chid";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB)."\nQuery: ".$query);
		if ($change = mysqli_fetch_assoc($dbresult)) {
			return $change;
		} else return false;
	}

# Revert text to some previous stadium (not a real UNDO!)
	function revert_change($aid, $chid, $userid=0) {
		$al = $this->alignment_info($aid); $txt = $al['text_id'];
		if ($change = $this->get_change($aid,$chid)) {
			return $this->update_element_text($txt,$change['element_id'],$change['old_contents'],$userid);
		} else return false;
	}

# Get first link position with given status
	function get_pos_by_status($aid,$minstatus) {
		$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		if (!$dbresult = mysqli_query($this->DB,"SELECT min(position) as pos FROM `{$txt}_links` WHERE (alignment_id='$aid' AND status>='$minstatus')"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		$ret = mysqli_fetch_assoc($dbresult);
		if ($ret['pos']!='') {
			return $ret['pos'];
		} else return FALSE;
	}
	
# Get position of next mark
	function get_next_mark($aid,$mypos,$dirup) {
		$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		if ($dirup) $query = "SELECT position as pos FROM `{$txt}_links` WHERE alignment_id=$aid AND mark>0 AND position>$mypos ORDER BY position ASC LIMIT 1";
		else $query = "SELECT position as pos FROM `{$txt}_links` WHERE alignment_id=$aid AND mark>0 AND position<$mypos ORDER BY position DESC LIMIT 1";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		$ret = mysqli_fetch_assoc($dbresult);
		if ($ret['pos']!='') {
			return $ret['pos'];
		} else return FALSE;
	}

# Get txt version by link id
	function get_ver_by_linkid($txt,$lid) {
		if (!$dbresult = mysqli_query($this->DB,"SELECT version_id FROM `{$txt}_links` WHERE id=$lid"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) {
			$ret = mysqli_fetch_assoc($dbresult);
			return $ret['version_id'];
		} else return FALSE;
	}

# Get info about link
	function get_link_info($txt,$lid) {
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_links` WHERE id=$lid"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) {
			$ret = mysqli_fetch_assoc($dbresult);
			return $ret;
		} else return FALSE;
	}

# Get txt version by element id
	function get_ver_by_id($txt,$id) {
		if (!$dbresult = mysqli_query($this->DB,"SELECT txtver_id FROM `{$txt}_elements` WHERE id=$id"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) {
			$ret = mysqli_fetch_assoc($dbresult);
			return $ret['txtver_id'];
		} else return FALSE;
	}

# Decrement position of alignment link
	function alitem_pos_dec($txt,$aid,$id,$pos,$userid=0) {
		if ($pos==1) return FALSE;
		# Prevent cross-linking of elements! (check for another el. at the same position with a lower txt_position)
		$query = "SELECT e2.id FROM `{$txt}_links` as l1, `{$txt}_links` as l2, `{$txt}_elements` as e1, `{$txt}_elements` as e2 WHERE l1.id=$id AND l2.position=l1.position AND l1.alignment_id=l2.alignment_id AND l2.version_id=l1.version_id AND l2.id!=l1.id AND e1.id=l1.element_id AND e2.id=l2.element_id AND e1.txt_position>e2.txt_position";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		# (if there are such elements, give up and just return)
		if (mysqli_num_rows($dbresult)) return;
		# Update
		if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_links` SET position=position-1 WHERE id=$id"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if ($this->auto_status_update) $this->update_manual_status($txt,$aid,$pos-1);
		$this->log_align_change($txt, $aid, 0, $id, $pos, 'S', $userid);
	}

# Increment position of alignment link
	function alitem_pos_inc($txt,$aid,$id,$pos,$userid=0) {
		# Prevent cross-linking of elements! (check for another el. at the same position with a higher txt_position)
		$query = "SELECT e2.id FROM `{$txt}_links` as l1, `{$txt}_links` as l2, `{$txt}_elements` as e1, `{$txt}_elements` as e2 WHERE l1.id=$id AND l2.position=l1.position AND l1.alignment_id=l2.alignment_id AND l2.version_id=l1.version_id AND l2.id!=l1.id AND e1.id=l1.element_id AND e2.id=l2.element_id AND e1.txt_position<e2.txt_position";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		# (if there are such elements, give up and just return)
		if (mysqli_num_rows($dbresult)) return;
		# Update
		if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_links` SET position=position+1 WHERE id=$id"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if ($this->auto_status_update) $this->update_manual_status($txt,$aid,$pos);
		$this->log_align_change($txt, $aid, 0, $id, $pos, 'P', $userid);
	}

# Move all version links one position down
	function alver_dec($txt,$aid,$ver,$pos,$userid=0) {
		if ($pos==1) return FALSE;
		if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_links` SET position=position-1 WHERE alignment_id=$aid AND version_id=$ver AND position>=$pos"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if ($this->auto_status_update) $this->update_manual_status($txt,$aid,$pos-1);
		$this->log_align_change($txt, $aid, $ver, 0, $pos, 'D', $userid);
	}

# Move all version links one position up
	function alver_inc($txt,$aid,$ver,$pos,$userid=0) {
		if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_links` SET position=position+1 WHERE alignment_id=$aid AND version_id=$ver AND position>=$pos"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if ($this->auto_status_update) $this->update_manual_status($txt,$aid,$pos);
		$this->log_align_change($txt, $aid, $ver, 0, $pos, 'U', $userid);
	}

# Move all links one position down (both versions)
	function alpos_dec($txt,$aid,$pos,$userid=0) {
		if ($pos==1) return FALSE;
		if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_links` SET position=position-1 WHERE alignment_id=$aid AND position>=$pos"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if ($this->auto_status_update) $this->update_manual_status($txt,$aid,$pos-1);
		$this->log_align_change($txt, $aid, 0, 0, $pos, 'M', $userid);
	}

# Move all links one position up (both versions)
	function alpos_inc($txt,$aid,$pos,$userid=0) {
		if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_links` SET position=position+1 WHERE alignment_id=$aid AND position>=$pos"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if ($this->auto_status_update) $this->update_manual_status($txt,$aid,$pos);
		$this->log_align_change($txt, $aid, 0, 0, $pos, 'I', $userid);
	}

# Update status to STATUS_MANUAL up to the given position
	function update_manual_status($txt,$aid,$pos) {
		if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_links` SET status=".STATUS_MANUAL." WHERE alignment_id=$aid AND position<=$pos AND (status=".STATUS_PLAIN." OR status=".STATUS_AUTOMATIC.")"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
	}

# Check for brokem XML elements in text
	function check_text($text,$debug=FALSE) {
		# XMLreader error reporting is strange...let's just let the user choose...
		if (!$debug) $ers = error_reporting(0);
		libxml_use_internal_errors(TRUE);
		$xml = XMLReader::xml("<?xml version=\"1.0\" encoding=\"UTF-8\"?><text>$text</text>\n");
		if (!$xml->read()) {
			if (!$debug) $ers = error_reporting($ers);// else $this->report_libxml_errors();
			$this->_failure('Invalid XML!');
			return FALSE; 
		}	
		while($xml->nodeType) { 
			if ((!$ret=$xml->read()) && ($xml->nodeType)) {
				if (!$debug) $ers = error_reporting($ers); //else $this->report_libxml_errors();
				$this->_failure('Invalid XML!');
				return FALSE; 
			}
		}
		$xml->close();
		if (!$debug) $ers = error_reporting($ers);
		return TRUE;
	}

	function report_libxml_errors() {
        $arErrors = libxml_get_errors();
        $xml_errors = "";
        foreach ($arErrors AS $xmlError) $xml_errors .= $xmlError->message;
        if ($xml_errors != "") {
          error_log("XML errors:\n".$xml_errors."\n");
        }
	}
	
# Update text of an element
	function update_element_text($txt,$id,$text,$userid=0,$change='E',$assoc='NULL', $letopen=false) {
		if (!$dbresult = mysqli_query($this->DB,"START TRANSACTION")) {
			$this->_failure("Cannot start transaction: ".mysqli_error($this->DB));
			return FALSE;
		}
		if ($this->_update_element_text($txt,$id,$text,$userid,$change,$assoc, $letopen)) {
			if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
				$this->_failure("Cannot commit transaction: ".mysqli_error($this->DB));
				return FALSE;
			}
			return TRUE;
		} else
			return FALSE;
	}
	
	function _update_element_text($txt,$id,$text,$userid=0,$change='E',$assoc='NULL', $letopen=false) {
		$change_id = false;
		# Check for brokem XML elements...
		if (!$this->check_text($text)) {
			return FALSE;
		}
		# get info and old contents
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (id='{$id}')")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
			return FALSE;
		}
		if (mysqli_num_rows($dbresult)) $el = mysqli_fetch_assoc($dbresult);
		else  {
			$this->_failure("Element not found!");
			return FALSE;
		}
		# Log the change
		$change_id = $this->log_edit_change($txt, $el['txtver_id'], $id, $assoc, $change, $userid, $el['contents'], $letopen);
		if ($change_id===FALSE) {
			return FALSE;
		}
		# Update...
		if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET contents='".mysqli_real_escape_string($this->DB,$text)."' WHERE id=$id")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Mark text as changed
		if (!$dbresult = mysqli_query($this->DB,"UPDATE versions SET text_changed=TRUE WHERE text_id=$txt AND id={$el['txtver_id']}")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
			return FALSE;
		}
		return $change_id;
	}
	
# Reset remote changes as closed (fully comitted)
	function close_updates($txt, $verid, $userid) {
		global $LOG_EDIT_CHANGES;
		if ($LOG_EDIT_CHANGES==true) {
			if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_changelog` SET open=0 WHERE txtver_id='$verid' AND userid='$userid'"))
				$this->_fail("Cannot access database: ".mysqli_error($this->DB));
			return 1;
		} else return 0;
	}

# Log change of text
	function log_edit_change($txt, $version, $element_id, $assoc, $change, $userid, $old_contents, $open=false) {
		global $LOG_EDIT_CHANGES,$_ERROR;
		if ($LOG_EDIT_CHANGES==true) {
			if ($open) $openval=1; else $openval=0;
			$values = "$version, $element_id, $assoc, '$change', $userid, '".mysqli_real_escape_string($this->DB,$old_contents)."', now(), $openval";
			if (!$dbresult = mysqli_query($this->DB,"INSERT INTO `{$txt}_changelog` (txtver_id,element_id,assoc_id,chtype,userid,old_contents,ts,open) VALUES ($values)")) {
				$this->_failure("Cannot access database: ".mysqli_error($this->DB));//$_ERROR.="mysqlid:".mysqli_insert_id($this->DB);
				return FALSE;
			}
			return mysqli_insert_id($this->DB);
		} else return 0;
	}
	
# Log change of alignment
	function log_align_change($txt, $aid, $ver, $id, $pos, $type, $userid=0) {
		global $LOG_ALIGN_CHANGES;
		if ($LOG_ALIGN_CHANGES==true) {
$table_align_changelog = "CREATE TABLE IF NOT EXISTS `{$txt}_align_changelog` (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	alignment_id BIGINT UNSIGNED,
	txtver_id BIGINT UNSIGNED,
	link_id BIGINT UNSIGNED,
	position BIGINT UNSIGNED,
	chtype CHAR(1) NOT NULL,
	userid INT UNSIGNED,
	ts DATETIME NOT NULL,
	
	PRIMARY KEY(id),
	INDEX index_txtver_id (txtver_id),
	INDEX index_alignment_id (alignment_id),
	INDEX index_chtype (chtype),
	INDEX index_userid (userid),
	INDEX index_ts (ts)
)";
			if (!$dbresult = mysqli_query($this->DB,"SET storage_engine=InnoDB"))
				$this->_fail("Cannot set default storage engine to InnoDB: ".mysqli_error($this->DB));
			if (!$dbresult = mysqli_query($this->DB,$table_align_changelog))
				$this->_fail("Cannot create new alignment changelog table in the database: ".mysqli_error($this->DB));
			$values = "$aid, $ver, $id, $pos, '$type', $userid, now()";
			if (!$dbresult = mysqli_query($this->DB,"INSERT INTO `{$txt}_align_changelog` (alignment_id, txtver_id, link_id, position, chtype, userid, ts) VALUES ($values)"))
				$this->_fail("Cannot insert into alignment changelog table: ".mysqli_error($this->DB));
			return mysqli_insert_id($this->DB);
		} else return 0;
	}

# Split element (given id and array of parts (strings))
	function split_element($txt,$id,$parts,$userid=0, $letopen=false) {
		# We need at least 2 string parts to make a split
		if (count($parts)<2) return FALSE;
		if (!$dbresult = mysqli_query($this->DB,"START TRANSACTION")) {
			$this->_failure("Cannot start transaction: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Find out more about the element we are going to split
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (id='{$id}')")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
			return FALSE;
		}
		if (mysqli_num_rows($dbresult)) $el = mysqli_fetch_assoc($dbresult);
		else {
			$this->_failure("Element not found!");
			return FALSE;
		}
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_links` WHERE (element_id='{$id}')")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
			return FALSE;
		}
		$links=array();
		while ($links[] = mysqli_fetch_assoc($dbresult));
		# Check for brokem XML elements...
		foreach ($parts as $part) { if (!$this->check_text($part)) return FALSE;}
		reset($parts);
		# The first part will just replace the old element contents
		$assoc = $this->_update_element_text($txt,$id,array_shift($parts),$userid,'S','NULL', $letopen);
		if ($assoc===FALSE) {
			return FALSE;
		}
		if (!$this->set_uniq_ids($txt,$el['txtver_id'],false))
			return FALSE;
		# Now shift all siblings up to make place for the new segments
		$pos=$el['position']; $txtpos=$el['txt_position'];
		if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET position=position+".count($parts)." WHERE txtver_id={$el['txtver_id']} AND parent={$el['parent']} AND position>$pos")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
			return FALSE;
		}
		# And shift txt_position of all elements upwards
		if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET txt_position=txt_position+".(count($parts)*2)." WHERE txtver_id={$el['txtver_id']} AND txt_position>$txtpos")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Now create the new elements and their link items
		foreach($parts as $newel) {
			$txtpos++;
			$wsid = $this->_insert_element($txt,$el['txtver_id'],$txtpos,$el['parent'],$pos,'__WS__','','',"\n");
			if ($wsid===FALSE) {
				return FALSE;
			}
			$pos++; $txtpos++; $newel = preg_replace("/\n/",' ',$newel);
			$newid = $this->_insert_element($txt,$el['txtver_id'],$txtpos,$el['parent'],$pos,$el['element_name'],'','',$newel);
			if ($newid===FALSE) {
				return FALSE;
			}
			foreach($links as $link)
				if ($link['alignment_id']) {
					if ($this->_new_link_item($txt,$link['alignment_id'],$link['version_id'],$newid,$link['position'],$link['status'])===FALSE)
						return FALSE;
				}
			if ($this->log_edit_change($txt, $el['txtver_id'], $newid, $assoc, 'I', $userid, '', $letopen)===FALSE)
				return FALSE;
		}
		if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
			$this->_failure("Cannot commit transaction: ".mysqli_error($this->DB));
			return FALSE;
		}
		return TRUE;
	}

# Check possibility of a (multi)merge (for API.php)
	function merge_possible($txt, $excludeaid, $id, $count=1) {
		global $_ERROR,$MERGE_UNCONFIRMED_FREELY;
		# Find out more about the first element we are going to merge
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (id='{$id}')"))
			$this->_fail("Cannot access database1: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) $el1 = mysqli_fetch_assoc($dbresult);
		else $this->_fail("Element '$id' not found!");
		# Find the last alignable element
		$ver = $this->txtver_by_id($el1['txtver_id']);
		$txt_elements = explode(' ',$ver['text_elements']);
		foreach($txt_elements as $element) $alignables[]="element_name='$element'";
		$alignable = join(' OR ',$alignables);
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (txtver_id={$el1['txtver_id']} AND txt_position>'{$el1['txt_position']}' AND ($alignable)) ORDER BY txt_position LIMIT $count"))
			$this->_fail("Cannot access database2: ".mysqli_error($this->DB));
		while ($ret = mysqli_fetch_assoc($dbresult)) { $el2 = $ret; };
		# Are the two elements siblings or at least their parents?
		$parental=FALSE;
		if ($el1['parent']!=$el2['parent']) {
			if (!$dbresult = mysqli_query($this->DB,"SELECT parent, position FROM `{$txt}_elements` WHERE (id='{$el1['parent']}' OR id='{$el2['parent']}') ORDER BY txt_position"))
				$this->_fail("Cannot access database3a: ".mysqli_error($this->DB));
			while ($p[] = mysqli_fetch_assoc($dbresult));
			# We cannot merge unrelated elements!
			if ($p[0]['parent']!=$p[1]['parent']) {
				$_ERROR = "Error: Cannot merge elements in so complicated relations. The parents of these elements must be siblings.";
				return FALSE;
			}	else $parental=TRUE;
		}
		# Are the two elements not linked to different elements in some alignment?
		$ignoreunconfirmed = '';
		if ($MERGE_UNCONFIRMED_FREELY)
			$ignoreunconfirmed = ' AND (l1.status='.STATUS_MANUAL.' OR l2.status='.STATUS_MANUAL.')';
		if (!$dbresult = mysqli_query($this->DB,"SELECT v1.version_name as v1, v2.version_name as v2, a.id as aid, l1.position as p1, l2.position as p2 FROM `{$txt}_links` as l1, `{$txt}_links` as l2, alignments as a, versions as v1, versions as v2  WHERE (a.id!= $excludeaid AND l1.element_id={$el1['id']} AND l2.element_id={$el2['id']} AND l1.alignment_id=l2.alignment_id AND l1.position!=l2.position AND a.id=l1.alignment_id AND v1.id=a.ver1_id AND v2.id=a.ver2_id$ignoreunconfirmed)"))
			$this->_fail("Cannot access database3b: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) {
			$locs = array();
			while ($cal = mysqli_fetch_assoc($dbresult)) { $locs[] = "alignment '{$cal['v1']} - {$cal['v2']}' ({$cal['aid']}) at positions {$cal['p1']}/{$cal['p2']}"; }
			$_ERROR = "Error: Cannot merge elements linked to different positions (segments). Please correct manually: ".join(' and ',$locs).'.';
			return FALSE;
		}	
		return TRUE;
	}

# Merge two elements (the given one and the following one)
	function merge_elements($txt,$id,$userid=0, $letopen=false, $exclude_al=-1) {
		global $_ERROR,$MERGE_UNCONFIRMED_FREELY;
		if (!$dbresult = mysqli_query($this->DB,"START TRANSACTION")) {
			$this->_failure("Cannot start transaction: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Find out more about the first element we are going to merge
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (id='{$id}')")) {
			$this->_failure("Cannot access database1: ".mysqli_error($this->DB));
			return FALSE;
		}
		if (mysqli_num_rows($dbresult)) $el1 = mysqli_fetch_assoc($dbresult);
		else {
			$this->_failure("Element '$id' not found!");
			return FALSE;
		}
		# Find the next alignable element
		$ver = $this->txtver_by_id($el1['txtver_id']);
		if ($ver===FALSE) {
			return FALSE;
		}
		$txt_elements = explode(' ',$ver['text_elements']);
		foreach($txt_elements as $element) $alignables[]="element_name='$element'";
		$alignable = join(' OR ',$alignables);
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (txtver_id={$el1['txtver_id']} AND txt_position>'{$el1['txt_position']}' AND ($alignable)) ORDER BY txt_position LIMIT 1")) {
			$this->_failure("Cannot access database2: ".mysqli_error($this->DB));
			return FALSE;
		}
		if (mysqli_num_rows($dbresult)) $el2 = mysqli_fetch_assoc($dbresult);
#print $el1['id']." <> ".$el2['id']."\n";
		# Are the two elements siblings or at least their parents?
		$parental=FALSE;
		if ($el1['parent']!=$el2['parent']) {
#print "Different parents!! {$el1['parent']} and {$el1['parent']}\n";
			if (!$dbresult = mysqli_query($this->DB,"SELECT parent, position FROM `{$txt}_elements` WHERE (id='{$el1['parent']}' OR id='{$el2['parent']}') ORDER BY txt_position")) {
				$this->_failure("Cannot access database3a: ".mysqli_error($this->DB));
				return FALSE;
			}
			while ($p[] = mysqli_fetch_assoc($dbresult));
#print "Parent's parents are {$p[0]['parent']} and {$p[1]['parent']}\n";
			# We cannot merge unrelated elements!
			if ($p[0]['parent']!=$p[1]['parent']) {
				$this->_failure("Error: Cannot merge elements in so complicated relations. The parents of these elements must be siblings.");
				return FALSE;
			}	else $parental=TRUE;
		}
#if ($parental) print "YES\n"; else print "NO\n";
		# Are the two elements not linked to different elements in some alignment?
		if ($exclude_al>0) $excl="a.id!=$exclude_al AND "; else $excl='';
		$ignoreunconfirmed = '';
		if ($MERGE_UNCONFIRMED_FREELY)
			$ignoreunconfirmed = ' AND (l1.status='.STATUS_MANUAL.' OR l2.status='.STATUS_MANUAL.')';
		if (!$dbresult = mysqli_query($this->DB,"SELECT v1.version_name as v1, v2.version_name as v2, a.id as aid, l1.position as p1, l2.position as p2 FROM `{$txt}_links` as l1, `{$txt}_links` as l2, alignments as a, versions as v1, versions as v2  WHERE ({$excl}l1.element_id={$el1['id']} AND l2.element_id={$el2['id']} AND l1.alignment_id=l2.alignment_id AND l1.position!=l2.position AND a.id=l1.alignment_id AND v1.id=a.ver1_id AND v2.id=a.ver2_id$ignoreunconfirmed)")) {
			$this->_failure("Cannot access database3b: ".mysqli_error($this->DB));
			return FALSE;
		}
#print "P1\n";
		if (mysqli_num_rows($dbresult)) {
			$locs = array();
			while ($cal = mysqli_fetch_assoc($dbresult)) { $locs[] = "alignment '{$cal['v1']} - {$cal['v2']}' ({$cal['aid']}) at positions {$cal['p1']}/{$cal['p2']}"; }
			#print "go to failure\n".join(' and ',$locs).'.';
			$this->_failure("Error: Cannot merge elements linked to different positions (segments). Please correct manually: ".join(' and ',$locs).'.');
			return FALSE;
		}	
#print "P2\n";
		# Update the text of the merged element_id
		$assoc = $this->_update_element_text($txt,$id,$el1['contents'].' '.$el2['contents'],$userid,'M', 'NULL', $letopen);
		if ($assoc===FALSE) {
			return FALSE;
		}
		if (!$this->set_uniq_ids($txt,$el1['txtver_id'],false))
			return FALSE;
		# Backup the element to be deleted
		if ($parental) $change = 'X'; else $change='D';
		if ($this->log_edit_change($txt, $el2['txtver_id'], $el2['id'], $assoc, $change, $userid, $el2['contents'], $letopen)===FALSE)
			return FALSE;
		# Now delete all elements up to the next element!
		if (!mysqli_query($this->DB,"DELETE FROM `{$txt}_elements` WHERE (txtver_id={$el1['txtver_id']} AND txt_position>'{$el1['txt_position']}' AND txt_position<='{$el2['txt_position']}')")) {
			$this->_failure("Cannot access database4: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Now, remove the links of the deleted element
#print "Removin links...\n";
		if (!mysqli_query($this->DB,"DELETE FROM `{$txt}_links` WHERE (element_id={$el2['id']})")) {
			$this->_failure("Cannot access database5: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Decrease the position of the following elements
#print "Decreasing position...\n";
		$diff=$el2['txt_position']-$el1['txt_position'];
		if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET txt_position=txt_position-$diff WHERE txtver_id={$el1['txtver_id']} AND txt_position>{$el1['txt_position']}")) {
			$this->_failure("Cannot access database6: ".mysqli_error($this->DB));
			return FALSE;
		}
		# if we deleted the parent of the second node, we need to connect its siblings to the new parent
		if ($parental) {
#print "Reconnecting parent...\n";
			if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET parent={$el1['parent']} WHERE parent={$el2['parent']}")) {
				$this->_failure("Cannot access database7: ".mysqli_error($this->DB));
				return FALSE;
			}
			# And update positions of the parent's siblings
#print "Updating parents order...\n";
			if (!mysqli_query($this->DB,"SET @cnt=0")) { $this->_failure("Cannot access database: ".mysqli_error($this->DB)); return FALSE; }
			if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET position=(@cnt:=@cnt+1) WHERE (txtver_id={$el2['txtver_id']} AND parent={$p[0]['parent']}) ORDER BY txt_position")) {
				$this->_failure("Cannot access database8: ".mysqli_error($this->DB));
				return FALSE;
			}
		}
		# Finally, update relative positions of the new siblings
#print "Updating sibling order...\n";
		if (!mysqli_query($this->DB,"SET @cnt=0")) { $this->_failure("Cannot access database: ".mysqli_error($this->DB)); return FALSE; }
		if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET position=(@cnt:=@cnt+1) WHERE (txtver_id={$el2['txtver_id']} AND parent={$el1['parent']} AND substr(element_name,1,2)!='__') ORDER BY txt_position")) {
			$this->_failure("Cannot access database9: ".mysqli_error($this->DB));
			return FALSE;
		}
		if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
			$this->_failure("Cannot commit transaction: ".mysqli_error($this->DB));
			return FALSE;
		}
		return TRUE;
	}

# Merge parents (of the given one and the preceding one), i.e. delete parent break at the given element
	function merge_parents($txt,$id,$userid=0, $letopen=false) {
		global $_ERROR;
		if (!$dbresult = mysqli_query($this->DB,"START TRANSACTION")) {
			$this->_failure("Cannot start transaction: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Find out more about the element
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (id='{$id}')")) {
			$this->_failure("Cannot access database1: ".mysqli_error($this->DB));
			return FALSE;
		}
		if (mysqli_num_rows($dbresult)) $el1 = mysqli_fetch_assoc($dbresult);
		else {
			$this->_failure("Element '$id' not found!");
			return FALSE;
		}
		# Find the previous alignable element
		$ver = $this->txtver_by_id($el1['txtver_id']);
		if ($ver===FALSE) {
			return FALSE;
		}
		$txt_elements = explode(' ',$ver['text_elements']);
		foreach($txt_elements as $element) $alignables[]="element_name='$element'";
		$alignable = join(' OR ',$alignables);
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (txtver_id={$el1['txtver_id']} AND txt_position<'{$el1['txt_position']}' AND ($alignable)) ORDER BY txt_position DESC LIMIT 1")) {
			$this->_failure("Cannot access database2: ".mysqli_error($this->DB));
			return FALSE;
		}
		if (mysqli_num_rows($dbresult)) $el2 = mysqli_fetch_assoc($dbresult);
		else {
			$this->_failure("Error: This is the start of the text.");
			return FALSE;
		}
		if ($el1['parent']==$el2['parent']) {
			$this->_failure("Error: The element is not the first child of its parent.");
			return FALSE;
		}
		# Are the two parents siblings?
		if (!$dbresult = mysqli_query($this->DB,"SELECT parent, position, txt_position FROM `{$txt}_elements` WHERE (id='{$el1['parent']}' OR id='{$el2['parent']}') ORDER BY txt_position")) {
			$this->_failure("Cannot access database3a: ".mysqli_error($this->DB));
			return FALSE;
		}
		while ($p[] = mysqli_fetch_assoc($dbresult));
		# We cannot merge unrelated elements!
		if ($p[0]['parent']!=$p[1]['parent']) {
			$this->_failure("Error: Cannot merge elements in so complicated relations. The parents must be siblings.");
			return FALSE;
		}
		if (!$this->set_uniq_ids($txt,$el1['txtver_id'],false))
			return FALSE;
		# Log the change
		$change = 'R';
		if ($this->log_edit_change($txt, $el1['txtver_id'], $el1['id'], 'NULL', $change, $userid, '', $letopen)===FALSE)
			return FALSE;
		# Now delete the parent
		if (!mysqli_query($this->DB,"DELETE FROM `{$txt}_elements` WHERE id={$el1['parent']}")) {
			$this->_failure("Cannot access database4a: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Delete whitespace
		if (!mysqli_query($this->DB,"DELETE FROM `{$txt}_elements` WHERE (parent={$el1['parent']} AND txt_position=({$p[1]['txt_position']}+1) AND element_name='__WS__')")) {
			$this->_failure("Cannot access database4b: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Delete possible elements/whitespace inbetween the parents
		if (!mysqli_query($this->DB,"DELETE FROM `{$txt}_elements` WHERE (txtver_id={$el2['txtver_id']} AND parent={$p[1]['parent']} AND txt_position<{$p[1]['txt_position']} AND txt_position>{$p[0]['txt_position']})")) {
			$this->_failure("Cannot access database5: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Decrease the position of the following elements
		if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET txt_position=txt_position-1 WHERE txtver_id={$el1['txtver_id']} AND txt_position>{$p[1]['txt_position']}")) {
			$this->_failure("Cannot access database6: ".mysqli_error($this->DB));
			return FALSE;
		}
		# connect elements to the new parent
		if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET parent={$el2['parent']} WHERE parent={$el1['parent']}")) {
			$this->_failure("Cannot access database7: ".mysqli_error($this->DB));
			return FALSE;
		}
		# And update positions of the parent's siblings
		if (!mysqli_query($this->DB,"SET @cnt=0")) { $this->_failure("Cannot access database: ".mysqli_error($this->DB)); return FALSE; }
		if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET position=(@cnt:=@cnt+1) WHERE (txtver_id={$el2['txtver_id']} AND parent={$p[0]['parent']}) ORDER BY txt_position")) {
			$this->_failure("Cannot access database8: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Finally, update relative positions of the new siblings
		if (!mysqli_query($this->DB,"SET @cnt=0")) { $this->_failure("Cannot access database: ".mysqli_error($this->DB)); return FALSE; }
		if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET position=(@cnt:=@cnt+1) WHERE (txtver_id={$el2['txtver_id']} AND parent={$el2['parent']} AND substr(element_name,1,2)!='__') ORDER BY txt_position")) {
			$this->_failure("Cannot access database9: ".mysqli_error($this->DB));
			return FALSE;
		}
		if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
			$this->_failure("Cannot commit transaction: ".mysqli_error($this->DB));
			return FALSE;
		}
		return TRUE;
	}

# Splait parent (of the given element just before it), i.e. create new parent break at the given element
	function split_parent($txt,$id,$userid=0, $letopen=false) {
		global $_ERROR;
		if (!$dbresult = mysqli_query($this->DB,"START TRANSACTION")) {
			$this->_failure("Cannot start transaction: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Find out more about the element
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (id='{$id}')")) {
			$this->_failure("Cannot access database1: ".mysqli_error($this->DB));
			return FALSE;
		}
		if (mysqli_num_rows($dbresult)) $el1 = mysqli_fetch_assoc($dbresult);
		else  { 
			$this->_failure("Element '$id' not found!");
			return FALSE;
		}
		if ($el1['position']==1){
			$this->_failure("Error: This element already is the first element of its parent.");
			return FALSE;
		}
		# Find out more about its parent
		if (!$dbresult = mysqli_query($this->DB,"SELECT * FROM `{$txt}_elements` WHERE (id='{$el1['parent']}')")) {
			$this->_failure("Cannot access database1: ".mysqli_error($this->DB));
			return FALSE;
		}
		while ($p[] = mysqli_fetch_assoc($dbresult));
		if (!$this->set_uniq_ids($txt,$el1['txtver_id'],false))
			return FALSE;
		# Log the change
		$change = 'N';
		if ($this->log_edit_change($txt, $el1['txtver_id'], $el1['id'], 'NULL', $change, $userid, '', $letopen)===FALSE)
			return FALSE;
		# Increase the position of the following elements
		if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET txt_position=txt_position+3 WHERE txtver_id={$el1['txtver_id']} AND txt_position>={$el1['txt_position']}")) {
			$this->_failure("Cannot access database2: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Now insert a new the parent
		$wsid = $this->_insert_element($txt,$el1['txtver_id'],($el1['txt_position']),$p[0]['parent'], 0,'__WS__','','',"\n");
		if ($wsid===FALSE) {
			return FALSE;
		}
		$newid = $this->_insert_element($txt,$el1['txtver_id'],$el1['txt_position']+1,$p[0]['parent'],($p[0]['position']+1),$p[0]['element_name'],'',$p[0]['attributes'],'');
		if ($newid===FALSE) {
			return FALSE;
		}
		$wsid = $this->_insert_element($txt,$el1['txtver_id'],($el1['txt_position']+2),$newid,0,'__WS__','','',"\n");
		if ($wsid===FALSE) {
			return FALSE;
		}
		# connect elements to the new parent
		if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET parent=$newid WHERE parent={$el1['parent']} AND txt_position>=({$el1['txt_position']}+2)")) {
			$this->_failure("Cannot access database3: ".mysqli_error($this->DB));
			return FALSE;
		}
		# And update positions of the parent's siblings
		if (!mysqli_query($this->DB,"SET @cnt=0")) { $this->_failure("Cannot access database: ".mysqli_error($this->DB)); return FALSE; }
		if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET position=(@cnt:=@cnt+1) WHERE (txtver_id={$el1['txtver_id']} AND parent={$p[0]['parent']}) ORDER BY txt_position")) {
			$this->_failure("Cannot access database4: ".mysqli_error($this->DB));
			return FALSE;
		}
		# Finally, update relative positions of the new siblings
		if (!mysqli_query($this->DB,"SET @cnt=0")) { $this->_failure("Cannot access database: ".mysqli_error($this->DB)); return FALSE; }
		if (!mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET position=(@cnt:=@cnt+1) WHERE (txtver_id={$el1['txtver_id']} AND parent=$newid AND substr(element_name,1,2)!='__') ORDER BY txt_position")) {
			$this->_failure("Cannot access database5: ".mysqli_error($this->DB));
			return FALSE;
		}
		if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
			$this->_failure("Cannot commit transaction: ".mysqli_error($this->DB));
			return FALSE;
		}
		return TRUE;
	}


# Get names of elements used as containers for aligned elements
	function get_container_names($txt,$docid) {
		$names = array();
		$ver = $this->txtver_by_id($docid);
		$txt_elements = explode(' ',$ver['text_elements']);
		foreach($txt_elements as $element) $alignables[]="e.element_name='$element'";
		$alignable = join(' OR ',$alignables);
		#$query = "SELECT DISTINCT(element_name) as name FROM `{$txt}_elements` WHERE id IN (SELECT DISTINCT(parent) FROM `{$txt}_elements` WHERE txtver_id=$docid AND ($alignable))";
		$query = "SELECT DISTINCT(pe.element_name) as name FROM `{$txt}_elements` as e LEFT JOIN `{$txt}_elements` as pe ON pe.id=e.parent AND pe.txtver_id=e.txtver_id WHERE e.txtver_id=$docid AND ($alignable)";
		if(!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database (container names): ".mysqli_error($this->DB));
		while ($ret = mysqli_fetch_assoc($dbresult)) $names[]=$ret['name'];
		return $names;
	}

# Are given elements nested in given document?
	function are_nested($element_names,$txt,$docid) {
		$elnames=array();
		foreach($element_names as $element) $elnames[]="element_name='$element'";
		$filter = join(' OR ',$elnames);
		$query = "CREATE TEMPORARY TABLE tmp_nesting AS SELECT parent, element_name, id FROM `{$txt}_elements` WHERE (txtver_id={$docid} AND ($filter))";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		$affected=mysqli_affected_rows($this->DB);
		while ($affected>1) {
			mysqli_query($this->DB,"ALTER TABLE tmp_nesting RENAME TO tmp_elements") or $this->_fail("Cannot access database: ".mysqli_error($this->DB));
			mysqli_query($this->DB,"ALTER TABLE tmp_elements ADD INDEX index_parent (parent)") or $this->_fail("Cannot access database: ".mysqli_error($this->DB));
			$query = "CREATE TEMPORARY TABLE tmp_nesting AS SELECT e.parent, e.element_name, e.id FROM `{$txt}_elements` e, tmp_elements p WHERE e.txtver_id={$docid} AND e.id = p.parent";
			mysqli_query($this->DB,$query) or $this->_fail("Cannot access database: ".mysqli_error($this->DB));
			$affected=mysqli_affected_rows($this->DB);
			mysqli_query($this->DB,"DROP TABLE tmp_elements") or $this->_fail("Cannot access database: ".mysqli_error($this->DB));
			$query = "SELECT element_name, id FROM tmp_nesting WHERE $filter";
			$dbresult=mysqli_query($this->DB,$query) or $this->_fail("Cannot access database: ".mysqli_error($this->DB));
			if (mysqli_num_rows($dbresult)) {
				mysqli_query($this->DB,"DROP TABLE tmp_nesting") or $this->_fail("Cannot access database: ".mysqli_error($this->DB));
				return TRUE;
			}
		}
		mysqli_query($this->DB,"DROP TABLE tmp_nesting") or $this->_fail("Cannot drop temp table in database: ".mysqli_error($this->DB));
		return FALSE;
	}
	
# Get text count
  function texts_count($filter=array()) {
    $filter_cond = '';
    if (IsSet($filter['tname']) && $filter['tname']!='') {
      $filter_cond .= "LOCATE('".mysqli_real_escape_string($this->DB,$filter['tname'])."',t.name)>0";
    }
    if (IsSet($filter['vname']) && $filter['vname']!='') {
      $vcond .= "LEFT JOIN versions as v ON v.text_id=t.id WHERE LOCATE('".mysqli_real_escape_string($this->DB,$filter['vname'])."',v.version_name)>0";
      if ($filter_cond!='') $filter_cond = $vcond.' AND '.$filter_cond;
      else $filter_cond = $vcond;
    } else {
      if ($filter_cond!='') $filter_cond = 'WHERE '.$filter_cond;
    }
    if (!$dbresult = mysqli_query($this->DB,"SELECT count(*) as count FROM texts as t $filter_cond"))
      $this->_fail("Cannot access database: ".mysqli_error($this->DB));
    $ret = mysqli_fetch_assoc($dbresult);
    return $ret['count'];
  }
	
# List all texts and versions
	function list_texts($offset = 0, $limit = 0, $filter=array()) {
		$texts=array();
    $filter_cond = '';
    if (IsSet($filter['tname']) && $filter['tname']!='') {
      $filter_cond .= "LOCATE('".mysqli_real_escape_string($this->DB,$filter['tname'])."',t.name)>0";
    }
    if (IsSet($filter['vname']) && $filter['vname']!='') {
      $vcond .= "LEFT JOIN versions as v ON v.text_id=t.id WHERE LOCATE('".mysqli_real_escape_string($this->DB,$filter['vname'])."',v.version_name)>0";
      if ($filter_cond!='') $filter_cond = $vcond.' AND '.$filter_cond;
      else $filter_cond = $vcond;
    } else {
      if ($filter_cond!='') $filter_cond = 'WHERE '.$filter_cond;
    }
    $qlimit = '';
    if ($limit || $filter_cond!='') {
      $pquery =  "SELECT t.id FROM texts as t $filter_cond ORDER BY t.name";
      if ($limit) $pquery .= " LIMIT $offset,$limit";
      if (!$dbresult1 = mysqli_query($this->DB,$pquery))
        $this->_fail("Cannot access database: ".mysqli_error($this->DB));
      if (mysqli_num_rows($dbresult1)) {
		$tids = array();
		while ($ret1 = mysqli_fetch_assoc($dbresult1)) { $tids[] = $ret1['id']; };
		$qlimit = ' AND t.id IN ('.join(', ', $tids).') ';
      } else {
        $qlimit = ' AND FALSE';
      }
    }
		$query = "SELECT t.id as text_id, t.name as text_name, v.id as version_id, v.version_name as version_name, v.root_id as root_id, v.text_changed as text_changed, v.uniq_ids as uniq_ids, (SELECT count(*) FROM alignments WHERE ver1_id=v.id OR ver2_id=v.id) as alignment_count FROM texts t, versions as v WHERE v.text_id=t.id $qlimit ORDER BY t.name, v.version_name";
		// by using JOIN... not any faster...?
    //$query = "SELECT t.id as text_id, t.name as text_name, v.id as version_id, v.version_name as version_name, v.root_id as root_id, v.text_changed as text_changed, v.uniq_ids as uniq_ids, count(a.id) as alignment_count FROM texts t, versions as v LEFT JOIN alignments as a ON (a.ver1_id=v.id OR a.ver2_id=v.id) WHERE v.text_id=t.id GROUP BY t.name, v.version_name ORDER BY t.name, v.version_name";
		if (!$dbresult1 = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		while ($ret1 = mysqli_fetch_assoc($dbresult1)) { $texts[] = $ret1; };
		return $texts;
	}

# List versions
	function list_versions($txt='') {
		$texts = array();
		$query = "SELECT DISTINCT(version_name) as version_name FROM versions ORDER BY version_name";
		if ($txt!='') $query = "SELECT * FROM versions WHERE text_id=$txt";
		if (!$dbresult1 = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB)." Query: ".$query);
		while ($ret1 = mysqli_fetch_assoc($dbresult1)) { $texts[] = $ret1; };
		return $texts;
	}

# Update IDs of alignable elements: return 1 for one-level / 2 for two-level IDs
	function update_eids($txt,$docid,$twolevel='') {
		global $FORCE_SIMPLE_NUMBERING;
		if ($twolevel==='') { if ($FORCE_SIMPLE_NUMBERING) $twolevel=false; else $twolevel=true; }
		$alignables=array();
		$ver = $this->txtver_by_id($docid);
		$txt_elements = explode(' ',$ver['text_elements']);
		foreach($txt_elements as $element) $alignables[]="e.element_name='$element'";
		$alignable = join(' OR ',$alignables);
		if ($twolevel) {
			# Before two-level numbering: first check whether the container elements do not nest each other!
			$containers = $this->get_container_names($txt,$docid);
			# Comment out the check if too time-consuming
			if (!$this->are_nested($containers,$txt,$docid)) {
				$contels=array();
				foreach($containers as $element) $contels[]="element_name='$element'";
				$contel = join(' OR ',$contels);
				# First number all container elements
				@mysqli_query($this->DB,"SET @num:=0") or $this->_fail("Cannot initiate counter in database: ".mysqli_error($this->DB));
				@mysqli_query($this->DB,"UPDATE `{$txt}_elements` SET element_id=@num:=@num+1 WHERE txtver_id=$docid AND ($contel) ORDER BY txt_position") 
					or $this->_fail("Cannot update parent elements in database: ".mysqli_error($this->DB));
				# Now, copy the parent's (container's) new IDs to the alignable elements
				@mysqli_query($this->DB,"UPDATE `{$txt}_elements` e, `{$txt}_elements` p SET e.element_id=p.element_id WHERE e.txtver_id=$docid AND e.parent=p.id AND ($alignable)")
					or $this->_fail("Cannot update elements in database (eid update1): ".mysqli_error($this->DB));
				# Finally, update the ID's of the alignable elements
				@mysqli_query($this->DB,"SET @num:=0, @prp:=0") or $this->_fail("Cannot initiate counters in database: ".mysqli_error($this->DB));
				@mysqli_query($this->DB,"UPDATE `{$txt}_elements` e SET element_id=CONCAT(element_id,':',@num:=if((@prp+0)=(parent+0),@num+1,1)), parent=@prp:=parent WHERE txtver_id=$docid AND ($alignable) ORDER BY txt_position")
					or $this->_fail("Cannot update elements in database (eid update2): ".mysqli_error($this->DB));
				$this->set_uniq_ids($txt,$docid,true);
				return 2;
			}
		}
		# One level numbering only if selected or as a fallback
		@mysqli_query($this->DB,"SET @num=0") or $this->_fail("Cannot initiate counter in database: ".mysqli_error($this->DB));
		@mysqli_query($this->DB,"UPDATE `{$txt}_elements` e SET element_id=@num:=@num+1 WHERE txtver_id=$docid AND ($alignable) ORDER BY txt_position")
			or $this->_fail("Cannot update elements: ".mysqli_error($this->DB));
		$this->set_uniq_ids($txt,$docid,true);
		return 1;
	}

# Set the "uniq_ids" flag
	function set_uniq_ids($txt,$vid,$value) {
		if ($value) $value='TRUE'; else $value='FALSE';
		if (!@mysqli_query($this->DB,"UPDATE versions SET uniq_ids=$value WHERE text_id=$txt AND id=$vid")) {
			$this->_failure("Cannot update: ".mysqli_error($this->DB));
			return FALSE;
		}
		return TRUE;
	}

# Change status of a single link
	function change_status($aid,$pos,$status) {
		$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		@mysqli_query($this->DB,"UPDATE `{$txt}_links` SET status=$status WHERE alignment_id=$aid AND position=$pos")
			or $this->_fail("Cannot update link: ".mysqli_error($this->DB));
		return TRUE;
	}
	
# Change status of a single link
	function change_mark($aid,$pos,$val) {
		$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		@mysqli_query($this->DB,"UPDATE `{$txt}_links` SET mark=$val WHERE alignment_id=$aid AND position=$pos")
			or $this->_fail("Cannot update link: ".mysqli_error($this->DB));
		return TRUE;
	}

# TCA2 alignment
	function autoalign_tca2($aid,$profile='',$report=TRUE) {
		if ($profile=='') $profile='default';
		if ($report) { print "Process: Initializing TCA2 alignment...\nProgress: 0\n"; flush(); ob_flush(); }
		$uid = $this->unique_id();
		$infilename1 = "/tmp/inf1.$uid.xml";
		$infilename2 = "/tmp/inf2.$uid.xml";
		$resfilename = "/tmp/alignment.$uid.xml";
		$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		$v1 = $al['ver1_id']; $v2 = $al['ver2_id'];
		$v1i = $this->txtver_by_id($v1); $v2i = $this->txtver_by_id($v2);
		foreach(explode(' ',$v1i['text_elements']) as $element) $alignables1[]="element_name='$element'";
		foreach(explode(' ',$v2i['text_elements']) as $element) $alignables2[]="element_name='$element'";
		$alignable1 = join(' OR ',$alignables1);
		$alignable2 = join(' OR ',$alignables2);
		# CHECK TABLE accelerates the query in our version of MySQL by multiple orders!
		mysqli_query($this->DB,"CHECK TABLE `{$txt}_links`");
    if (!$dbresult = mysqli_query($this->DB,"START TRANSACTION")) {
      $this->_failure("Cannot start transaction: ".mysqli_error($this->DB));
      if ($report) print("$_ERROR\n");
      return FALSE;
    }
		# Get unaligned elements from text version 1
		$anyelements = false;
		if ($report) { print "Process: Exporting elements...\nProgress: 0\n"; flush(); ob_flush(); }
		$query = "SELECT id,contents FROM `{$txt}_elements` WHERE (txtver_id='$v1' AND ($alignable1) AND id NOT IN (SELECT element_id FROM `{$txt}_links` WHERE alignment_id='$aid' AND version_id='$v1')) ORDER BY txt_position";
		if (!$dbresult = mysqli_query($this->DB,$query)) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
      if ($report) print("$_ERROR\n");
      return FALSE;
    }
		if (mysqli_num_rows($dbresult)) {
			if (!$file = fopen($infilename1,'w')) {
        $this->_failure("Cannot open file for writing: $infilename1");
        if ($report) print("$_ERROR\n");
        return FALSE;
      }
			if (fwrite($file,"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<text><p>\n")===FALSE) {
        $this->_failure("Cannot write to file: $infilename1");
        if ($report) print("$_ERROR\n");
        return FALSE;
      }
			while ($e = mysqli_fetch_assoc($dbresult)) {
				$anyelements = true;
				if (preg_match('/^(<[^>]*>|\W)*$/',trim($e['contents']))) $e['contents'] = $e['contents'].'X'; # TCA2 is hysterical about empty elements
				if (fwrite($file,"<s id=\"{$e['id']}\">{$e['contents']}</s>\n")===FALSE) {
          $this->_failure("Cannot write to file: $infilename1");
          if ($report) print("$_ERROR\n");
          return FALSE;
        }
			}
			if (fwrite($file,"</p></text>\n")===FALSE) {
        $this->_failure("Cannot write to file: $infilename1");
        if ($report) print("$_ERROR\n");
        return FALSE;
      }
		}
		fclose($file);
		#print "Process: Written $i elements from text 1.\nProgress: 0\n"; flush(); ob_flush();
		# Get unaligned elements from text version 2
		$query = "SELECT id,contents FROM `{$txt}_elements` WHERE (txtver_id='$v2' AND ($alignable2) AND id NOT IN (SELECT element_id FROM `{$txt}_links` WHERE alignment_id='$aid' AND version_id='$v2')) ORDER BY txt_position";
		if (!$dbresult = mysqli_query($this->DB,$query)) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
      if ($report) print("$_ERROR\n");
      return FALSE;
    }
		if (mysqli_num_rows($dbresult)) {
			if (!$file = fopen($infilename2,'w'))  {
        $this->_failure("Cannot open file for writing: $infilename2");
        if ($report) print("$_ERROR\n");
        return FALSE;
      }
			if (fwrite($file,"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<text><p>\n")===FALSE) {
        $this->_failure("Cannot write to file: $infilename2");
        if ($report) print("$_ERROR\n");
        return FALSE;
      }
			while ($e = mysqli_fetch_assoc($dbresult)) {
				$anyelements = true;
				if (preg_match('/^(<[^>]*>|\W)*$/',trim($e['contents']))) $e['contents'] = $e['contents'].'X'; # TCA2 is hysterical about empty elements
				if (fwrite($file,"<s id=\"{$e['id']}\">{$e['contents']}</s>\n")===FALSE) {
          $this->_failure("Cannot write to file: $infilename2");
          if ($report) print("$_ERROR\n");
          return FALSE;
        }
			}
			if (fwrite($file,"</p></text>\n")===FALSE) {
        $this->_failure("Cannot write to file: $infilename2");
        if ($report) print("$_ERROR\n");
        return FALSE;
      }
		}
		fclose($file);
		#print "Process: Written $i elements from text 2.\nProgress: 0\n"; flush(); ob_flush();
		# Run TCA2
		if ($anyelements) {
			if ($report) { print "Process: Running TCA2 using profile '$profile'...\nProgress: 0\n"; flush(); ob_flush(); }
			$pwd = preg_replace('/[^\/]*$/','',__FILE__);
			if (!$tca2 = popen("cd $pwd/tca2; nice -n 10 java -jar tca2.jar -cfg=$profile.cfg -dic=$profile.dic -out=$resfilename $infilename1 $infilename2",'r')) {
        $this->_failure("Cannot run TCA2.");
        if ($report) print("$_ERROR\n");
        return FALSE;
      }
			while ($line=fgets($tca2)) {
				if ($report)
					if (preg_match('/^Progress:/',$line,$m))
						{ print $line; flush(); ob_flush(); }
					elseif (preg_match('/^Aligned: ([0-9]+)\/([0-9]+)/',$line,$m)) {
						$prog = round((intval($m[1])/intval($m[2]))*100);
						if ($prog>$lastprog) { $lastprog=$prog; print "Progress: $prog\n"; flush(); ob_flush(); }
					} elseif (preg_match('/^(Reading|Running|Error)/',$line,$m))
						{ print "Process: ".$line."Progress: 0\n"; flush(); ob_flush(); }
					else print $line;
			}
			pclose($tca2);
			if (preg_match('/^Saving:/',$line)) {
        $this->_failure("TCA2 failed.");
        if ($report) print("$_ERROR\n");
        return FALSE;
      } #{print "LAST>$line<"; flush(); ob_flush(); return FALSE; }
			# Load the alignment
			if ($report) { print "Process: Importing resulting alignment...\nProgress: 0\n"; flush(); ob_flush(); }
			$contents = file_get_contents($resfilename);
			$total = substr_count($contents,'<link '); if (!$total) $total = substr_count($contents,'<LINK ');
			if (!$total) {
					if ($report) { print("Process: Importing resulting alignment...<br />ERROR: No links found in file!\n"); flush(); ob_flush(); }
          $this->_failure("No links found in file!");
					return FALSE;
			}
			$xml = new XMLReader(); 
			$xml->open($resfilename);
			while ($xml->name!='linkGrp') {
				$step=@$xml->read();
				if (!$step) {
					if ($report) { print("Process: Initializing import...<br />ERROR: Invalid XML file!\n"); flush(); ob_flush(); }
					$xml->close();
					return FALSE;
				}
			}
			if (!$dbresult = mysqli_query($this->DB,"SELECT max(position) as last FROM `{$txt}_links` WHERE (alignment_id='$aid')")) {
				$this->_failure("Cannot access database: ".mysqli_error($this->DB));
        if ($report) print("$_ERROR\n");
        $xml->close();
        return FALSE;
      }
			if (!mysqli_num_rows($dbresult)) $position=0;
			else {
				$ret = mysqli_fetch_assoc($dbresult);
				$position = $ret['last'];
			}
			$cnt = 0; $status = STATUS_AUTOMATIC;
			while (@$xml->read()) {
				if ($xml->name=='link') {
					$position++;
					$cnt++; $prog=round(($cnt/$total)*100);
					if ($report AND $prog!=$lastprog) { $lastprog=$prog; print "Progress: $prog\n"; flush(); ob_flush(); }
					while ($xml->moveToNextAttribute()) {
						if ($xml->name=='xtargets') $link = $xml->value;
						if ($xml->name=='status') $status = array_search($xml->value,$STATUS);
					}
					list($v1_grp,$v2_grp) = explode(';',$link);
					if (trim($v1_grp)!='') $v1_ids = explode(' ',$v1_grp); else $v1_ids = array();
					if (trim($v2_grp)!='') $v2_ids = explode(' ',$v2_grp); else $v2_ids = array();
					if (!$this->_add_link($txt,$aid,$al['ver1_id'],$al['ver2_id'],$v1_ids,$v2_ids,$position,$status)) {
            $xml->close();
            if ($report) print("Process: Importing alignment from file...<br/>$_ERROR\n");
            return FALSE;
          }
				}
			}
			$xml->close();
			unlink($resfilename);
	 	} else {
			if ($report) { print "Process: Nothing to align. Skipping TCA2.\nProgress: 0\n"; flush(); ob_flush(); }
		}
    if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
      $this->_failure("Cannot commit transaction: ".mysqli_error($this->DB));
      if ($report) print("$_ERROR\n");
      return FALSE;
    }
		unlink($infilename1); unlink($infilename2);
		return TRUE;
	}

# HUNALIGN alignment
	function autoalign_hunalign($aid,$profile='',$report=TRUE) {
		if ($profile=='') $profile='none';
		if ($report) { print "Process: Preparing HUNALIGN alignment...\nProgress: 0\n"; flush(); ob_flush(); }
		$uid = $this->unique_id();
		$infilename1 = "/tmp/inf1.$uid.txt";
		$infilename2 = "/tmp/inf2.$uid.txt";
		$resfilename = "/tmp/alignment.$uid.txt";
		$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		$v1 = $al['ver1_id']; $v2 = $al['ver2_id'];
		$v1i = $this->txtver_by_id($v1); $v2i = $this->txtver_by_id($v2);
		foreach(explode(' ',$v1i['text_elements']) as $element) $alignables1[]="element_name='$element'";
		foreach(explode(' ',$v2i['text_elements']) as $element) $alignables2[]="element_name='$element'";
		$conts1 = $this->get_container_names($txt,$v1);
		$conts2 = $this->get_container_names($txt,$v2);
		foreach ($conts1 as $element) $alignables1[]="element_name='$element'";
		foreach ($conts2 as $element) $alignables2[]="element_name='$element'";
		$alignable1 = join(' OR ',$alignables1);
		$alignable2 = join(' OR ',$alignables2);
		$elements1 = array(); $elements2 = array();
		# CHECK TABLE accelerates the query in our version of MySQL by multiple orders!
		mysqli_query($this->DB,"CHECK TABLE `{$txt}_links`");
    if (!$dbresult = mysqli_query($this->DB,"START TRANSACTION")) {
      $this->_failure("Cannot start transaction: ".mysqli_error($this->DB));
      if ($report) print("$_ERROR\n");
      return FALSE;
    }
		# Get unaligned elements from text version 1
		$anyelements = false; $incompl=false;
		if ($report) { print "Process: Exporting unaligned elements...\nProgress: 0\n"; flush(); ob_flush(); }
		if (!$dbresult = mysqli_query($this->DB,"SELECT id,element_name,contents,element_id FROM `{$txt}_elements` WHERE (txtver_id='$v1' AND ($alignable1) AND id NOT IN (SELECT element_id FROM `{$txt}_links` WHERE alignment_id='$aid' AND version_id='$v1')) ORDER BY txt_position")) {
			$this->_failure("Cannot access database: ".mysqli_error($this->DB));
      if ($report) print("$_ERROR\n");
      return FALSE;
    }
		if (mysqli_num_rows($dbresult)) {
			if (!$file = fopen($infilename1,'w')) {
        $this->_failure("Cannot open file for writing: $infilename1");
        if ($report) print("$_ERROR\n");
        return FALSE;
      }
			$i = 0;
			while ($e = mysqli_fetch_assoc($dbresult)) {
				if (in_array($e['element_name'],$conts1)) {
					$e['contents'] = "<p>"; $elements1[$i] = FALSE;
				} else  {
					$anyelements = true;
					$e['contents'] = preg_replace('/[\n\r]/','',$e['contents']);
					$e['contents'] = preg_replace('/<[^>]*>/','',$e['contents']);
					$elements1[$i] = $e['id'];
				}
				$i++;
				if (fwrite($file,"{$e['contents']}\n")===FALSE) {
          $this->_failure("Cannot write to file: $infilename1");
          if ($report) print("$_ERROR\n");
          return FALSE;
        }
				# Current HUNALIGN cannot align too long texts!
				//if ($i>=30000) { $incompl=true; print "\n------------break at ".$e['element_id']."\n\n"; break; }
			}
		}
		fclose($file);
		#print "Process: Written $i elements from text 1.\nProgress: 0\n"; flush(); ob_flush();
		if ($report) { print "Progress: 50\n"; flush(); ob_flush(); }
		# Get unaligned elements from text version 2
		if (!$dbresult = mysqli_query($this->DB,"SELECT id,element_name,contents,element_id FROM `{$txt}_elements` WHERE (txtver_id='$v2' AND ($alignable2) AND id NOT IN (SELECT element_id FROM `{$txt}_links` WHERE alignment_id='$aid' AND version_id='$v2')) ORDER BY txt_position")) {
      $this->_failure("Cannot access database: ".mysqli_error($this->DB));
      if ($report) print("$_ERROR\n");
      return FALSE;
    }
		if (mysqli_num_rows($dbresult)) {
			if (!$file = fopen($infilename2,'w')) {
        $this->_failure("Cannot open file for writing: $infilename2");
        if ($report) print("$_ERROR\n");
        return FALSE;
      }
			$i = 0;
			while ($e = mysqli_fetch_assoc($dbresult)) {
				if (in_array($e['element_name'],$conts2)) {
					$e['contents'] = "<p>"; $elements2[$i] = FALSE;
				} else {
					$anyelements = true;
					$e['contents'] = preg_replace('/[\n\r]/','',$e['contents']);
					$e['contents'] = preg_replace('/<[^>]*>/','',$e['contents']);
					$elements2[$i] = $e['id'];
				}
				$i++;
				if (fwrite($file,"{$e['contents']}\n")===FALSE) {
          $this->_failure("Cannot write to file: $infilename2");
          if ($report) print("$_ERROR\n");
          return FALSE;
        }
				# Current HUNALIGN cannot align too long texts!
				//if ($i>=30000) { $incompl=true; print "\n------------break at ".$e['element_id']."\n\n"; break; }
			}
		}
		fclose($file);
		#print "Process: Written $i elements from text 2.\nProgress: 0\n"; flush(); ob_flush();
		# Run HUNALIGN (if any alignable elements...)
		if ($anyelements) {
			$pwd = preg_replace('/[^\/]*$/','',__FILE__);
			if ($report) { print "Process: Running HUNALIGN using profile '$profile'...\nProgress: 0\n"; flush(); ob_flush(); }
			if (!$hunalign = popen("cd $pwd/hunalign; nice -n 10 ./process.sh $profile $infilename1 $infilename2 $resfilename",'r')) {
        $this->_failure("Cannot run hunalign.");
        if ($report) print("$_ERROR\n");
        return FALSE;
      }
			while ($line=fgets($hunalign)) {
				if ($report) { print "Process: hunalign > ".$line."\n"; flush(); ob_flush(); }
			}
			pclose($hunalign);
			# Load the alignment
			if ($report) { print "Process: Importing resulting alignment...\nProgress: 0\n"; flush(); ob_flush(); }
			$contents = file_get_contents($resfilename);
			$total = substr_count($contents,"\n");
			if (!$total) {
					if ($report) { print("Process: Importing resulting alignment...<br />ERROR: No links found in file!\n"); flush(); ob_flush(); }
          $this->_failure("No links found in file!");
					return FALSE;
			}
			if (!$dbresult = mysqli_query($this->DB,"SELECT max(position) as last FROM `{$txt}_links` WHERE (alignment_id='$aid')")) {
				$this->_failure("Cannot access database: ".mysqli_error($this->DB));
        if ($report) print("$_ERROR\n");
        return FALSE;
      }
			if (!mysqli_num_rows($dbresult)) $position=0;
			else {
				$ret = mysqli_fetch_assoc($dbresult);
				$position = $ret['last'];
			}
			$ptr1 = 0; $ptr2 = 0;
			$cnt = 0; $status = STATUS_AUTOMATIC;
			foreach (explode("\n",$contents) as $line) {
				$cnt++; $prog=round(($cnt/$total)*100);
				if ($report AND $prog!=$lastprog) { $lastprog=$prog; print "Progress: $prog\n"; flush(); ob_flush(); }
				list($v1p,$v2p,$prec) = explode("\t",$line);
				$v1_ids = array(); $v2_ids = array();
				while ($ptr1<$v1p) { 
					if ($elements1[$ptr1]) $v1_ids[] = $elements1[$ptr1];
					$ptr1++;
				}
				while ($ptr2<$v2p) { 
					if ($elements2[$ptr2]) $v2_ids[] = $elements2[$ptr2];
					$ptr2++;
				}
				if (count($v1_ids)>0 || count($v2_ids)>0) {
					$position++;
					if (!$this->_add_link($txt,$aid,$al['ver1_id'],$al['ver2_id'],$v1_ids,$v2_ids,$position,$status)) {
            if ($report) print("Process: Importing alignment from file...<br/>$_ERROR\n");
            return FALSE;
          }
				}
			}
			unlink($resfilename); 
		} else {
			if ($report) { print "Process: Nothing to align. Skipping hunalign.\nProgress: 0\n"; flush(); ob_flush(); }
		}
    if (!$dbresult = mysqli_query($this->DB,"COMMIT")) {
      $this->_failure("Cannot commit transaction: ".mysqli_error($this->DB));
      if ($report) print("$_ERROR\n");
      return FALSE;
    }
		unlink($infilename1); unlink($infilename2);
		//if ($incompl) $this->autoalign_hunalign($aid,$profile,$report);
		return TRUE;
	}
	
	function autoalignment_profiles($aligner) {
		$profiles = array();
		switch ($aligner) {
		case 'tca2':
			foreach(glob('tca2/*.cfg') as $cfg) { preg_match('/tca2\/(.*)\.cfg/',$cfg,$m); $profiles[]=$m[1]; }
			break;
		case 'hunalign':
			foreach(glob('hunalign/*.dic') as $cfg) { preg_match('/hunalign\/(.*)\.dic/',$cfg,$m); $profiles[]=$m[1]; }
			break;
		}
		return $profiles;
	}

# Generate unique ID for filenames (from http://www.weberdev.com/get_example-3543.html)
function unique_id() {
  // explode the IP of the remote client into four parts
  $ipbits = explode(".", $_SERVER["REMOTE_ADDR"]);
  // Get both seconds and microseconds parts of the time
  list($usec, $sec) = explode(" ",microtime());
  // Fudge the time we just got to create two 16 bit words
  $usec = (integer) ($usec * 65536);
  $sec = ((integer) $sec) & 0xFFFF;
  // Fun bit - convert the remote client's IP into a 32 bit
  // hex number then tag on the time.
  // Result of this operation looks like this xxxxxxxx-xxxx-xxxx
  $uid = sprintf("%08x-%04x-%04x",($ipbits[0] << 24)
         | ($ipbits[1] << 16)
         | ($ipbits[2] << 8)
         | $ipbits[3], $sec, $usec);
  return $uid;
} 

	# Shift text (from the given link ID on) to a new position
	function move_to($txt,$lid,$newpos) {
		$info = $this->get_link_info($txt,$lid);
		$aid = $info['alignment_id'];
		$ver = $info['version_id'];
		$oldpos = $info['position'];
		if ($newpos<$oldpos) {
			# Moving down: First check conflicts!
			if (!$dbresult = mysqli_query($this->DB,"SELECT id FROM `{$txt}_links` WHERE alignment_id=$aid AND version_id=$ver AND position>$newpos AND position<$oldpos"))
				$this->_fail("Cannot access database1: ".mysqli_error($this->DB));
			if (mysqli_num_rows($dbresult)) {
				return "Cannot move the text. There are other elements between position $newpos and $oldpos. This range must be empty.";
			}
			$diff = $oldpos-$newpos;
			if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_links` SET position=position-$diff WHERE alignment_id=$aid AND version_id=$ver AND position>=$oldpos"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
			if ($this->auto_status_update) $this->update_manual_status($txt,$aid,$newpos-1);
		} else {
			$diff = $newpos-$oldpos;
			if (!$dbresult = mysqli_query($this->DB,"UPDATE `{$txt}_links` SET position=position+$diff WHERE alignment_id=$aid AND version_id=$ver AND position>=$oldpos"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
			if ($this->auto_status_update) $this->update_manual_status($txt,$aid,$newpos-1);
		}
		return true;
	}

# Delete links from the given position up (for re-alignment)
	function delete_alignment_from_pos($aid,$pos) {
		$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		if (!$dbresult = mysqli_query($this->DB,"DELETE FROM `{$txt}_links` WHERE alignment_id=$aid AND position>=$pos"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		return true;
	}

# Change the "editor" for alignment
	function alignment_cheditor($aid,$userid) {
		if ($userid=='') $userid='NULL';
		@mysqli_query($this->DB,"UPDATE alignments SET editor=$userid WHERE id=$aid")
			or $this->_fail("Cannot update alignments: ".mysqli_error($this->DB));
		return true;
	}

# Change the "resp" for alignment
	function alignment_chresp($aid,$userid) {
		if ($userid=='') return FALSE;
		@mysqli_query($this->DB,"UPDATE alignments SET resp=$userid WHERE id=$aid")
			or $this->_fail("Cannot update alignments: ".mysqli_error($this->DB));
		return true;
	}

# Change the "remote_user" for alignment
	function alignment_chruser($aid,$userid) {
		if ($userid=='') return FALSE;
		@mysqli_query($this->DB,"UPDATE alignments SET remote_user=$userid WHERE id=$aid")
			or $this->_fail("Cannot update alignments: ".mysqli_error($this->DB));
		return true;
	}
	
# Change the "status" for alignment
	function alignment_chstat($aid,$status) {
		global $_ERROR, $ALSTAT;
		$al = $this->alignment_info($aid);
		if ($al['resp']=='') $al['resp']=0;
		if ($al['editor']=='') $al['editor']=0;
		#if ($status==ALSTAT_CLOSED) {
		#	if ($al['status']!=ALSTAT_FINISHED) {
		#		$_ERROR = "Error: You have to set status to 'finished' first!";
		#		return FALSE;
		#	}
		#} else
		if ($status==ALSTAT_FINISHED) {
			if (!$this->alignment_check_finished($aid)) 
				return FALSE;
		}
		if ($status==ALSTAT_FINISHED || $status==ALSTAT_CLOSED) {
			if (!$this->alignment_check_complete($aid)) 
				return FALSE;
		}
		$output = array(); $retval = 0;
		if (is_executable('triggers/alstat_'.$ALSTAT[$status])) {
			$retval = 1;
			$ret = exec('triggers/alstat_'.$ALSTAT[$status]." {$al['text_name']} {$al['ver1_name']} {$al['ver2_name']} {$ALSTAT[$al['status']]} {$al['id']} {$al['resp']} {$al['editor']}",$output,$retval);
		}
		if ($retval==0) {
			@mysqli_query($this->DB,"UPDATE alignments SET status=$status WHERE id=$aid")
			or $this->_fail("Cannot update alignments: ".mysqli_error($this->DB));
			if ($status==ALSTAT_REMOTE) {
				@mysqli_query($this->DB,"UPDATE alignments SET remote_user=editor WHERE id=$aid")
				or $this->_fail("Cannot update alignments: ".mysqli_error($this->DB));
			}
			if ($ret!='') {
				$_ERROR = "Warning: ".$ret;
				return FALSE;
			}
		} else {
			$_ERROR = "External script failure: $ret";
			return FALSE;
		}
		return TRUE;
	}

# Lock alignment for a remote editor
	function alignment_remote_lock($aid,$userid) {
		@mysqli_query($this->DB,"UPDATE alignments SET status=".ALSTAT_REMOTE.", remote_user=$userid WHERE id=$aid")
			or $this->_fail("Cannot update alignments: ".mysqli_error($this->DB));
	}

# Unlock alignment
	function alignment_remote_unlock($aid,$userid,$stat) {
		@mysqli_query($this->DB,"UPDATE alignments SET status=$stat, remote_user=NULL WHERE id=$aid")
			or $this->_fail("Cannot update alignments: ".mysqli_error($this->DB));
	}

# Check whether the alignment is really finished
	function alignment_check_finished($aid) {
		global $_ERROR;
		# Test for unconfirmed links
		if ($this->get_pos_by_status($aid,STATUS_AUTOMATIC)) {
			$_ERROR = "Error: There are still unconfirmed links in this alignment.";
			return FALSE;
		}
		return TRUE;
	}

# Check whether the alignment is complete
	function alignment_check_complete($aid) {
		global $_ERROR;
		# Test for incomplete alignment 
		$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		$v1 = $al['ver1_id']; $v2 = $al['ver2_id'];
		$v1i = $this->txtver_by_id($v1); $v2i = $this->txtver_by_id($v2);
		foreach(explode(' ',$v1i['text_elements']) as $element) $alignables1[]="element_name='$element'";
		foreach(explode(' ',$v2i['text_elements']) as $element) $alignables2[]="element_name='$element'";
		$alignable1 = join(' OR ',$alignables1);
		$alignable2 = join(' OR ',$alignables2);
		$elements1 = array(); $elements2 = array();
		# CHECK TABLE accelerates the query in our version of MySQL by multiple orders!
		mysqli_query($this->DB,"CHECK TABLE `{$txt}_links`");
		# Check for unaligned elements in text version 1
		if (!$dbresult = mysqli_query($this->DB,"SELECT id,element_name,contents FROM `{$txt}_elements` WHERE (txtver_id='$v1' AND ($alignable1) AND id NOT IN (SELECT element_id FROM `{$txt}_links` WHERE alignment_id='$aid' AND version_id='$v1')) ORDER BY txt_position"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) {
			$_ERROR = "Error: The alignment is incomplete. Re-align it and finish it.";
			return FALSE;
		}
		# Check for unaligned elements in text version 2
		if (!$dbresult = mysqli_query($this->DB,"SELECT id,element_name,contents FROM `{$txt}_elements` WHERE (txtver_id='$v2' AND ($alignable2) AND id NOT IN (SELECT element_id FROM `{$txt}_links` WHERE alignment_id='$aid' AND version_id='$v2')) ORDER BY txt_position"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		if (mysqli_num_rows($dbresult)) {
			$_ERROR = "Error: The alignment is incomplete. Re-align it and finish it.";
			return FALSE;
		}
		return TRUE;
	}

# Change the "chtext" for alignment
	function alignment_chtext($aid,$value) {
		if ($value) $value='TRUE'; else $value='FALSE';
		@mysqli_query($this->DB,"UPDATE alignments SET chtext=$value WHERE id=$aid")
			or $this->_fail("Cannot update alignments: ".mysqli_error($this->DB));
		return true;
	}

# Change the "c_chstruct" for alignment
	function alignment_chcstruct($aid,$value) {
		if ($value) $value='TRUE'; else $value='FALSE';
		@mysqli_query($this->DB,"UPDATE alignments SET c_chstruct=$value WHERE id=$aid")
			or $this->_fail("Cannot update alignments: ".mysqli_error($this->DB));
		return true;
	}

# Search text
	function alignment_search($aid,$version,$string,$mypos,$dir,$type='substr') {
		global $_ERROR;
		$al = $this->alignment_info($aid);
		$txt = $al['text_id'];
		if ($version=='0') { $vidcond = ''; }
		elseif ($version=='1') { $vidcond = " AND l.version_id={$al['ver1_id']}"; }
		else { $vidcond = " AND l.version_id={$al['ver2_id']}"; }
		if ($dir=='up') { $dirp='>'; $order='ASC'; }
		else { $dirp='<'; $order='DESC'; }
		$string = mysqli_real_escape_string($this->DB,$string);
		$query = '';
		switch ($type) {
		case 'elid':
			$sc = "e.element_id='$string'";
			break;
		case 'substr':
			$sc = "e.contents LIKE '%$string%'";
			break;
		case 'bsubstr':
			$sc = "e.contents LIKE BINARY '%$string%'";
			break;
		case 'regexp':
			$sc = "e.contents REGEXP '$string'";
			break;
		case 'bregexp':
			$sc = "e.contents REGEXP BINARY '$string'";
			break;
		case 'ftext':
			$string = "+$string";
      $string = preg_replace("/([\s])(\S+)/u","\${1}+\${2}",$string);
			$sc = "MATCH (e.contents) AGAINST ('$string' IN BOOLEAN MODE)";
			break;
		case 'cftext':
			$sc = "MATCH (e.contents) AGAINST ('$string' IN BOOLEAN MODE)";
			break;
		case 'emptyseg':
			if ($version=='0') {
				$query = "SELECT l.position as pos FROM `{$txt}_links` l WHERE l.alignment_id=$aid AND l.position$dirp$mypos GROUP BY l.position HAVING (COUNT(CASE WHEN l.version_id={$al['ver1_id']} THEN element_id ELSE NULL END)=0) OR (COUNT(CASE WHEN l.version_id={$al['ver2_id']} THEN element_id ELSE NULL END)=0) ORDER BY l.position $order LIMIT 1";
			} else {
				if ($version=='1') $vid=$al['ver1_id']; else $vid=$al['ver2_id'];
				$query = "SELECT l.position as pos FROM `{$txt}_links` l WHERE l.alignment_id=$aid AND l.position$dirp$mypos GROUP BY l.position HAVING COUNT(CASE WHEN l.version_id=$vid THEN element_id ELSE NULL END)=0 ORDER BY l.position $order LIMIT 1";
			}
			break;
		case 'non-one2one':
			$query = "SELECT l.position as pos FROM `{$txt}_links` l WHERE l.alignment_id=$aid AND l.position$dirp$mypos GROUP BY l.position HAVING (COUNT(CASE WHEN l.version_id={$al['ver1_id']} THEN element_id ELSE NULL END)!=1 OR COUNT(CASE WHEN l.version_id={$al['ver2_id']} THEN element_id ELSE NULL END)!=1) ORDER BY l.position $order LIMIT 1";
			break;
    case 'largesegs':
      $query = "SELECT l.position as pos FROM `{$txt}_links` l WHERE l.alignment_id=$aid AND l.position$dirp$mypos GROUP BY l.position HAVING (COUNT(CASE WHEN l.version_id={$al['ver1_id']} THEN element_id ELSE NULL END)>1 AND COUNT(CASE WHEN l.version_id={$al['ver2_id']} THEN element_id ELSE NULL END)>1) ORDER BY l.position $order LIMIT 1";
      break;
		case 'change':
			$query = "SELECT l.position as pos FROM `{$txt}_links` l WHERE l.alignment_id=$aid AND l.position$dirp$mypos$vidcond AND (SELECT count(id) FROM `{$txt}_changelog` WHERE element_id=l.element_id)>0 ORDER BY l.position $order LIMIT 1";
			break;
	}
		if ($query=='')
			$query = "SELECT l.position as pos FROM `{$txt}_elements` e, `{$txt}_links` l WHERE l.alignment_id=$aid AND l.element_id=e.id$vidcond AND l.position$dirp$mypos AND $sc ORDER BY l.position $order LIMIT 1";
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB)."\nQuery: ".$query);
		$ret = mysqli_fetch_assoc($dbresult);
		if ($ret['pos']!='') {
			return $ret['pos'];
		} else {
			if ($dir=='up' && $mypos!=1) {
				$ret = $this->alignment_search($aid,$version,$string,1,$dir,$type);
				if ($ret) {
					$_ERROR='Warning: No more matches until the end. Searching from the beginning.';
					return $ret;
				}
			} elseif ($dir=='down' && $mypos!=$al['link_count']) {
				$ret = $this->alignment_search($aid,$version,$string,$al['link_count'],$dir,$type);
				if ($ret) {
					$_ERROR='Warning: No more matches until the beginning. Searching from the end.';
					return $ret;
				}
			}
			$_ERROR = "No matches.";
			return FALSE;
		}
	}

# Swap text versions in alignment
	function swap_versions($aid) {
		global $_ERROR;
		$al = $this->alignment_info($aid);
		if (!$al) { $_ERROR="Alignment not found."; return FALSE; }
		if (!$dbresult = mysqli_query($this->DB,"UPDATE alignments SET ver1_id={$al['ver2_id']},ver2_id={$al['ver1_id']} WHERE (id=$aid)"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		return true;
	}

# Update user details
	function user_save($u) {
		global $U_TABLE, $U_FIRSTNAME, $U_SURNAME, $U_USERNAME, $U_USERID, $U_USERTYPE, $U_USERPASS, $U_DATABASE;
		if ($u['id']==0) {
			$pwc=''; $pwv='';
			if ($u['password']!='') {
				$pwc=", $U_USERPASS";
				$pwv=", '{$u['password']}'";
			}
			$query="INSERT INTO $U_DATABASE.$U_TABLE ($U_FIRSTNAME, $U_SURNAME, $U_USERNAME, $U_USERTYPE$pwc) VALUES ('{$u['firstname']}', '{$u['surname']}', '{$u['username']}', '{$u['type']}'$pwv)";
		} else {
			$pwd='';
			if ($u['password']!='')
				$pwd = ",$U_USERPASS='{$u['password']}'";
			$query="UPDATE $U_DATABASE.$U_TABLE SET $U_FIRSTNAME='{$u['firstname']}',$U_SURNAME='{$u['surname']}',$U_USERNAME='{$u['username']}',$U_USERTYPE='{$u['type']}'$pwd WHERE $U_USERID='{$u['id']}'";
		}
		if (!$dbresult = mysqli_query($this->DB,$query))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		return true;
	}

# Delete user
	function user_delete($id) {
		global $U_TABLE, $U_USERID, $U_DATABASE;
		if (!$dbresult = mysqli_query($this->DB,"DELETE FROM $U_DATABASE.$U_TABLE WHERE $U_USERID='$id'"))
			$this->_fail("Cannot access database: ".mysqli_error($this->DB));
		return true;
	}

} # end of class InterText

?>
