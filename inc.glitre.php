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

require('File/MARCXML.php');

function glitre_search($q) {
	
	global $config;
	
	$q = masser_input($q);
	$query = '';
	if (!empty($config['lib']['sru'])) {
		// SRU
		$query = urlencode($q);
	} else {
		// Z39.50
		$query = "any=$q";
	}
	return podesearch($query);
}

/*
Tar i mot det ferdige søkeuttrykket og bestemmer om det skal økes med SRU 
eller Z39.50, basert på info fra config.php. 
*/
function podesearch($query, $postvisning=false){
	
	global $config;

	$marcxml = '';
	if (!empty($config['lib']['sru'])) {
		$marcxml = get_sru($query);
	} else { 
		$marcxml = get_z($query);
	}
	return get_poster($marcxml, $config['max_records'], $postvisning);
	
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
		MARCXML-data basert på $query. syntaksen er 'normarc'. mot
		deichmanske kan denne byttes til hvertfall USMARC og MARC21
		*/
		$fetch = yazCclArray($ccl, 'normarc');
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
function yazCclArray($ccl, $syntax = 'normarc')
{
	
	global $config;
	
	$hits = 0;
	
	$type = 'xml';
	
	$yaz_con_opts = array(
		'piggyback' => false
	);
	
	$id = yaz_connect($config['lib']['z3950'], $yaz_con_opts);
	yaz_element($id, "F");
	yaz_syntax($id, $syntax);
	yaz_range($id, 1, 1);
	
	yaz_ccl_conf($id, get_zconfig());
	$cclresult = array();
	if (!yaz_ccl_parse($id, $ccl, $cclresult)) {
		echo 'Error: '.$cclresult["errorstring"];
	} else {
		// NB! Ser ikke ut som Z39.50 fra Bibliofil støtter "sort"
		// Se nederst her: http://www.bibsyst.no/produkter/bibliofil/z3950.php
		// PHP/YAZ-funksjonen yaz-sort ville kunne dratt nytte av dette: 
		// http://no.php.net/manual/en/function.yaz-sort.php
		// Sort Flags
		// a Sort ascending
		// d Sort descending
		// i Case insensitive sorting
		// s Case sensitive sorting
		// Bib1-attributter man kunne sortert på: 
		// http://www.bibsyst.no/produkter/bibliofil/z/carl.xml
		// yaz_sort($id, "1=31 di");
		$rpn = $cclresult["rpn"];
		// Debug
		yaz_search($id, "rpn", utf8_decode($rpn));
	}
	
	yaz_wait();

	$error = yaz_error($id);
	if (!empty($error))	{
		echo "<p>Error: $error</p>";
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

	if ($config['debug']) {
		$out .= "\n\n <!-- \n\n $marcxml \n\n --> \n\n ";
	}
	
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

function glitre_record($id) {

	global $config;
	
	$marcxml = '';
	if (!empty($config['lib']['sru'])) {
		$marcxml = get_sru('rec.id=' . urlencode($id), 1);
	} else {
		$marcxml = get_z('tnr=' . urlencode($id), 1);
	}
	
	if ($config['debug']) {
		echo("\n\n <!-- \n\n $marcxml \n\n --> \n\n ");
	}
	
	// Variabel som skal samle opp output
	$out = "";
	
	// Hent ut MARC-postene fra strengen i $marcxml
	$poster = new File_MARCXML($marcxml, File_MARC::SOURCE_STRING);

	// Gå igjennom postene
	while ($post = $poster->next()) {
		$out .= '<p class="tilbake"><a href="javascript:history.go(-1)">Tilbake til trefflista</a></p>';
		$data = get_basisinfo($post, true);
		$out .= $data['post'];
		$out .= get_detaljer($post);
	}
	
	return $out;
	
}

/*
Henter ut grunnleggende informasjon som tittel, artist, selskap, år fra en post
og returnerer dem ferdig formattert. Samtidig bygges det opp et array med tittel, 
artist og år som brukes ved sortering av postene. 
*/
function get_basisinfo($post, $postvisning) {

	global $config;

	// Hent ut IDen til posten og sett sammen URLen til posten i katalogen
	$bibid = '';
	$itemurl = '';
	if ($post->getField("999") && $post->getField("999")->getSubfield("c")) {
		// Hvis 999$c er derfinert har vi SRU-data fra Koha, og kan sette sammen den verdien med item_url-variabelen fra config.php
		$bibid = marctrim($post->getField("999")->getSubfield("c"));
		$itemurl = $config['lib']['item_url'] . $bibid;
	} else {
		// Alternativt finner vi en fiks ferdig URL i 996$u
		$bibid = substr(marctrim($post->getField("001")), 3);
		$itemurl = marctrim($post->getField("996")->getSubfield("u"));
	}

	// BYGG OPP ENKEL POSTVISNING

    $out = '<div class="basisinfo">';
    
    // Tittel
    if ($post->getField("245") && $post->getField("245")->getSubfield("a")) {
    	// Fjern eventuelle punktum på slutten av tittelen
    	$tittel = preg_replace("/\.$/", "", marctrim($post->getField("245")->getSubfield("a")));

		if ($postvisning) {
			// Vi lenker ikke til posten når vi er inne på den
			$out .= '<span class="albumtittel">' . $tittel . '</span>';

		} else {
			$out .= '<a href="?lib=' . $_GET['lib'] . '&id=' . $bibid . '" title="Vis detaljer" class="albumtittel">' . $tittel . '</a>' . "\n";
		}

    }
    if ($post->getField("245") && $post->getField("245")->getSubfield("b")) {
    	$out .= ' : ' . marctrim($post->getField("245")->getSubfield("b")) . "\n";
    }
    if ($post->getField("245") && $post->getField("245")->getSubfield("c")) {
    	$out .= ' / ' . marctrim($post->getField("245")->getSubfield("c")) . "\n";
    }
    
    // Artist
    $artist = '';
    $beskrivelse = '';
    // Sjekk om vi har artisten i 100 eller 110
    /*
    if ($post->getField("100") && $post->getField("100")->getSubfield("a")) {
    	$artist = marctrim($post->getField("100")->getSubfield("a"));
    	if ($post->getField("100")->getSubfield("q")) {
    		$beskrivelse = marctrim($post->getField("100")->getSubfield("q"));
    	}
    }
    if ($post->getField("110") && $post->getField("110")->getSubfield("a")) {
    	$artist = marctrim($post->getField("110")->getSubfield("a"));
    	if ($post->getField("110")->getSubfield("q")) {
    		$beskrivelse = marctrim($post->getField("110")->getSubfield("q"));
    	}
    }
    if ($artist != '') {
    	$out .= '<br /><a href="?q=' . urlencode($artist) . '&bib=' . $_GET['lib'] . '" class="artist">' . $artist . '</a>';
    	if ($beskrivelse != '') {
    		$out .= " ($beskrivelse)";
    	}
    }
    // Hvis vi ikke fant noe i 100 eller 110 ser v iom vi finner noe i 511
    if (!$post->getField("100") && !$post->getField("110")) {
    	if ($post->getField("511") && $post->getField("511")->getSubfield("a")) {
    		$out .= '<br />';
    		$out .= marctrim($post->getField("511")->getSubfield("a"));
    	}
    }
    */
    
    // Sted, utgiver, år
    if ($post->getField("260")) {
    	// if ($post->getField("260")->getSubfield("a")) {
    	// 	if (marctrim($post->getField("260")->getSubfield("a")) != '[S.l.]') {
    	// 		$out .= marctrim($post->getField("260")->getSubfield("a")) . ', ';
    	// 	}
    	// }
    	// if ($post->getField("260")->getSubfield("b")) {
    	// 	$out .= marctrim($post->getField("260")->getSubfield("b")) . ', ';
    	// }
    	if ($post->getField("260")->getSubfield("c")) {
    		$out .= ' ' . marctrim($post->getField("260")->getSubfield("c")) . "\n";
    	}
    }
    // if ($config['vis_kataloglenke']) {
    // 	$out .= ' [<a href="' . $itemurl . '" title="Vis i katalogen til ' . $config['lib']['title'] . '">Vis i katalogen</a>]';
    // }
    $out .= '</div>';
    
    // HENT UT DATA FOR SORTERING
    
    $data = array();

    // Tittel
   	$data['tittel'] = marctrim($post->getField("245")->getSubfield("a")); 
    if ($post->getField("245") && $post->getField("245")->getSubfield("b")) {
    	$data['tittel'] .= " " . marctrim($post->getField("245")->getSubfield("b"));
    }
    
    // Artist
    if ($post->getField("100") && $post->getField("100")->getSubfield("a")) {
    	$data['artist'] = marctrim($post->getField("100")->getSubfield("a"));
    }
    if ($post->getField("110") && $post->getField("110")->getSubfield("a")) {
    	$data['artist'] = marctrim($post->getField("110")->getSubfield("a"));
    }
    
    // År
   	if ($post->getField("260") && $post->getField("260")->getSubfield("c")) {
   		preg_match("/\d{4}/", marctrim($post->getField("260")->getSubfield("c")), $match);
   		$data['aar'] = $match[0];
   	}
   	
   	// Legg til post for visning
    $data['post'] = $out;

    return $data;
	
}

function get_detaljer($post) {

	$out = '<div class="detaljer">';
	
	// INNHOLD
	
	/*
	
	// Hent ut spor-navn fra 740$2, indikator 2 = 2 (analytt)
	if ($post->getField("740")) {
		$out .= '<p>Spor:</p>';
		$out .= '<ul>';
		$fields740 = $post->getFields("740");
		foreach ($fields740 as $field740) {
			// Sjekk om dette er en analytt
			if ($field740->getIndicator(2) == 2) {
				$tittel = marctrim($field740->getSubfield("a"));
				$tittelu = urlencode($tittel);
	    		$out .= '<li><a href="?q=' . $tittelu . '&bib=' . $_GET['lib'] . '">' . $tittel . '</a></li>' . "\n";
			}
	    }
	    $out .= '</ul>';
	// Eller hent info fra 505
	} else {
		if ($post->getField("505") && $post->getField("505")->getSubfield("a")) {
    		$out .= '<p>' . marctrim($post->getField("505")->getSubfield("a")) . '</p>' . "\n";
    	}	
	}
	
	*/

	// Items

	if ($post->getField("850") && $post->getField("850")->getSubfield("a")) {
		$out .= '<p>Eksemplarer:</p><ul>';
		foreach ($post->getFields("850") as $item) {
			$out .= '<li>'. marctrim($item->getSubfield("a")) . ', ' . marctrim($item->getSubfield("c")) . '</li>' . "\n";
		}
		$out .= '</ul>';
	}

	// Link to OPAC
	
	if ($post->getField("996") && $post->getField("996")->getSubfield("u")) {
		$url = marctrim($post->getField("996")->getSubfield("u"));
		$out .= '<p><a href="'. $url . '">Vis posten i BIBSYS Ask</a></p>' . "\n";
	}
	
	// MEDVIRKENDE
	
	/*
	
	if ($post->getField("700") && $post->getField("700")->getSubfield("a")) {
		$out .= '<p>Medvirkende:</p><ul>';
		foreach ($post->getFields("700") as $med) {
			$med = marctrim($med->getSubfield("a"));
			$out .= '<li><a href="?q=' . urlencode($med) . '&bib=' . $_GET['lib'] . '">' . $med . '</a></li>' . "\n";
		}
		$out .= '</ul>';
	}
	
	// EMNER
	
	$emner = $post->getFields('6\d\d', true);
	if ($emner) {
		$out .= '<p>Emner:</p>' . "\n";
		$out .= '<ul>' . "\n";
		foreach ($emner as $emne) {
	   		$out .= '<li>' . marctrim($emne->getSubfield("a")) . '</li>' . "\n";
	    }
	}
    $out .= '</ul>' . "\n";
	
	*/
	
	$out .= '</div>' . "\n";
	
	return $out;
	
}

function masser_input($s) {

	// Fjern komma fra feks Asbjørnsen, Kristin
	$s = str_replace(',', '', $s);
	// Fjern &
	$s = str_replace('&', '', $s);
	
	return $s;
	
}

/*
Teksten som kommer fra LastFM fører av en eller annen grunn ut i intet. 
Denne funksjonen fjerner foreløpig lenkene, etter hvert vil den endre dem så de peker til 
rett sted. 
*/
function lastfm_lenker($s) {

	$s = preg_replace("/<a .*?>(.*?)<\/a>/i", "$1", $s);
	return $s;
	
}

/*
Last.fm foretrkker Susanne Lundeng fremfor Lundeng, Susanne
*/
function avinverter($s) {
	// Sjekk om strengen inneholder noe komma
	if (substr_count($s, ',', 2) > 0) {
		list($first, $last) = split(', ', $s);
		return "$last $first";
	} else {
		return $s;
	}
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