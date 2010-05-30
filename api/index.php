<?php

include('inc.config.php');
include('inc.glitre.php');
$config = get_config('hig');

// Search
if (!empty($_GET['q'])) {
	echo(glitre_search($_GET['q']));
}

// Display one record	
if (!empty($_GET['id'])) {
	echo(glitre_record($_GET['id']));
}

?>