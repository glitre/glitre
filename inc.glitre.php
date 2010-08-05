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
		$query = $args['q'] ? urlencode(masser_input($args['q'])) : 'rec.id=' . urlencode($args['id']);
		$marcxml = get_sru($query);
	} else {
		// Z39.50
		$query = $args['q'] ? "any=" . masser_input($args['q']) : 'tnr=' . urlencode($args['id']);
		$marcxml = get_z($query);
	}

	// Sort the records
	$marcxml = glitre_sort($marcxml, $args['sort_by'], $args['sort_order']);
	
	// Format the records
	return glitre_format($marcxml, $args['format']);
}

/***************************************
STEP TWO - Format the records as desired 
****************************************/

function glitre_format($marcxml, $format){

	global $config;

	//Decide what to do based on $format
	if ($format == 'raw') {
		return $marcxml;
	} elseif ($format == 'json') {
		
	// Try to split $format on .
	} elseif (list($mode, $type) = explode('.', $format)) {
		// TODO
		$file = '/home/sites/div.libriotech.no/public/glitre/plugin/' . $type . '.php';
		if (file_exists($file)) {
			include($file);
			return format($marcxml);
		} else {
			// TODO: Log false use of format
			return "$file not found!";
		}
	}

}

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

	// Create a DOM and load the data
	$xml = new DOMDocument;
	$xml->loadXML($marcxml);
	
	// Create a dom and load the XSLT
	$xsl = new DOMDocument;
	$xsl->load('/home/sites/div.libriotech.no/public/glitre/xslt/simplesort.xslt', LIBXML_NOCDATA);
		
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
		if ($p == $config['max_records']) {
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
	$maximumRecords = $config['max_records'];
	
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

/*
Tar i mot MARC-poster i form av en streng med MARCXML. 
Returnerer ferdig formatert treffliste med navigering. 
*/
function get_poster ($marcxml, $limit, $postvisning) {
	
	global $config; 
	
	$out = '';

	require('File/MARCXML.php');
	
	// Hent ut MARC-postene fra strengen i $marcxml
	$xml_poster = new File_MARCXML($marcxml, File_MARC::SOURCE_STRING);
	
	// Gå igjennom postene
	$antall_poster = 0;
	$poster = array();
	while ($post = $xml_poster->next()) {
		$poster[] = get_basisinfo($post, $postvisning);
		$antall_poster++;
	}
	
	// Sorter
	$poster = sorter($poster);

	// Sjekk om $limit er mindre enn det totale antallet poster
	// Hvis ja: plukk ut de $limit første postene
	if ($antall_poster > $limit) {
		$poster = array_slice($poster, 0, $limit);	
		$out .= '<p class="antall-poster">' . "Viser $limit av $antall_poster treff</p>";
	
	// Sjekk om vi skal vise et utsnitt
	} elseif ($antall_poster > $config['per_page']) {
		
		// Plukker ut poster som skal vises
		$side = !empty($_GET['side']) ? $_GET['side'] : 1;
		$offset = ($side - 1) * $config['per_page'];
		$lengde = $config['per_page'];
		$poster = array_slice($poster, $offset, $lengde);
		
		// Lenker for blaing
		$forste = $offset + 1;
		$siste = $forste + $config['per_page'] - 1;
		if ($siste > $antall_poster) {
			$siste = $antall_poster;
		}
		$out .= '<p id="blaing" class="antall-poster">' . "Viser treff $forste - $siste av $antall_poster. ";
		$blaurl = '?q=' . $_GET['q'] . '&lib=' . $_GET['lib'] . '&sorter=' . $_GET['sorter'] . '&orden=' . $_GET['orden'] . '&side=';
		if ($side > 1) {
			$forrigeside = $side - 1;
			$out .= '<a href="' . $blaurl . $forrigeside . '">Vis forrige side</a> ';
		} else {
			$out .= 'Vis forrige side ';
		}
		// (($page + 1) * $perPage) &gt; $hits + $perPage
		if ((($side + 1) * $config['per_page']) > ($antall_poster + $config['per_page'])) {
			$out .= 'Vis neste side ';
		} else {
			$nesteside = $side + 1;
			$out .= '<a href="' . $blaurl . $nesteside . '">Vis neste side</a> ' . "\n";
		}
		$out .= '</p>';
		
	} else {
		
		$out .= '<p class="antall-poster">' . "Viser $antall_poster av $antall_poster treff</p>" . "\n";
		
	}

	// Legg til de sorterte postene i $out
	foreach ($poster as $post) {
		$out .= $post['post'];
	}
	
	if ($antall_poster == 0) {
		$out .= '<p>Beklager, null treff...</p>' . "\n";	
	}
	
	return $out;
	
}

/*
Sorter postene. Dersom ikke både sorter og orden er satt bruker vi default sortering (år, synkende).
*/
function sorter($poster) {
	
	if ((!empty($_GET['sorter']) && 
			($_GET['sorter'] == 'aar' || 
			 $_GET['sorter'] == 'tittel' ||
			 $_GET['sorter'] == 'artist')
			 ) && 
		(!empty($_GET['orden']) && 
			($_GET['orden'] == 'stig' ||
			 $_GET['orden'] == 'synk')
			 )
		) {
			
		if ($_GET['sorter'] == 'aar' && $_GET['orden'] == 'synk') {
			usort($poster, "sorter_aar_synkende");
		} elseif ($_GET['sorter'] == 'aar' && $_GET['orden'] == 'stig') {
			usort($poster, "sorter_aar_stigende");
		} elseif ($_GET['sorter'] == 'tittel' && $_GET['orden'] == 'synk') {
			usort($poster, "sorter_tittel_synkende");
		} elseif ($_GET['sorter'] == 'tittel' && $_GET['orden'] == 'stig') {
			usort($poster, "sorter_tittel_stigende");
		} elseif ($_GET['sorter'] == 'artist' && $_GET['orden'] == 'synk') {
			usort($poster, "sorter_artist_synkende");
		} elseif ($_GET['sorter'] == 'artist' && $_GET['orden'] == 'stig') {
			usort($poster, "sorter_artist_stigende");
		} 
		
	} else {
		usort($poster, "sorter_aar_synkende");
	}
	
	return $poster;
	
}

function sorter_aar_synkende($a, $b) {
    return strcmp($b["aar"], $a["aar"]);
}

function sorter_aar_stigende($a, $b) {
    return strcmp($a["aar"], $b["aar"]);
}

function sorter_tittel_synkende($a, $b) {
    return strcmp($b["tittel"], $a["tittel"]);
}

function sorter_tittel_stigende($a, $b) {
    return strcmp($a["tittel"], $b["tittel"]);
}

function sorter_artist_synkende($a, $b) {
    return strcmp($b["artist"], $a["artist"]);
}

function sorter_artist_stigende($a, $b) {
    return strcmp($a["artist"], $b["artist"]);
}

function masser_input($s) {

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