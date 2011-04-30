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

// Dummy data
if (!empty($_GET['dummy'])) {

  if (!empty($_GET['q'])) {
    if (!empty($_GET['format'])) {
      echo('
      <div id="dummyresult1"><div class="toolbar"><h1>Demotittel 1</h1><a class="button back" href="#">Tilbake</a></div><div><ul><li>Tittel: Demotittel 1</li><li>Forfatter: Demoforfatter</li><li>Utgitt: 1999</li></ul></div></div>
      <div id="dummyresult2"><div class="toolbar"><h1>Demotittel 2</h1><a class="button back" href="#">Tilbake</a></div><div><ul><li>Tittel: Demotittel 2</li><li>Forfatter: Demoforfatter</li><li>Utgitt: 1998</li></ul></div></div>
      <div id="dummyresult3"><div class="toolbar"><h1>Demotittel 3</h1><a class="button back" href="#">Tilbake</a></div><div><ul><li>Tittel: Demotittel 3</li><li>Forfatter: Demoforfatter</li><li>Utgitt: 1997</li></ul></div></div>
      ');
      exit;
    } else {
      echo('
      <h2>Treffliste</h2>
      <ul class="rounded">
      <li><a class="flip searchresult" href="#dummyresult1">Treff 1 for ' . $_GET['q'] . '</a></li>
      <li><a class="flip searchresult" href="#dummyresult2">Treff 2</a></li>
      <li><a class="flip searchresult" href="#dummyresult3">Treff 3</a></li>
      </ul>
      ');
      exit;
    }
  }

  if (!empty($_GET['id'])) {
    echo('test');
    exit;
  }

}

// Check that we have all the arguments we need
if (empty($_GET['library'])) {
  echo('Missing parameter: library');
  exit;
}
if (empty($_GET['q']) && empty($_GET['id'])) {
  echo('Missing parameter: q OR id');
  exit;
}

// Check that page is a number, set it to 0 otherwise
if (!empty($_GET['page']) && !is_int((int) $_GET['page'])) {
	$_GET['page'] = 0;
}

$data = '';

// Search
if (!empty($_GET['q']) && !empty($_GET['library'])) {
  $args = array(
    'q' => $_GET['q'], 
    'library'       => $_GET['library'], 
    'format'        => $_GET['format']        ? $_GET['format']     : 'simple',
    'page'          => $_GET['page']          ? $_GET['page']       : 0,
    'sort_by'       => $_GET['sort_by']       ? $_GET['sort_by']    : 'year',
    'sort_order'    => $_GET['sort_order']    ? $_GET['sort_order'] : 'descending', 
    'content_type'  => true, 
    // Pass an ID for the currently logged in user to Glitre
    // It is the responsibility of the calling application to authenticate the user before passing
    // her ID to Glitre. Glitre has no way to know about valid and invalid users, except being told
    // about them by the calling application. 
    'loggedin_user' => $_GET['loggedin_user'] ? 'dummyuser'         : '',
  );
  $data = glitre_search($args);

// Display one record	
} elseif (!empty($_GET['id'])) {
  $args = array(
    'id'            => $_GET['id'], 
    'library'       => $_GET['library'], 
    'format'        => $_GET['format']        ? $_GET['format'] : 'simple',
    // Pass an ID for the currently logged in user to Glitre - see comments above! 
    'loggedin_user' => $_GET['loggedin_user'] ? 'dummyuser'     : '',
  );
  $data = glitre_search($args);
}

// Do the actual output
if ($data && !empty($data['content_type'])) { 
  header('Content-type: ' . $data['content_type']);
  echo($data['data']);
} else {
  echo($data);
}

?>
