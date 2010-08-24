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

function format($records, $num_of_records, $first_record, $last_record) {

// print_r($records); 
// exit;

	$out = '<p>Displaying ' . $first_record . ' to ' . $last_record . ' of ' . $num_of_records . ' hits.</p>';
	$out .= '<ul>';
	foreach ($records as $rec) {
		$out .= '<li>' . get_basic_info($rec) . '</li>';
	}
	$out .= '</ul>';
	
	$ret = array(
		'data' => $out, 
		'content_type' => 'text/html'
	);	
	return $ret;

}

function format_single($records) {
	
	$out = '';
	foreach ($records as $rec) {
		$out .= '<h2>' . get_basic_info($rec) . '</h2>';
		$out .= get_detail($rec);
	}
	
	$ret = array(
		'data' => $out, 
		'content_type' => 'text/html'
	);	
	return $ret;
	
}

function get_basic_info($record) {
	
	// print_r($record); 
	// exit;

	global $config;
	
	// Get the ID and create a link to the record in the OPAC
	$bibid = '';
	if ($record->getField("999") && $record->getField("999")->getSubfield("c")) {
		// Koha
		$bibid = marctrim($record->getField("999")->getSubfield("c"));
	} else {
		// Others
		$bibid = substr(marctrim($record->getField("001")), 3);
	}

    $out = '';
    
    // Title
    if ($record->getField("245") && $record->getField("245")->getSubfield("a")) {
    	// Remove . at the end of a title
    	$title = preg_replace("/\.$/", "", marctrim($record->getField("245")->getSubfield("a")));
		$out .= '<a href="?library=' . $_GET['library'] . '&id=' . $bibid . '">' . $title . '</a>' . "\n";
    }
    if ($record->getField("245") && $record->getField("245")->getSubfield("b")) {
    	$out .= ' : <span class="subtitle">' . marctrim($record->getField("245")->getSubfield("b")) . "</span>\n";
    }
    if ($record->getField("245") && $record->getField("245")->getSubfield("c")) {
    	$out .= ' / <span class="author">' . marctrim($record->getField("245")->getSubfield("c")) . "</span>\n";
    }
    // Publication data
    if ($record->getField("260")) {
    	// Year
    	if ($record->getField("260")->getSubfield("c")) {
    		$out .= ' (<span class="year">' . marctrim($record->getField("260")->getSubfield("c")) . "</span>)\n";
    	}
    }

    return $out;
	
}

function get_detail($record) {

	$out = '<div class="recorddetail">';

	// Get the basic info anyway
    // Title
    if ($record->getField("245") && $record->getField("245")->getSubfield("a")) {
    	// Remove . at the end of a title
    	$title = preg_replace("/\.$/", "", marctrim($record->getField("245")->getSubfield("a")));
		$out .= '<p class="title">Tittel: ' . $title . "</p>\n";
    }
    if ($record->getField("245") && $record->getField("245")->getSubfield("b")) {
    	$out .= '<p class="subtitle">Undertittel: ' . marctrim($record->getField("245")->getSubfield("b")) . "</p>\n";
    }
    if ($record->getField("245") && $record->getField("245")->getSubfield("c")) {
    	$out .= '<p class="author">Forfatter: ' . marctrim($record->getField("245")->getSubfield("c")) . "</p>\n";
    }
    // Publication data
    if ($record->getField("260")) {
    	// Year
    	if ($record->getField("260")->getSubfield("c")) {
    		$out .= '<p class="year">Publisert: ' . marctrim($record->getField("260")->getSubfield("c")) . "</p>\n";
    	}
    }

	// Subjects
	$subjects = $record->getFields('6\d\d', true);
	if ($subjects) {
		$out .= '<p>Emner:</p>' . "\n";
		$out .= '<p>' . "\n";
		foreach ($subjects as $subject) {
	   		$out .= '' . marctrim($subject->getSubfield("a")) . '; ' . "\n";
	    }
	    $out .= '</p>' . "\n";
	}

	// Items
	if ($record->getField("850") && $record->getField("850")->getSubfield("a")) {
		$out .= '<p>Eksemplarer:</p><ul>';
		foreach ($record->getFields("850") as $item) {
			$out .= '<li>'. marctrim($item->getSubfield("a")) . ', ' . marctrim($item->getSubfield("c")) . '</li>' . "\n";
		}
		$out .= '</ul>';
	}

	// Link to OPAC
	if ($record->getField("996") && $record->getField("996")->getSubfield("u")) {
		$url = marctrim($record->getField("996")->getSubfield("u"));
		$out .= '<p><a href="'. $url . '">OPAC</a></p>' . "\n";
	}
	
	$out .= '</div>' . "\n";
	
	return $out;
	
}

?>