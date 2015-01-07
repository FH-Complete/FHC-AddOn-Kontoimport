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
 * Authors: Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at>
 */
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');

class kontoimport extends basis_db
{
	public $new=true;
	public $result=array();
	
	protected $kontoimport_id;
	protected $ref_id;
	protected $status;
	protected $empfaengeriban;
	protected $empfaengerbic;
	protected $betrag;
	protected $datum;
	protected $name;
	protected $land;
	protected $adresse;
	protected $verwendungszweck;
	protected $zahlungsreferenz;
	protected $buchungsnr;
		
	/**
	 * Konstruktor
	 */
	public function __construct()
	{
		parent::__construct();		
	}

	public function __set($name,$value)
	{
		$this->$name=$value;
	}
	
	public function __get($name)
	{
		return $this->$name;
	}
	
	/**
	 * Laedt einen Eintrag aus der Datenbank
	 * 
	 * @param $kontoimport_id ID die zu laden ist
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load($kontoimport_id)
	{
		$qry = "SELECT * FROM addon.tbl_kontoimport WHERE kontoimport_id=".$this->db_add_param($kontoimport_id);
		
		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->kontoimport_id = $row->kontoimport_id;
				$this->ref_id = $row->ref_id;
				$this->status = $row->status;
				$this->empfaengeriban = $row->empfaengeriban;
				$this->empfaengerbic = $row->empfaengerbic;
				$this->betrag = $row->betrag;
				$this->datum = $row->datum;
				$this->name = $row->name;
				$this->land = $row->land;
				$this->adresse = $row->adresse;
				$this->verwendungszweck = $row->verwendungszweck;
				$this->zahlungsreferenz = $row->zahlungsreferenz;
				$this->buchungsnr = $row->buchungsnr;
				$this->new=false;
				return true;
			}
			else
			{
				$this->errormsg='Eintrag wurde nicht gefunden';
				return false;
			}
		}
		else
		{
			$this->errormsg ='Fehler beim Laden der Daten';
			return false;
		}
	}
	
	/**
	 * Speichert die Daten in die Datenbank
	 * 
	 * @return boolean
	 */
	public function save()
	{
		if($this->new)
		{
			$qry = "BEGIN;INSERT INTO addon.tbl_kontoimport(ref_id, status, empfaengeriban, empfaengerbic, betrag, datum, name, 
					land, adresse, verwendungszweck, zahlungsreferenz, buchungsnr) VALUES(".
					$this->db_add_param($this->ref_id).','.
					$this->db_add_param($this->status).','.
					$this->db_add_param($this->empfaengeriban).','.
					$this->db_add_param($this->empfaengerbic).','.
					$this->db_add_param($this->betrag).','.
					$this->db_add_param($this->datum).','.
					$this->db_add_param($this->name).','.
					$this->db_add_param($this->land).','.
					$this->db_add_param($this->adresse).','.
					$this->db_add_param($this->verwendungszweck).','.
					$this->db_add_param($this->zahlungsreferenz).','.
					$this->db_add_param($this->buchungsnr).');';
		}
		else
		{
			$qry = 'UPDATE addon.tbl_kontoimport SET '.
					'ref_id='.$this->db_add_param($this->ref_id).', '.
					'status='.$this->db_add_param($this->status).', '.
					'empfaengeriban='.$this->db_add_param($this->empfaengeriban).', '.
					'empfaengerbic='.$this->db_add_param($this->empfaengerbic).', '.
					'betrag='.$this->db_add_param($this->betrag).', '.
					'datum='.$this->db_add_param($this->datum).', '.
					'name='.$this->db_add_param($this->name).', '.
					'land='.$this->db_add_param($this->land).', '.
					'adresse='.$this->db_add_param($this->adresse).', '.
					'verwendungszweck='.$this->db_add_param($this->verwendungszweck).', '.
					'zahlungsreferenz='.$this->db_add_param($this->zahlungsreferenz).', '.
					'buchungsnr='.$this->db_add_param($this->buchungsnr).' '.
					' WHERE kontoimport_id='.$this->db_add_param($this->kontoimport_id);
		}
		
		if($this->db_query($qry))
		{
			if($this->new)
			{
				
				$qry = "SELECT currval('addon.seq_kontoimport_kontoimport_id') as id";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->kontoimport_id = $row->id;
						$this->new=false;
						$this->db_query('COMMIT;');
					}
					else
					{
						$this->errormsg = 'Fehler beim Auslesen der Sequence';
						$this->db_query('ROLLBACK;');
						return false;
					}
				}
				else
				{
					$this->errormsg = 'Fehler beim Auslesen der Sequence';
					$this->db_query('ROLLBACK;');
					return false;
				}
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern der Daten';
			return true;
		}
	}
	
	/**
	 * Laedt einen Eintrag anhand der ReferenzID
	 * 
	 * @param $ref_id ReferenzID
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadFromReference($ref_id)
	{
		$qry = "SELECT * FROM addon.tbl_kontoimport WHERE ref_id=".$this->db_add_param($ref_id);
		
		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->kontoimport_id = $row->kontoimport_id;
				$this->ref_id = $row->ref_id;
				$this->status = $row->status;
				$this->empfaengeriban = $row->empfaengeriban;
				$this->empfaengerbic = $row->empfaengerbic;
				$this->betrag = $row->betrag;
				$this->datum = $row->datum;
				$this->name = $row->name;
				$this->land = $row->land;
				$this->adresse = $row->adresse;
				$this->verwendungszweck = $row->verwendungszweck;
				$this->zahlungsreferenz = $row->zahlungsreferenz;
				$this->buchungsnr = $row->buchungsnr;
				return true;
			}
			else
			{
				$this->errormsg='Eintrag wurde nicht gefunden';
				return false;
			}
		}
		else
		{
			$this->errormsg ='Fehler beim Laden der Daten';
			return false;
		}
	}
	
	/**
	 * Laedt einen Eintrag anhand der ReferenzID
	 *
	 * @param $ref_id ReferenzID
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function getBuchungen($status)
	{
		$qry = "SELECT * FROM addon.tbl_kontoimport WHERE status=".$this->db_add_param($status);
			
		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new kontoimport();
				
				$obj->kontoimport_id = $row->kontoimport_id;
				$obj->ref_id = $row->ref_id;
				$obj->status = $row->status;
				$obj->empfaengeriban = $row->empfaengeriban;
				$obj->empfaengerbic = $row->empfaengerbic;
				$obj->betrag = $row->betrag;
				$obj->datum = $row->datum;
				$obj->name = $row->name;
				$obj->land = $row->land;
				$obj->adresse = $row->adresse;
				$obj->verwendungszweck = $row->verwendungszweck;
				$obj->zahlungsreferenz = $row->zahlungsreferenz;
				$obj->buchungsnr = $row->buchungsnr;
				
				$this->result[] = $obj;
				
			}
			
			return true;
		}
		else
		{
			$this->errormsg ='Fehler beim Laden der Daten';
			return false;
		}
	}
}
?>
