<?php

include('../inc.config.php');
include('../inc.glitre.php');
$config = get_config('hig');

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
