<?php

include('../inc.glitre.php');

// Dummy data
if (!empty($_GET['dummy'])) {

  if (!empty($_GET['q'])) {
    echo('
    <ul class="rounded">
    <li><a class="flip" href="#dummyresult1">Treff 1 for ' . $_GET['q'] . '</a></li>
    <li><a class="flip" href="#dummyresult2">Treff 2</a></li>
    <li><a class="flip" href="#dummyresult3">Treff 3</a></li>
    </ul>
    ');
    exit;
  }

  if (!empty($_GET['id'])) {
    echo(glitre_search($args));
  }

}

// Search
if (!empty($_GET['q'])) {
  $args = array(
    'q' => $_GET['q'], 
    'library' => $_GET['library'], 
    'format' => $_GET['format'], 
    'page' => $_GET['page'],
    'per_page' => $_GET['per_page']
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
