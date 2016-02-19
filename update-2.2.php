#!/usr/bin/php
<?php
ini_set('error_reporting', 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED');
set_time_limit(9999);
$CLI_MODE = true;
require 'settings.php';
require 'lib_intertext.php';
$it = new InterText;

$query = "ALTER TABLE texts ENGINE=InnoDB";
print "Modifying table 'texts'.\n";
mysql_query($query) OR print("- ERROR: Cannot modify database: ".mysql_error()."\n");
$query = "ALTER TABLE versions ENGINE=InnoDB";
print "Modifying table 'versions'.\n";
mysql_query($query) OR print("- ERROR: Cannot modify database: ".mysql_error()."\n");
$query = "ALTER TABLE alignments ENGINE=InnoDB";
print "Modifying table 'alignments'.\n";
mysql_query($query) OR print("- ERROR: Cannot modify database: ".mysql_error()."\n");

$query = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='$DB_DATABASE' AND TABLE_NAME LIKE '%_elements' OR TABLE_NAME LIKE '%_links' OR TABLE_NAME LIKE '%_changelog' OR TABLE_NAME LIKE '%_align_changelog'";
if (!$dbresult = mysql_query($query))
	die("Cannot access database: ".mysql_error());
$tables = array();
while ($ret = mysql_fetch_assoc($dbresult)) { $tables[] = $ret['TABLE_NAME']; }

foreach($tables as $table) {
	print "Modifying table '$table'.\n";
  if ($DISABLE_FULLTEXT AND substr($table,-9)=='_elements') {
    $query = "DROP INDEX index_ft_contents ON $table";
    mysql_query($query) OR print("- ERROR: Cannot modify database: ".mysql_error()."\n");
  }
  if ($DISABLE_FULLTEXT AND preg_match('/[0-9]+_changelog/', $table)) {
    $query = "DROP INDEX index_ft_old_contents ON $table";
    mysql_query($query) OR print("- ERROR: Cannot modify database: ".mysql_error()."\n");
  }
  $query = "ALTER TABLE $table ENGINE=InnoDB";
	mysql_query($query) OR die("- ERROR: Cannot modify database: ".mysql_error()."\n");
}


?>