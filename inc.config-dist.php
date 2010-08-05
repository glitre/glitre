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
	$c['base_path'] = '/path/to/glitre/';
	$c['smarty_path'] = '/path/to/Smarty.class.php';
	
	// Sample libraries
	$l = array();
	$l['hig'] = array(
		'name'  => 'Høgskolen i Gjøvik (Z39.50)', 
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system' => 'bibsys',
		'z3950'  => 'z3950.bibsys.no:2100/HIG'
	);
	$l['drmfb'] = array(
		'name'  => 'Drammen folkebibliotek (Z39.50)',
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system' => 'bibliofil',  
		'z3950'  => 'z3950.drammen.folkebibl.no:2100/data'
	);
	$l['pode'] = array(
		'name'    => 'Pode (SRU)', 
		'records_max' => 20, 
		'records_per_page' => 4, 
		'system'   => 'koha', 
		'sru'      => 'http://torfeus.deich.folkebibl.no:9999/biblios', 
		'item_url' => 'http://dev.bibpode.no/cgi-bin/koha/opac-detail.pl?biblionumber='
	);
	
	$c['lib'] = $l[$lib];
	
	return $c;
	
}

?>