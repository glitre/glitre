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

$config = array(); 

/***************************************************************** 
STEP ONE - Get the records and make sure they are standard MARCXML 
******************************************************************/

function glitre_search($args) {
	
	global $config;

	include('inc.config.php');
	$config = get_config($args['library']);

	// Collect the MARCXML in a string
	$marcxml = '';
	if (!empty($config['lib']['sru'])) {
		// SRU
		$query = $args['q'] ? urlencode(massage_input($args['q'])) : 'rec.id=' . urlencode($args['id']);
		$marcxml = get_sru($query);
	} else {
		// Z39.50
		$query = $args['q'] ? "any=" . massage_input($args['q']) : 'tnr=' . urlencode($args['id']);
		$marcxml = get_z($query);
	}

	// Sort the records
	$records = glitre_sort($marcxml, $args['sort_by'], $args['sort_order']);
	
	// Pick out the ones we actually want
	
	
	// Format the records
	return glitre_format($records, $args['format']);
}

/***************************************
STEP TWO - Sort the records as desired 

Arguments 
$marcxml: a string containing records in MARCXML
$sort_by: what criteria to sort on
$sort_order: ascending or descending sort

Returns 
Sorted records, in the form of an array of arrays

record1
	title
	author
	year
	marcobj

****************************************/

function glitre_sort($marcxml, $sort_by = 'year', $sort_order = 'descending') {
	
	// Check that sort_by and sort_order are valid
	$allowed_sort_by = array('author', 'year', 'title');
	if (!in_array($sort_by, $allowed_sort_by)) {
		exit("Invalid sort_by: $sort_by");	
	}
	$allowed_sort_order = array('descending', 'ascending');
	if (!in_array($sort_order, $allowed_sort_order)) {
		exit("Invalid sort_order: $sort_order");	
	}
	
	// Parse the records
	require('File/MARCXML.php');
	$rawrecords = new File_MARCXML($marcxml, File_MARC::SOURCE_STRING);
	
	$count = 0;
	$records = array();
	while ($rawrec = $rawrecords->next()) {
		$records[] = get_sortable_record($rawrec);
		$count++;
	}
	
	// Do the actual sorting
	if       ($sort_by == 'year'   && $sort_order == 'descending') {
		usort($records, "sort_year_descending");
	} elseif ($sort_by == 'year'   && $sort_order == 'ascending')  {
		usort($records, "sort_year_ascending");
	} elseif ($sort_by == 'title'  && $sort_order == 'descending') {
		usort($records, "sort_title_descending");
	} elseif ($sort_by == 'title'  && $sort_order == 'ascending')  {
		usort($records, "sort_title_ascending");
	} elseif ($sort_by == 'author' && $sort_order == 'descending') {
		usort($records, "sort_author_descending");
	} elseif ($sort_by == 'author' && $sort_order == 'ascending')  {
		usort($records, "sort_author_ascending");
	} 	
	
	return $records;
	
}

function sort_year_descending($a, $b) {
    return strcmp($b['year'], $a['year']);
}

function sort_year_ascending($a, $b) {
    return strcmp($a['year'], $b['year']);
}

function sort_title_descending($a, $b) {
    return strcmp($b['title'], $a['title']);
}

function sort_title_ascending($a, $b) {
    return strcmp($a['title'], $b['title']);
}

function sort_author_descending($a, $b) {
    return strcmp($b['author'], $a['author']);
}

function sort_author_ascending($a, $b) {
    return strcmp($a['author'], $b['author']);
}

function get_sortable_record($rec){

	$outrecord = array();

	// Title
	if ($rec->getField("245") && $rec->getField("245")->getSubfield("a")) {
		$outrecord['title'] = marctrim($rec->getField("245")->getSubfield("a"));
	}
	if ($rec->getField("245") && $rec->getField("245")->getSubfield("b")) {
		$outrecord['title'] .= " " . marctrim($rec->getField("245")->getSubfield("b"));
	}
	
	// Author
	if ($rec->getField("100") && $rec->getField("100")->getSubfield("a")) {
		// Personal author
		$outrecord['author'] = marctrim($rec->getField("100")->getSubfield("a"));
	}
	if ($rec->getField("110") && $rec->getField("110")->getSubfield("a")) {
		// Corporate author
		$outrecord['author'] = marctrim($rec->getField("110")->getSubfield("a"));
	}
	
	// Year
	if ($rec->getField("260") && $rec->getField("260")->getSubfield("c")) {
		preg_match("/\d{4}/", marctrim($rec->getField("260")->getSubfield("c")), $match);
		$outrecord['year'] = $match[0];
	}
	
	// Save the record-as-object for later re-use
	$outrecord['marcobj'] = $rec;
	
	return $outrecord;
	
}

