<?php
/* 
 * Copyright 2014 fhcomplete.org
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 *
 * Authors: Stefan Puraner <stefan.puraner@technikum-wien.at>
 */

header( 'Expires:  -1' );
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Pragma: no-cache' );
header('Content-Type: text/html;charset=UTF-8');

require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/konto.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../../../include/benutzerberechtigung.class.php');

$recht = new benutzerberechtigung();

$user_id = get_uid();
$recht->getBerechtigungen($user_id);
if($recht->isBerechtigt("soap/buchungen") || $recht->isBerechtigt("basis/addon"))
{

    $uid = isset($_GET["uid"])? $_GET["uid"]:NULL;
    $benutzer = new benutzer($uid);

    $konto = new konto();

    $bool = $konto->getBuchungen($benutzer->person_id);

    $data['result']=$konto->result;
    $data['return']=true;
    $data['error']='false';
    $data['errormsg']='';
}
else
{
    $data['result']="";
    $data['return']=true;
    $data['error']='false';
    $data['errormsg']='Sie haben keine Berechtigung für diese Seite. ("soap/buchungen")';
}
echo json_encode($data);




