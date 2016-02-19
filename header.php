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
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"
        "http://www.w3.org/TR/1998/REC-html40-19980424/loose.dtd">
<html onkeydown="keyPress(event);">
<head>
	<title>InterText</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> 
	<link type="text/css" href="css/cupertino/ui.css" rel="stylesheet" />
	<link type="text/css" href="css/intertext.css" rel="stylesheet" /> 
	<script type="text/javascript" src="js/jxs.js"></script>
	<script type="text/javascript" src="js/jquery-1.3.2.min.js"></script> 
	<script type="text/javascript" src="js/jeip.js"></script>
	<script type="text/javascript" src="js/ui.core.js"></script>
	<script type="text/javascript" src="js/ui.progressbar.js"></script>
	<script type="text/javascript">
	function showElement(objId) {
		document.getElementById(objId).style.visibility="visible";
	}
	function hideElement(objId) {
		document.getElementById(objId).style.visibility="hidden";
	}
	function mergeConfirm(thisform,id)
	{
		//var el1 = document.getElementById(id).innerHTML;
		return confirm("Are you sure you want to merge the elements (sentences)?\n(WARNING: invisible XML elements (paragraph breaks, etc.) inbetween will be deleted!)");
	}
	function deleteConfirm(thisform,vname)
	{
		return confirm("Are you sure you want to delete the text version '"+vname+"' ?");
	}
	function udeleteConfirm(thisform,vname)
	{
		return confirm("Are you sure you want to delete the user '"+vname+"' ?");
	}
	function aldeleteConfirm(thisform,vname)
	{
		return confirm("Are you sure you want to delete the alignment '"+vname+"' ?");
	}
	function revertConfirm()
	{
		return confirm("Do you really want to replace the current element contents with the old contents? (no splitting nor unsplitting will be done)");
	}
	function toggle(id,mode) {
		if (mode=="on") {
			document.getElementById("t"+id).setAttribute("href","javascript:toggle('"+id+"','off')");
			var spans = document.getElementById(id).getElementsByTagName("span");
			var i;
			for (i=0; i<spans.length; i++) {
				if (spans[i].getAttribute("class")!="version") spans[i].style.display="inline";
				else { spans[i].style.display="block"; spans[i].style.padding="3px 0px"; }
			}
		} else {
			document.getElementById("t"+id).setAttribute("href","javascript:toggle('"+id+"','on')");
			var spans = document.getElementById(id).getElementsByTagName("span");
			var i;
			for (i=0; i<spans.length; i++) {
				if (spans[i].getAttribute("class")!="version") spans[i].style.display="none";
				else { spans[i].style.display="inline"; spans[i].style.padding="0px"; }
			}
		}
	}
	function toggle_disp(caller,id,mode) {
		if (mode=="on") {
			caller.setAttribute("onclick","toggle_disp(this,'"+id+"','off')");
			document.getElementById(id).style.display="inline";
		} else {
			caller.setAttribute("onclick","toggle_disp(this,'"+id+"','on')");
			document.getElementById(id).style.display="none";
		}
	}
	function toggle_block(caller,id,mode) {
		if (mode=="on") {
			caller.setAttribute("onclick","toggle_block(this,'"+id+"','off')");
			document.getElementById(id).style.display="block";
		} else {
			caller.setAttribute("onclick","toggle_block(this,'"+id+"','on')");
			document.getElementById(id).style.display="none";
		}
	}
	function loadCont(url,id) {
		if (document.getElementById(id).innerHTML=="") $.get(url,function(data){document.getElementById(id).innerHTML=data;});
		else document.getElementById(id).innerHTML="";
	}
	function loadProfiles(url) {
		$.get(url,function(data){document.getElementById("profile_sel").innerHTML=data;});
	}
	function moveDialog(aid,id,defchange,eid) {
		note = "";
		if (defchange=="del") {
			note = " (leave empty if you want to delete the paragraph break instead of moving the alignment)";
		}
		if (defchange=="new") {
			note = " (leave empty if you want to insert a paragraph break instead of moving the alignment)";
		}
		var newpos = window.prompt("Move to new position"+note+":","");
		if (newpos===null)
			return;
		if (newpos!="") window.location="?req=moveto&aid="+aid+"&id="+id+"&newpos="+newpos;
		else {
			if (defchange=="del") {
				if (confirm("Are you sure you want to remove the paragraph break at this sentence?"))
					window.location="?req=delpar&aid="+aid+"&id="+eid;
			}
			if (defchange=="new") {
				if (confirm("Are you sure you want to insert a new paragraph break before this sentence?"))
					window.location="?req=newpar&aid="+aid+"&id="+eid;
			}
		}
	}
	function gotoDialog(aid) {
		newpos = window.prompt("Jump to new position:","");
		if (newpos) window.location="?req=setpos&aid="+aid+"&pos="+newpos;
	}
	function stypeChange(caller) {
		var value = caller.options[caller.selectedIndex].value;
		if (value=='emptyseg' || value=='non-one2one' || value=='largesegs' || value=='change') {
			document.getElementById('searchstring').disabled = true;
			document.getElementById('searchstring').style.color="#ccc";
		} else {
			document.getElementById('searchstring').disabled = false;
			document.getElementById('searchstring').style.color="black";
		}
		if (value=='non-one2one' || value=='largesegs') {
			document.getElementById('searchversion').disabled = true;
		} else {
			document.getElementById('searchversion').disabled = false;
		}
	}

	function showChangelog(caller,aid,itemid,status) {
		if (status=='off') {
			caller.setAttribute('onclick','showChangelog(this,'+aid+','+itemid+',\'on\')');
			document.getElementById('changes_'+itemid).innerHTML='';
		} else {
			$.get('aligner.php?req=changelog&aid='+aid+'&id='+itemid, function(data) {
				document.getElementById('changes_'+itemid).innerHTML=data;
				caller.setAttribute('onclick','showChangelog(this,'+aid+','+itemid+',\'off\')');
			});
		}
	}

</script>
</head>
<body>

