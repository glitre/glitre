<?php 

/* 

Copyright 2010-2011 ABM-utvikling/Nasjonalbiblioteket

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

include('../inc.glitre.php'); 

header('Content-Type: text/html; charset=utf-8');
echo('<?xml version="1.0" encoding="utf-8"?>' . "\n\n"); 

echo('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n"); 
echo('<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">' . "\n");
echo("<head>\n<title>Glitre test</title>\n" . "\n");
echo('<body>' . "\n");

echo('
<div class="searchform">
<form method="get" action="">
<p>
<input type="text" name="q" value="' . $_GET['q'] . '" />
<input type="hidden" name="library" value="hig" />
<!-- input type="hidden" name="sorter" value="aar" / -->
<!-- input type="hidden" name="orden" value="synk" / -->
<input type="submit" value="Search" />
</p>
</form>
</div>' . "\n");

// Search
if (!empty($_GET['q'])) {
  $args = array(
    'q' => $_GET['q'], 
    'library' => $_GET['library'],
    'format' => 'plugin.simple', 
    'page' => $_GET['side'] ? $_GET['side'] : 1,
    'per_page' => 4
  );
  echo(glitre_search($args));
}

// Display one record	
if (!empty($_GET['id'])) {
  $args = array(
    'id' => $_GET['id'], 
    'library' => $_GET['library'],
    'format' => 'plugin.simple'
  );
  echo(glitre_search($args));
}

echo("</body>\n</html>");

?>
