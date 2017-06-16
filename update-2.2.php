#!/usr/bin/php
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

set_time_limit(9999);
$CLI_MODE = true;
require 'init.php';
require 'lib_intertext.php';
$it = new InterText;

$query = "ALTER TABLE texts ENGINE=InnoDB";
print "Modifying table 'texts'.\n";
mysqli_query($it->DB,$query) OR print("- ERROR: Cannot modify database: ".mysqli_error($it->DB)."\n");
$query = "ALTER TABLE versions ENGINE=InnoDB";
print "Modifying table 'versions'.\n";
mysqli_query($it->DB,$query) OR print("- ERROR: Cannot modify database: ".mysqli_error($it->DB)."\n");
$query = "ALTER TABLE alignments ENGINE=InnoDB";
print "Modifying table 'alignments'.\n";
mysqli_query($it->DB,$query) OR print("- ERROR: Cannot modify database: ".mysqli_error($it->DB)."\n");

$query = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='$DB_DATABASE' AND TABLE_NAME LIKE '%_elements' OR TABLE_NAME LIKE '%_links' OR TABLE_NAME LIKE '%_changelog' OR TABLE_NAME LIKE '%_align_changelog'";
if (!$dbresult = mysqli_query($it->DB,$query))
	die("Cannot access database: ".mysqli_error($it->DB));
$tables = array();
while ($ret = mysqli_fetch_assoc($dbresult)) { $tables[] = $ret['TABLE_NAME']; }

foreach($tables as $table) {
	print "Modifying table '$table'.\n";
  if ($DISABLE_FULLTEXT AND substr($table,-9)=='_elements') {
    $query = "DROP INDEX index_ft_contents ON $table";
    mysqli_query($it->DB,$query) OR print("- ERROR: Cannot modify database: ".mysqli_error($it->DB)."\n");
  }
  if ($DISABLE_FULLTEXT AND preg_match('/[0-9]+_changelog/', $table)) {
    $query = "DROP INDEX index_ft_old_contents ON $table";
    mysqli_query($it->DB,$query) OR print("- ERROR: Cannot modify database: ".mysqli_error($it->DB)."\n");
  }
  $query = "ALTER TABLE $table ENGINE=InnoDB";
	mysqli_query($it->DB,$query) OR die("- ERROR: Cannot modify database: ".mysqli_error($it->DB)."\n");
}


?>