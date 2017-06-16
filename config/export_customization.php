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