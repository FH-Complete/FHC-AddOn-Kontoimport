<?php
/* Copyright (C) 2013 FH Technikum-Wien
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 */
/**
 * FH-Complete Addon Template Datenbank Check
 *
 * Prueft und aktualisiert die Datenbank
 */
require_once('../../config/system.config.inc.php');
require_once('../../include/basis_db.class.php');
require_once('../../include/functions.inc.php');
require_once('../../include/benutzerberechtigung.class.php');

// Datenbank Verbindung
$db = new basis_db();

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
	<title>Addon Datenbank Check</title>
</head>
<body>
<h1>Addon Datenbank Check</h1>';

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('basis/addon'))
{
	exit('Sie haben keine Berechtigung für die Verwaltung von Addons');
}

echo '<h2>Aktualisierung der Datenbank</h2>';

// Code fuer die Datenbankanpassungen

if($result = $db->db_query("SELECT schema_name FROM information_schema.schemata WHERE schema_name = 'addon'"))
{
	if($db->db_num_rows($result)==0)
	{
		$qry = "CREATE SCHEMA addon;
		GRANT USAGE ON SCHEMA addon TO vilesci;
		GRANT USAGE ON SCHEMA addon TO web;
		";

		if(!$db->db_query($qry))
			echo '<strong>Schema addon: '.$db->db_last_error().'</strong><br>';
		else
			echo ' Neues Schema addon hinzugefügt<br>';
	}
}

if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_kontoimport"))
{

	$qry = "CREATE TABLE addon.tbl_kontoimport
			(
				kontoimport_id bigint,
				ref_id varchar(50),
				status varchar(1),
				buchungsnr bigint,
				empfaengeriban varchar(35),
				empfaengerbic varchar(16),
				betrag numeric(12,4),
				datum date,
				name text,
				land text,
				adresse text,
				verwendungszweck text,
				zahlungsreferenz varchar(35)
			);
	CREATE SEQUENCE addon.seq_kontoimport_kontoimport_id
		 INCREMENT BY 1
		 NO MAXVALUE
		 NO MINVALUE
		 CACHE 1;

		ALTER TABLE addon.tbl_kontoimport ADD CONSTRAINT pk_kontoimport PRIMARY KEY (kontoimport_id);
		ALTER TABLE addon.tbl_kontoimport ALTER COLUMN kontoimport_id SET DEFAULT nextval('addon.seq_kontoimport_kontoimport_id');
		ALTER TABLE addon.tbl_kontoimport ADD CONSTRAINT fk_kontoimport_konto FOREIGN KEY(buchungsnr) REFERENCES public.tbl_konto (buchungsnr) ON DELETE CASCADE ON UPDATE CASCADE;

	GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_kontoimport TO vilesci;
	GRANT SELECT, UPDATE ON SEQUENCE addon.seq_kontoimport_kontoimport_id TO vilesci;	
	";

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_kontoimport: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' addon.tbl_kontoimport: Tabelle addon.tbl_kontoimport hinzugefuegt!<br>';

}


echo '<br>Aktualisierung abgeschlossen<br><br>';
echo '<h2>Gegenprüfung</h2>';


// Liste der verwendeten Tabellen / Spalten des Addons
$tabellen=array(
	"addon.tbl_kontoimport"  => array("kontoimport_id","ref_id","status","empfaengeriban","empfaengerbic","betrag","datum","name","land","adresse","verwendungszweck","zahlungsreferenz")
);


$tabs=array_keys($tabellen);
$i=0;
foreach ($tabellen AS $attribute)
{
	$sql_attr='';
	foreach($attribute AS $attr)
		$sql_attr.=$attr.',';
	$sql_attr=substr($sql_attr, 0, -1);

	if (!@$db->db_query('SELECT '.$sql_attr.' FROM '.$tabs[$i].' LIMIT 1;'))
		echo '<BR><strong>'.$tabs[$i].': '.$db->db_last_error().' </strong><BR>';
	else
		echo $tabs[$i].': OK - ';
	flush();
	$i++;
}
?>
