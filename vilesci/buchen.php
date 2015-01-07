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
 *	    Stefan Puraner  <stefan.puraner@technikum-wien.at>
 */
/*
 * Gegenbuchen von importierten Kontoeintraegen
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/konto.class.php');
require_once('../../../include/datum.class.php');
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
	<title>Buchen</title>
	<link rel="stylesheet" href="../../../skin/fhcomplete.css" />
	<link rel="stylesheet" href="../../../skin/vilesci.css" />
	<link rel="stylesheet" href="../../../skin/jquery.css" type="text/css"/>
	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css"/>
	<script type="text/javascript" src="../../../include/js/jquery1.9.min.js"></script>	
	<link rel="stylesheet" type="text/css" href="../../../skin/jquery-ui-1.9.2.custom.min.css"/>
	
	<style type="text/css">
	    span {
		font-size: 12px;
	    }
	    
	    select {
		font-size: 12px !important;
	    }
	    
	    .bold {
		font-weight: bold !important;
		float: lefT;
		width: 100px;
		text-align: right;
		margin: 4px 15px 1px 0;
	    }
	</style>

	<script type="text/javascript">
	var focus_global = null;
	var row_global = null;

	$(document).ready(function() 
	{ 
		var buchungen_global;
		
		$("#dialog").dialog({
		    autoOpen: false,
		    title: "Buchungssuche",
		    width: "auto"
		});

		$("#t1").tablesorter(
		{
			sortList: [[0,0]],
			widgets: ["zebra"]
		}); 
		
//		var getSelected = function(){
//		    var t = "";
//		    if(window.getSelection) {
//			t = window.getSelection();
//		    } else if(document.getSelection) {
//			t = document.getSelection();
//		    } else if(document.selection) {
//			t = document.selection.createRange().text;
//		    }
//		    return t;
//		}

//		$(document).mouseup(function(eventObject) {
	});
	
	var getSelected = function(){
	    var t = "";
	    if(window.getSelection) {
		t = window.getSelection();
	    } else if(document.getSelection) {
		t = document.getSelection();
	    } else if(document.selection) {
		t = document.selection.createRange().text;
	    }
	    return t;
	}

	function searchPerson(row)
	{
	    row_global = row;
	    if(getSelected().toString()!=="" && getSelected().toString().length >= 3)
	    {
		//alert(getSelected().toString());
		var searchItem = getSelected().toString();
		//searchItem = searchItem.split(" ");
		$.ajax({
		    dataType: "json",
		    url: "./benutzer_request.php",
		    data: {
			"typ": "json",
			"searchItem": searchItem,
			//"searchItem": "Vondrak Peter",
//				"searchItem": "antauri",
		    }
		}).success(function(data)
		{
		    writePersonDropdown(data.result, data.errormsg);
		});
	    }
	    else
	    {
		alert("Sie müssen mindestesn 3 Zeichen markieren.");
	    }
	}

	function writePersonDropdown(persons,errormsg)
	{
	    if(persons != "")
	    {
		var html = "<span class=\"bold\">Personen: </span><select id=\"persons\" onchange=\"searchBuchung();\">";
		persons.forEach(function(v,i){
		    if(v.uid!=null)
			html += "<option value=\""+v.uid+"\">"+v.vorname+" "+v.nachname+"</option>";
		    else
			html += "<option value=\"null\">Keine Person gefunden!</option>";
			$("#buchungen").empty();
			$("#buchungsDetails").empty();
		});
		html += "</select>";
		$("#personen").html(html);
		searchBuchung();
		$("#dialog").dialog("open");
	    }
	    else if(errormsg != "")
	    {
		alert(errormsg);
	    }
	}
	
	function searchBuchung()
	{
	    uid = $("#persons").val();
	    if(uid !== "null")
	    {
		$.ajax({
		    dataType: "json",
		    url: "./buchung_request.php",
		    data: {
			"typ": "json",
			"uid": uid
		    }
		}).success(function(data)
		{
		    writeBuchungen(data.result,data.errormsg);
		});
	    }
	}
	
	function writeBuchungen(buchungen, errormsg)
	{
	    buchungen_global = buchungen;
	    if(buchungen != "")
	    {
		var html = "<span class=\"bold\">Buchungen: </span><select id=\"buchungen_dropdown\" onchange=\"writeBuchungsDetails(buchungen_global);\">";
		values = Object.getOwnPropertyNames(buchungen);
		values.forEach(function(v,i){
		    if(buchungen[v].parent && !buchungen[v].childs)
		    {
			var parent = buchungen[v].parent;
			html += "<option id=\""+parent.buchungsnr+"\" value=\""+parent.buchungsnr+"\">"+parent.buchungstext+"</option>";
			$("#buchungen").html(html);
		    }
		});
		html += "</select>";
		writeBuchungsDetails(buchungen);
	    }
	    else if(errormsg != "")
	    {
		$("#buchungsDetails").html("<span>"+errormsg+"</span>");
	    }
	    else if(buchungen.length == 0)
	    {
		$("#buchungsDetails").html("<span>Keine offenen Buchungen vorhanden!</span>");
	    }
	    
	}
	
	function writeBuchungsDetails(buchungen)
	{
	    buchung_nr = $("#buchungen_dropdown").val();
	    buchung = buchungen[buchung_nr].parent;
	    var html = "";
	    html += "<br\><span class=\"bold\">Details</span><br\>";
	    html += "<span class=\"bold\">Buchungs-Nr.: </span><span id=\"buchungsNr\">"+buchung_nr+"</span><br\>";
	    html += "<span class=\"bold\">Buchungstext: </span><span>"+buchung.buchungstext+"</span><br\>";
	    html += "<span class=\"bold\">Betrag: </span><span>"+buchung.betrag+"</span><br\>";
	    
	    var button = "<button style=\"font-size: 12px; margin: 0 0 0 115px;\" type=\"button\" onclick=\"selectBuchungsNr();\">Buchung auswählen</button>";
	    html += button;

	    $("#buchungsDetails").html(html);
	}
	
	function selectBuchungsNr()
	{
//	    if(focus_global !== null)
//	    {
//		focus = focus_global;
//		buchungsNr = $("#buchungsNr").text();
//		focus.val(buchungsNr);
//	    }
//	    else
//	    {
//		alert("Bitte Zielelement für Buchungs-Nr. auswählen");
//	    }

	    if(row_global !== null)
	    {
		console.log(row_global);
		console.log("input_"+row_global);
		console.log($("#input_"+row_global));
		console.log(buchungsNr);
		buchungsNr = $("#buchungsNr").text();
		$("#input_"+row_global).val(buchungsNr);
		$("#input_"+row_global).focus();
	    }
	    else
	    {
		alert("Bitte Zielelement für Buchungs-Nr. auswählen");
	    }
	}
	
	function setFocusedElement(ele)
	{
	    focus_global = $(ele);
	}
	</script>
</head>
<body>
';

if(isset($_POST['aktion']))
{
	switch($_POST['aktion'])
	{
		case 'gegenbuchen':
			$kontoimport_id=$_POST['kontoimport_id'];
			$buchungsnr = $_POST['buchungsnr'];
			$konto = new konto();
			$konto->load($buchungsnr);
			
			$kontoimport = new kontoimport();
			if($kontoimport->load($kontoimport_id))
			{
				$konto->betrag = $kontoimport->betrag;
				$konto->buchungsdatum = $kontoimport->datum;
				$konto->mahnspanne = '0';
				$konto->buchungsnr_verweis = $buchungsnr;
				$konto->new = true;
				$konto->insertamum = date('Y-m-d H:i:s');
				$konto->insertvon = $uid;
				if($konto->save())
				{
					$kontoimport->status='u';
					$kontoimport->buchungsnr=$konto->buchungsnr;
					$kontoimport->save();
				}
				else
					echo "Speichern fehlgeschlagen:".$konto->errormsg;
			}
			break;
		case 'verwerfen':
			$kontoimport_id=$_POST['kontoimport_id'];
			$kontoimport = new kontoimport();
			if($kontoimport->load($kontoimport_id))
			{
				$kontoimport->status='v';
				$kontoimport->save();
			}
			break;
	}
}
echo '
<h1>Buchungen<span style="vertical-align: super;"></span></h1>

';
$kontoimport = new kontoimport();
$kontoimport->getBuchungen('n');

echo '<table id="t1" class="tablesorter">
<thead>
<tr>
	<th>Datum</th>
	<th>Einzahler</th>
	<th>Verwendungszweck</th>
	<th>Zahlungsreferenz</th>
	<th>Betrag</th>
	<th colspan=2>Aktion</th>
</tr>
</thead>
<tbody>';

$datum_obj = new datum();
$i = 1;
foreach($kontoimport->result as $row)
{
	echo '<tr>';
	echo '<td>'.$datum_obj->formatDatum($row->datum,'d.m.Y').'</td>';
	echo '<td>';
	echo $row->name;
	echo ' '.$row->land;
	echo ' '.$row->adresse;
	echo '</td>';
	echo '<td>'.$row->verwendungszweck.'</td>';
	echo '<td>'.$row->zahlungsreferenz.'</td>';
	echo '<td align="right">'.number_format($row->betrag,2,',','').'</td>';
	echo '<td>';
	$konto = new konto();
	if($konto->loadFromZahlungsreferenz($row->zahlungsreferenz))
		$buchungsnr = $konto->buchungsnr;
	else
		$buchungsnr='';
	
	echo '<form method="POST">
	<input type="hidden" name="aktion" value="verwerfen" />
	<input type="hidden" name="kontoimport_id" value="'.$row->kontoimport_id.'" />
	<input type="submit" name="verwerfen" value="verwerfen"/>
	</form>';
	echo '</td><td nowrap>';
	echo '<form method="POST">
	<input type="hidden" name="aktion" value="gegenbuchen" />
	Buchung Nr:<input id="input_'.$i.'" type="buchungsnr" class="t1" name="buchungsnr" size="5" value="'.$buchungsnr.'" onclick="setFocusedElement(this);"/>
	<input type="hidden" name="kontoimport_id" value="'.$row->kontoimport_id.'" />
	<input type="submit" name="buchen" value="gegenbuchen"/>
	<a href="#" onclick="searchPerson('.$i.');"><img src="../../../skin/images/search.png" height="14"></a>
	</form>';
	
	echo '</td>';
	echo '</tr>';
	$i++;
}
echo '</tbody></table>';

echo '<span style="font-weight: bold;">Suche:</span><br/>';
echo '<span>1) </span><span>Name einer Person markieren</span><br/>';
echo '<span>2) </span><span>in entsprechender Buchungszeile auf Lupen-Symbol klicken</span><br/>';
echo '<span>3) </span><span>Person auswählen</span><br/>';
echo '<span>4) </span><span>passende Buchung auswählen und Buchungsnummer übertragen</span>';

echo '<div id="dialog">'
    . '<div id="personen"></div>'
    . '<div id="buchungen"></div>'
    . '<div id="buchungsDetails"></div>'
    . '</div>';
?>
