<?php

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

// Search
if (!empty($_GET['q']) && !empty($_GET['library'])) {
  $args = array(
    'q' => $_GET['q'], 
    'library'    => $_GET['library'], 
    'format'     => $_GET['format']     ? $_GET['format']     : 'plugin.simple',
    'page'       => $_GET['page']       ? $_GET['page']       : 1,
    'per_page'   => $_GET['per_page']   ? $_GET['per_page']   : 10,
    'sort_by'    => $_GET['sort_by']    ? $_GET['sort_by']    : 'year',
    'sort_order' => $_GET['sort_order'] ? $_GET['sort_order'] : 'descending'
  );
  echo(glitre_search($args));
}

// Display one record	
if (!empty($_GET['id'])) {
  $args = array(
    'id' => $_GET['id'], 
    'library' => $_GET['library'], 
    'format' => $_GET['format'], 
  );
  echo(glitre_search($args));
}

?>