/*

Sort using XSLT - not used any more
Arguments: 
$marcxml: a string containing records in MARCXML
$sort_by: what criteria to sort on
$sort_order: ascending or descending sort
Applies xslt/simplesort.xslt and returns the result. 

function glitre_xslt_sort($marcxml, $sort_by = 'year', $sort_order = 'descending') {
	
	global $config;
	
	// Check that sort_by and sort_order are valid
	$allowed_sort_by = array('author', 'year', 'title');
	if (!in_array($sort_by, $allowed_sort_by)) {
		exit("Invalid sort_by: $sort_by");	
	}
	$allowed_sort_order = array('descending', 'ascending');
	if (!in_array($sort_order, $allowed_sort_order)) {
		exit("Invalid sort_order: $sort_order");	
	}

	// Create a DOM and load the data
	$xml = new DOMDocument;
	$xml->loadXML($marcxml);
	
	// Create a dom and load the XSLT
	$xsl = new DOMDocument;
	$xsl->load($config['base_path'] . 'xslt/simplesort.xslt', LIBXML_NOCDATA);
		
	// Configure the XSLT processor
	$proc = new XSLTProcessor;
	// Parameters
	$proc->setParameter('', 'sortBy', $sort_by);
	$proc->setParameter('', 'sortOrder', $sort_order);
	// Add the XSLT DOM to the processor
	$proc->importStyleSheet($xsl);
	
	// Do the transformation
	$dom = $proc->transformToDoc($xml);
	
	// Return the XML 
	return $dom->saveXML();
	
}

*/

/*****************************************
STEP THREE - Format the records as desired 
******************************************/

function glitre_format($records, $format){

	global $config;

	//Decide what to do based on $format
	if ($format == 'raw') {
		return "Format raw currently not supported";
	} elseif ($format == 'json') {
		return "Format json currently not supported";
	// Try to split $format on .
	} elseif (list($mode, $type) = explode('.', $format)) {
		// TODO
		$file = $config['base_path'] . 'plugin/' . $type . '.php';
		if (file_exists($file)) {
			include($file);
			return format($records);
		} else {
			// TODO: Log false use of format
			return "$file not found!";
		}
	}

}

/********
FUNCTIONS
*********/

/*
Utfører Z39.50-søket og returnerer postene i MARCXML-format, som en streng
*/
function get_z($ccl) {
	
	$out = '';
	
	/*
	hvis ikke ccl-parameteren er oppgitt får man en tom XML-struktur
	tilbake med records som rotnode
	*/
	if (!isset($ccl))
	{
		
		$out .= "<records>\n</records>";
	} 
	/*
	hvis ccl-parameteren er satt får man MARCXML basert på ccl-
	parameteren tilbake
	*/
	else
	{
		
		$out .= "<records>\n";
		/*
		kjører funksjonen yazCclArray som returnerer en array med
		MARCXML-data basert på $query. 
		*/
		$fetch = yazCclArray($ccl);
		/*
		henter ut verdien med nøkkelen 'result'. det er her selve
		dataene ligger lagret. $fetch-arrayen har også en verdi med
		nøkkel 'hits' som forteller hvor mange records $fetch inneholder
		*/
		$data = $fetch['result'];
		//går gjennom $data-arrayen
		foreach ($data as $record)
		{
			//splitter på nylinjetegn
			$lines = explode("\n", $record);
			/*
			overskriver den første noden i hver record med en
			'<record>'-node. dette gjør at namespacet blir fjernet
			og gjør parsing og transformering av XML lettere
			*/
			$lines[0] = "<record>";
			/*
			samler arrayen $lines til en streng og konverterer til
			utf-8
			*/
			$out .= utf8_encode(implode("\n", $lines));
		}
		$out .= "</records>";
	}
	
	return $out;
	
}

