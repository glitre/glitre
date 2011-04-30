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
set_include_path(get_include_path() . PATH_SEPARATOR . '/home/lib/PEAR');

function get_config($lib = false) {

	$c = array();

	$c['debug'] = true;

	// Path to your installation, remember trailing slash
	$c['base_path'] = '/home/me/glitre/';
	$c['smarty_path'] = '/usr/share/php/smarty/libs/Smarty.class.php';
	
	// These are default values, that may be overridden by per-library settings below
	$c['records_max'] = 100;
	$c['records_per_page'] = 10;
	
	// Allowed formats
	// Candidates can be found in the formats/ folder. Use the filename sans ".php" here
	$c['allowed_formats'] = array('simple', 'isbn-plain');
	
	// Plugins
	// Öppna bibliotek plugin config
	$c['plugins']['oppnabib']['baseurl']  = 'http://demo.biblab.no:3000/';
	$c['plugins']['oppnabib']['username'] = '***';
	$c['plugins']['oppnabib']['password'] = '***';
	$c['plugins']['oppnabib']['userpassword'] = '***';
	
	// Additional info for the lists of hits
	$c['active_plugins']['hitlist']['oppnabib'] = 'oppnabib_detail_compact';
	$c['active_plugins']['hitlist']['openlibrary_image'] = 'openlibrary_image_get_image_url_s';
	
	// Plugins that display below the title and above the main bulk of the record
	$c['active_plugins']['detail_above']['openlibrary_image'] = 'openlibrary_image_get_image_url_m';
	
	// Plugins that display below the main bulk of the record
	$c['active_plugins']['detail_below']['oppnabib'] = 'oppnabib_detail_full';

	// Caching
	// Determining the the right amount of time here will probably need some experimentation
	// Time to cache the raw results retrieved from Z39.50/SRU servers
	$c['cache_time_search'] = 3600 * 24;
	// Time to cache the results after they have been sorted
	$c['cache_time_sorted'] = 3600 * 24;
	// Time to cache records fetched with id=
	$c['cache_time_record'] = 3600;
	// Cache log - set to false to disable caching
	// Make sure the file you specify as the log is writable by the web server
	$c['cache_log_file'] = '/tmp/glitrecache.log';
	
	// Default sorting
	$c['default_sort_by'] = 'year';
	$c['default_sort_order'] = 'descending';
	
	//Libraries
	$l = array();
	$l['hig'] = array(
		'name'  => 'Høgskolen i Gjøvik (Z39.50)', 
		'records_max' => 100, 
		'records_per_page' => 10, 
		'system' => 'bibsys',
		'z3950'  => 'z3950.bibsys.no:2100/HIG'
	);
    $l['higsru'] = array(
		'name'  => 'Høgskolen i Gjøvik (SRU)', 
		'records_max' => 10, 
		'records_per_page' => 4, 
		'system' => 'bibsys',
		'sru'      => 'http://sru.bibsys.no/services/sru', 
		'item_url' => '?'
	);	
	$l['deich'] = array(
		'name'  => 'Deichmanske',
		'records_max' => 100, 
		'records_per_page' => 4, 
		'system' => 'bibliofil',  
		'z3950'  => 'z3950.deich.folkebibl.no:210/data'
	);
	$l['drmfb'] = array(
		'name'  => 'Drammen folkebibliotek',
		'records_max' => 10, 
		'records_per_page' => 4, 
		'system' => 'bibliofil',  
		'z3950'  => 'z3950.drammen.folkebibl.no:2100/data'
	);
	$l['stavanger'] = array(
		'name'    => 'Stavanger folkebibliotek', 
		'records_max' => 10, 
		'records_per_page' => 4, 
		'system'   => 'aleph', 
		'z3950'    => 'aleph.stavanger.kommune.no:2100/Z3950', 
	);
	$l['kristiansund'] = array(
		'name'    => 'Kristiansund folkebibliotek', 
		'records_max' => 10, 
		'records_per_page' => 4, 
		'system'   => 'mikromarc', 
		'z3950'    => 'z-kristiansund-fb.bibits.no:2107/xxdefault', 
	);
	$l['zpode'] = array(
		'name'    => 'Pode (Z39.50)', 
		'records_max' => 10, 
		'records_per_page' => 4, 
		'system'   => 'koha', 
		'z3950'    => 'dev.bibpode.no:9999/biblios', 
	);
	$l['pode'] = array(
		'name'    => 'Pode (SRU)', 
		'records_max' => 10, 
		'records_per_page' => 4, 
		'system'   => 'koha', 
		'sru'      => 'http://torfeus.deich.folkebibl.no:9999/biblios', 
		'item_url' => 'http://dev.bibpode.no/cgi-bin/koha/opac-detail.pl?biblionumber='
	);
	$l['sksk'] = array(
		'name'    => 'Sjøkrigsskolen (Z39.50)', 
		'records_max' => 10, 
		'records_per_page' => 4, 
		'system'   => 'koha', 
		'z3950'    => 'sksk.bibkat.no:9999/biblios', 
	);
	
	if ($lib) {
		$c['lib'] = $l[$lib];
	} else {
		$c['libraries'] = $l;	
	}
	
	return $c;
	
}

?>
