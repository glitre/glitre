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



// Hent ut MARC-postene fra strengen i $marcxml
$poster = new File_MARCXML($marcxml, File_MARC::SOURCE_STRING);

// Gå igjennom postene
while ($post = $poster->next()) {
	$out .= '<p class="tilbake"><a href="javascript:history.go(-1)">Tilbake til trefflista</a></p>';
	$data = get_basisinfo($post, true);
	$out .= $data['post'];
	$out .= get_detaljer($post);
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

?>