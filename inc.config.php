<?php

/* 

Copyright 2010 ABM-utvikling

This file is part of Glitre.

Glitre is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Glitre is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Glitre.  If not, see <http://www.gnu.org/licenses/>.

*/

// Set the path to PEAR
// http://php.net/set_include_path
set_include_path(get_include_path() . PATH_SEPARATOR . '/home/lib/pear/PEAR');

function get_config($lib) {

	$c = array();

	$c['debug'] = true;
	
	$c['max_records'] = 20;
	$c['per_page'] = 4;

	// Library independent settings
	
	// Path to your installation, remember trailing slash
	$c['base_path'] = '/home/sites/div.libriotech.no/public/glitre/';
	$c['smarty_path'] = '/home/lib/Smarty-2.6.26/libs/Smarty.class.php';
	
	//Libraries
	$l = array();
	$l['hig'] = array(
		'name'  => 'Høgskolen i Gjøvik (Z39.50)', 
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system' => 'bibsys',
		'z3950'  => 'z3950.bibsys.no:2100/HIG'
	);
    $l['higsru'] = array(
		'name'  => 'Høgskolen i Gjøvik (SRU)', 
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system' => 'bibsys',
		'sru'      => 'http://sru.bibsys.no/services/sru', 
		'item_url' => '?'
	);	
	$l['deich'] = array(
		'name'  => 'Deichmanske',
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system' => 'bibliofil',  
		'z3950'  => 'z3950.deich.folkebibl.no:210/data'
	);
	$l['drmfb'] = array(
		'name'  => 'Drammen folkebibliotek',
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system' => 'bibliofil',  
		'z3950'  => 'z3950.drammen.folkebibl.no:2100/data'
	);
	$l['stavanger'] = array(
		'name'    => 'Stavanger folkebibliotek', 
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system'   => 'aleph', 
		'z3950'    => 'aleph.stavanger.kommune.no:2100/Z3950', 
	);
	$l['kristiansund'] = array(
		'name'    => 'Kristiansund folkebibliotek', 
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system'   => 'mikromarc', 
		'z3950'    => 'z-kristiansund-fb.bibits.no:2107/xxdefault', 
	);
	$l['zpode'] = array(
		'name'    => 'Pode (Z39.50)', 
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system'   => 'koha', 
		'z3950'    => 'dev.bibpode.no:9999/biblios', 
	);
	$l['pode'] = array(
		'name'    => 'Pode (SRU)', 
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system'   => 'koha', 
		'sru'      => 'http://torfeus.deich.folkebibl.no:9999/biblios', 
		'item_url' => 'http://dev.bibpode.no/cgi-bin/koha/opac-detail.pl?biblionumber='
	);
	$l['sksk'] = array(
		'name'    => 'Sjøkrigsskolen (Z39.50)', 
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system'   => 'koha', 
		'z3950'    => 'sksk.bibkat.no:9999/biblios', 
	);
	
	$c['lib'] = $l[$lib];
	
	return $c;
	
}

?>