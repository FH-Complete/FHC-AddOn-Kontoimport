<?php
/* Copyright (C) 2014 fhcomplete.org
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
 * Authors: Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at> and
 */
/*
 * Import von Kontoauszuegen
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('kontoimport.class.php');

$uid = get_uid();
$rechte = new benutzerberechtigung();

$rechte->getBerechtigungen($uid);
if(!$rechte->isBerechtigt('basis/addon'))
	die('Sie haben keine Berechtigung');

echo '<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Kontoauszug Import</title>
	<link rel="stylesheet" href="../../../skin/fhcomplete.css" />
	<link rel="stylesheet" href="../../../skin/vilesci.css" />
</head>
<body>
';

echo '
<h1>Kontoauszug Import</h1>
Wählen Sie die XML-Datei aus um die Kontoinformationen zu importieren:<br><br>
<form action="'.$_SERVER['PHP_SELF'].'" method="POST" enctype="multipart/form-data">
<table>
<tr>
	<td>Datenformat</td>
	<td>
	<SELECT name="art">
		<OPTION value="camt.053.001.02">Bank to Customer Statement (camt.053.001.02)</OPTION>
	</SELECT>
	</td>
</tr>
<tr>
	<td>XML-Datei</td>
	<td><input type="file" name="xmlfile"></td>
</tr>
<tr>
	<td></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td></td>
	<td><input type="submit" value="Daten importieren"></td>
</tr>
</table>
</form>
<br><br>
';

if(isset($_FILES['xmlfile']))
{
	
	// XML Laden
	$xml_doc = new DOMDocument;
	if(!$xml_doc->load($_FILES['xmlfile']['tmp_name']))
		die('Fehler beim Laden des XML Files');

	$xsl_doc = new DOMDocument;

	// XSL fuer CSV konvertierung laden
	$xslname = '';
	switch($_POST['art'])
	{
		case 'camt.053.001.02': 
			$xslname = 'camt.053.001.02.xsl';
			break;
		default:
			$xslname = 'camt.053.001.02.xsl';
	}

	if(!$xsl_doc->load($xslname))
		die('unable to load xsl');
		
	// Configure the transformer
	$proc = new XSLTProcessor;
	$proc->importStyleSheet($xsl_doc); // attach the xsl rules
	
	$buffer = $proc->transformToXml($xml_doc);
	
	// CSV-Buffer in PHP Array Umwandeln
	
	// Die Zeilen muessen einzeln geparst werden da str_getcsv diese nicht erkennt
	$data = str_getcsv($buffer, "\n");
	
	// erste Zeile mit ueberschriften wegschneiden
	$data = array_slice($data, 1);
	
	/*
	0 => EmpfIBAN
	1 => EmpfBIC
	2 => Betrag
	3 => Datum
	4 => TransaktionsId
	5 => Name
	6 => Land
	7 => Adresse
	8 => Verwendungszweck
	9 => Zahlungsreferenz
	*/
	
	$kontoimport = new kontoimport();
	
	foreach($data as $row) 
	{
		$row = str_getcsv($row);
		if($kontoimport->loadFromReference($row[4]))
		{
			echo '<br>Eintrag mit der TransaktionsID '.$row[4].' ist bereits vorhanden und wird uebersprungen';
		}
		else
		{
			$kontoimport = new kontoimport();
			$kontoimport->empfaengeriban = $row[0];
			$kontoimport->empfaengerbic = $row[1];
			$kontoimport->betrag = $row[2];
			$kontoimport->datum = $row[3];
			$kontoimport->ref_id=$row[4];
			$kontoimport->name = $row[5];
			$kontoimport->land = $row[6];
			$kontoimport->adresse = $row[7];
			$kontoimport->verwendungszweck = $row[8];
			$kontoimport->zahlungsreferenz = $row[9];
			$kontoimport->status = 'n';
			
			if($kontoimport->save())
			{
				echo '<br>Eintrag mit der TransaktionsID '.$row[4].' importiert';
			}
		}
	}
	echo '<br><br>Daten Import abgeschlossen.<br><br>
	<a href="buchen.php">Buchungen übertragen</a>
	
	</body>
	</html>';
}
?>
