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

### General defaults
# set default timezone
date_default_timezone_set('Europe/Prague');
# MySQL access for InterText database:
$DB_SERVER = "localhost";
$DB_USER = "intertext";
$DB_PASSWORD = "intertext";
$DB_DATABASE = "intertext";
# MySQL access for user database
$U_SERVER = $DB_SERVER;
$U_USER = $DB_USER;
$U_PASSWORD = $DB_PASSWORD;
$U_DATABASE = $DB_DATABASE;
$U_TABLE = 'users';
$U_USERNAME = 'username';
$U_USERPASS = 'password';
$U_USERTYPE = 'status';
$U_USERID = 'id';
$U_FIRSTNAME = 'name';
$U_SURNAME = 'surname';
# USER levels <=> values in database, column $U_USERTYPE
$USER_ADMIN = 0; # main administrator
$USER_RESP = 1; # supervisor / section coordinator / responsible
$USER_EDITOR = 2; # editor
# ordering of alignmnets (do not change unless you know what and why!)
$ALORDER = array(
	'ver1asc' => 'v1.version_name ASC',
	'ver1desc' => 'v1.version_name DESC',
	'ver2asc' => 'v2.version_name ASC',
	'ver2desc' => 'v2.version_name DESC',
	'edasc' => 'a.editor ASC',
	'eddesc' => 'a.editor DESC',
	'respasc' => 'a.resp ASC',
	'respdesc' => 'a.resp DESC',
	'statasc' => 'a.status ASC, a.id DESC',
	'statdesc' => 'a.status DESC, a.id DESC'
);
# default order in the list of alignments
$DEFAULT_TORDER = 'asc'; # primary sorting by text name ('asc', 'desc' or '' for no ordering by text name)
$DEFAULT_ALORDER = 'ver1asc';
# names of modes
$MODES=array(
	'1'=>"manual status update", 
	'6'=>'manual &amp; roll (-5)',
	'7'=>'manual &amp; roll (-2)',
	'8'=>'manual &amp; roll (act.)',
	'2'=>'auto update status',
	'3'=>'auto &amp; roll (-5)',
	'4'=>'auto &amp; roll (-2)',
	'5'=>'auto &amp; roll (act.)'
);
# default mode
$DEFAULT_MODE=4;
# available limits of positions per page
$LIMITS = array('10','20','50','100');
# default limit (positions per page)
$DEFAULT_LIMIT=20;
# Highlight non-1:1 alignments by default?
$DEFAULT_HIGHLIGHT = 1; # 1=true, 0=false
# Hide controls by default?
$DEFAULT_HIDE = 0; # 1=true, 0=false
# Default permission to edit texts
$DEFAULT_EDIT_PERMISSION = 1; # 1=true; 0=false
# Default permission to change structure of central/pivot text versions
$DEFAULT_C_CHSTRUCT_PERMISSION = 0; # 1=true; 0=false
# Central/pivot text versions (protected of structure changes by the c_chstruct attribute)
$C_VERSION = 'cs-.*'; # Czech versions for InterCorp
# default alignment method and profile (for automatic alignment)
$DEFAULT_METHOD='hunalign';
$DEFAULT_PROFILE='_none_';
#$DEFAULT_METHOD='tca2';
#$DEFAULT_PROFILE='no-cs';
# Default XML elements containing alignable text
$DEFAULT_TEXT_ELEMENTS = 'head s verse';
# Back links
$TXTMGR_URL = '';
$TXTMGR_TEXT = '';
# Logout URL
$LOGOUT_URL = 'aligner.php';
# Global permissions
# $SET['newalign']: permission to CREATE new alignments
# $SET['delalign']: permission to DELETE alignments
# $SET['realign']: permission to run automatic alignment again from any position
# $SET['chalmethod']: permission to change method for authomatic re-alignment
# $SET['export']: permission to export texts and/or alignment
$SET['newalign'] = false;
$SET['delalign'] = false;
$SET['realign'] = true;
$SET['chalmethod'] = true;
$SET['export'] = true;
# Maximal user level for upload of new alignments from the client InterText editor application
# (the user will also be allowed to download any text version from the database!)
$UPLOAD_MAXUSERLEVEL_CLIENT = $USER_ADMIN;
# Maximal user level for download of isolated text versions 
$DOWNLOAD_TXTVER_MAXUSERLEVEL = $USER_ADMIN;
# Filter text versions freely accessible for download (limited to user with the UPLOAD permission above)
$DOWNLOAD_TXTVER_FILTER = '.*'; # all versions / "cs-.*": only Czech versions for InterCorp
# Tags allowed in text:
$TAGS_IN_TEXT="i|b|u|emph";
# Use logging of text editation changes?
$LOG_EDIT_CHANGES = true;
# Log all changes in alignments!
$LOG_ALIGN_CHANGES = false;
# Enforce single level (re)numbering of alignable elements
# (and keep original IDs of all other elements!)
$FORCE_SIMPLE_NUMBERING = false;
# ignore unconfirmed alignments when checking for merge conflicts
$MERGE_UNCONFIRMED_FREELY = false;
# deny users to remove text from elements
$DENY_EMPTY_UPDATES = true;
# disable fulltext search (necessary for MySQL <= 5.5)
$DISABLE_FULLTEXT = false;
?>