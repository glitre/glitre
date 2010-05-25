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

header('Content-Type: text/html; charset=utf-8');
echo('<?xml version="1.0" encoding="utf-8"?>' . "\n\n"); 

include('inc.config.php');
$config = get_config('hig');
include('inc.glitre.php');
require('File/MARCXML.php');

echo('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n"); 
echo('<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">' . "\n");
echo("<head>\n<title>Glitre test</title>\n" . "\n");
echo('<body>' . "\n");
echo('<div id="content">' . "\n");

echo('
<div class="searchform">
<form method="get" action="search.php">
<p>
<input type="text" name="q" value="' . $_GET['q'] . '" />
<input type="hidden" name="lib" value="hig" />
<input type="hidden" name="sorter" value="aar" />
<input type="hidden" name="orden" value="synk" />
<input type="submit" value="Search" />
</p>
</form>
</div>' . "\n");

// q eller item må være satt
// bib må være satt, og må være en nøkkel i $config['lib']
if ((!empty($_GET['q']) || !empty($_GET['id'])) && !empty($_GET['lib'])) {

	echo('<div id="main">' . "\n");
	
	/* TREFFLISTE */
	
	// Sortering
	if (!empty($_GET['q'])) {
	
		// Søk
		if (!empty($_GET['q'])) {
			echo('<div id="treffliste">' . "\n");
			$q = masser_input($_GET['q']);
			$query = '';
			if (!empty($config['lib']['sru'])) {
				// SRU
				$qu = urlencode($q);
				$query = $qu;
			} else {
				// Z39.50
				$query = "any=$q";
			}
			echo(podesearch($query));
			echo('</div>' . "\n");
		}
	}

	// Postvisning	
	if (!empty($_GET['id'])) {
		echo(postvisning($_GET['id']));
	}

	// Avslutter div main
	echo('</div>');

} else {

	echo('default');
	
}

// Avslutter div content
echo('</div>' . "\n");

echo("</body>\n</html>");

?>