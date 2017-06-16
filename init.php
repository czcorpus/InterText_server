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

ini_set('error_reporting', 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED');

# Session initialization
ini_set('session.gc_maxlifetime', '31104000');
session_name('InterText_SID');
session_start();
$cookie=session_get_cookie_params();
$ctimeout = time()+60*60*24*30*12*5;
setcookie(session_name(),session_id(),$ctimeout);

require 'config/config.php';
require 'config/login_handler.php';
require 'config/default_permissions.php';
require 'config/export_customization.php';
?>