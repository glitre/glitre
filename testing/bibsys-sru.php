<?php

$sru_url = 'http://sru.bibsys.no/search/biblio?version=1.2&operation=searchRetrieve&startRecord=1&maximumRecords=10&query=ibsen&recordSchema=marcxchange';

// Retrieve the data
$sru_data = file_get_contents($sru_url) or exit("Feil");

// Prepare the data for use with File_MARC
$sru_data = str_replace("<record xmlns=\"http://www.loc.gov/MARC21/slim\">", "<record>", $sru_data);
preg_match_all('/(<record.*?>.*?<\/record>)/si', $sru_data, $matches);
$marcxml = implode("\n\n", $matches[0]);
$marcxml = '<?xml version="1.0" encoding="utf-8"?>' . "\n<collection>\n$marcxml\n</collection>";

// FIXME Ugly hack - special treatment for data from BIBSYS
// This should probably be handled more gracefully by actually interpreting the
// namespace(s) properly...
$marcxml = str_replace('<marc:',  '<',  $marcxml);
$marcxml = str_replace('</marc:', '</', $marcxml);

header ("Content-Type:text/xml");
echo($marcxml);

?>
