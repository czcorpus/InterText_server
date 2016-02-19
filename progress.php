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
<div id="info">
<p><strong>Process:</strong> <span id="process">Running new alignment...</span></p>
<div id="progressbar"></div>
<p><a href="#" onclick="toggle_block(this,'log','on')">full log</a></p>
</div>
<div id="log"></div>
<script type="text/javascript">
	$(document).ready(function(){
		document.getElementById("info").style.display="block";
		$("#progressbar").progressbar({ value: 0 });
		var url = "alignment.php";
		var last = ""; var lastlen=0; var prog=0; var proc="default";
		jx.load(url, false, 'text', 'get', {'handler': function(http) {
			if (http.readyState == 3) {
				results = http.responseText;
				var m = results.split("\n");
				if ( m.length > lastlen ) {
					document.getElementById("log").innerHTML=results;
					n = m.slice(lastlen);
					lastlen = m.length-1;
					while (n.length > 0) {
						last = n.shift();
						if (last.match(/Progress: ([0-9]*)/)) {
							prog = Number(RegExp.$1);
						}
						if (last.match(/Process: (.*)/)) {
							proc = RegExp.$1;
						}
					}
					//results.match(/Progress: ([0-9]+)\n.*$/); prog = Number(RegExp.$1);
					//results.match(/Process: (.*)\n.*?$/); proc = RegExp.$1;
					$("#progressbar").progressbar('option', 'value', prog);
					document.getElementById("process").innerHTML = proc;
				}
			}
			if (http.readyState == 4) {
				document.getElementById("log").innerHTML=http.responseText;
<?php if (!IsSet($_REQUEST['keep'])) print "\t\t\t\tif (proc==\"Finished.\") window.location=\"aligner.php\";"; ?>
			}
		}});
  });
</script>