/*
returnerer en array med XML-data, hvert element i arrayen
inneholder XML-data om en record. funksjonen fungerer omtrent
på samme måte som yazCclSearch
*/
function yazCclArray($ccl)
{
	
	global $config;
	$system = $config['lib']['system'];
	
	// Create an array to hold settings for the different systems
	$zopts = array();
	$zopts['aleph'] = array(
	  'syntax' => '', 
	);
	$zopts['bibliofil'] = array(
	  'syntax' => 'normarc', 
	);
	$zopts['bibsys'] = array(
	  'syntax' => '', 
	);
	$zopts['koha'] = array(
	  'syntax' => '', 
	);
	$zopts['mikromarc'] = array(
	  'syntax' => 'normarc', 
	);
	$zopts['reindex'] = array(
	  'syntax' => '', 
	);
	$zopts['tidemann'] = array(
	  'syntax' => '', 
	);
		
	$hits = 0;
	
	$type = 'xml';
	
	$yaz_con_opts = array(
		'piggyback' => false
	);
	
	$id = yaz_connect($config['lib']['z3950'], $yaz_con_opts);
	yaz_element($id, "F");
	yaz_syntax($id, $zopts[$system]['syntax']);
	yaz_range($id, 1, 1);
	
	yaz_ccl_conf($id, get_zconfig());
	$cclresult = array();
	if (!yaz_ccl_parse($id, $ccl, $cclresult)) {
		echo 'Error yaz_ccl_parse: '.$cclresult["errorstring"];
	} else {
		// Norwegian Z39.50 have no or limited support for yaz_sort
		// See http://wiki.biblab.no/index.php/Z39.50%2C_SRU_og_sortering for details
		// yaz_sort($id, "1=31 di");
		$rpn = $cclresult["rpn"];
		// Debug
		yaz_search($id, "rpn", utf8_decode($rpn));
	}
	
	yaz_wait();

	$error = yaz_error($id);
	if (!empty($error))	{
		$yaz_errno = yaz_errno($id);
		echo "<p>Error yaz_wait: $error ($yaz_errno)</p>";
	} else {
		$hits = yaz_hits($id);
	}
	
	$data = array();
	
	for ($p = 1; $p <= $hits; $p++)
	{
		$rec = yaz_record($id, $p, $type);
		if (empty($rec)) continue;
		$data[] = $rec;
		// If a max number of records is set for this library, respect it - otherwise use the default.
		$records_max = $config['lib']['records_max'] ? $config['lib']['records_max'] : $config['records_max'];
		if ($p == $records_max) {
		  break;
		}
	}
	
	$ret = array("hits" => $hits, "result" => $data);
	
	return $ret;
}

/*
kvalifikatorsetup til yaz_ccl_conf, disse verdiene er hentet fra
BIB-1 attributtsettet funnet her:
http://bibsyst.no/produkter/bibliofil/bib1.php
ti => 1=4
ti = tittel
1 = structure (virker bare med 1 her)
4 = use attribute

KVALIFIKATORFORKLARING:
ti -> tittel
kl -> klassifikasjon (dewey)
fo -> forfatter
år -> år
sp -> språk
eo -> emneord
is -> isbn
tnr -> tittelnummer
*/
function get_zconfig() {

	return $config = array(
		'any' => '1=1016 4=2',
		'tnr' => '1=12 4=2'
	);	
	
}

/*
Utfører SRU-søket og returnerer postene i MARCXML-format, som en streng. 
Argumenter: 
query = det søkebegrepet som det skal søkes etter
limit = maks antall poster som skal returneres
*/
function get_sru($query) {
	
	global $config;
	
	$version = '1.2';
	$recordSchema = 'marcxml';
	$startRecord = 1; 
	$maximumRecords = $config['records_max'];
	
	// Bygg opp SRU-urlen
	$sru_url = $config['lib']['sru'];

	$sru_url .= "?operation=searchRetrieve";
	$sru_url .= "&version=$version";
	$sru_url .= "&query=$query";
	$sru_url .= "&recordSchema=$recordSchema";
	$sru_url .= "&startRecord=$startRecord";
	$sru_url .= "&maximumRecords=$maximumRecords";
	
	// Debug
	// echo($sru_url);

	// Hent SRU-data
	$sru_data = file_get_contents($sru_url) or exit("Feil");
	
	// Massér SRU-dataene slik at vi lett kan behandle dem med funksjonene fra File_MARC
	$sru_data = str_replace("<record xmlns=\"http://www.loc.gov/MARC21/slim\">", "<record>", $sru_data);
	preg_match_all('/(<record>.*?<\/record>)/si', $sru_data, $treff);
	$marcxml = implode("\n\n", $treff[0]);
	$marcxml = '<?xml version="1.0" encoding="utf-8"?>' . "\n<collection>\n$marcxml\n</collection>";
	
	return $marcxml;

}

function massage_input($s) {

	// Fjern komma fra feks Asbjørnsen, Kristin
	$s = str_replace(',', '', $s);
	// Fjern &
	$s = str_replace('&', '', $s);
	
	return $s;
	
}

/*
Av en eller annen grunn gir dette: 
$post->getField("zzz")->getSubfield("a")
alltid dette: 
[a]: Tittelen kommer her...
Denne funksjonen kapper av de 5 første tegnene, slik at vi får ut den faktiske tittelen
*/

function marctrim($s) {
	
	return substr($s, 5);
	
}

?>