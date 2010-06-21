<?php

include('../inc.glitre.php');

// Search
if (!empty($_GET['q']) && !empty($_GET['library']) && !empty($_GET['format'])) {
  $args = array(
    'q' => $_GET['q'], 
    'library' => $_GET['library'], 
    'format' => $_GET['format'], 
    'page' => $_GET['page'] ? $_GET['page'] : 1,
    'per_page' => $_GET['per_page'] ? $_GET['per_page'] : 10
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
