<?php echo('<?xml version="1.0" encoding="utf-8"?>'); echo("\n\n"); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="nb_NO" lang="nb_NO">
<head>
<title>Zsort</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>

<p style="text-align: center;">
Z39.50: 
<a href="?library=stavanger&system=Aleph (Z39.50)">Aleph</a>
<a href="?library=deich&system=Bibliofil (Z39.50)">Bibliofil</a>
<a href="?library=hig&system=BIBSYS (Z39.50)">BIBSYS</a>
<a href="?library=sksk&system=Koha (Z39.50)">Koha</a>
<a href="?library=kristiansund&system=Mikromarc (Z39.50)">Mikromarc</a>
Reindex
Tidemann
<br />
SRU: 
<a href="?library=higsru&system=BIBSYS (SRU)">BIBSYS</a>
<a href="?library=pode&system=Koha (SRU)">Koha</a>
</p>

<?php

/* 
This file is intended to test the different sorting behaviours of our target systems. 
*/

include('../inc.glitre.php');

if (!empty($_GET['library'])) {
  if (!empty($_GET['system'])) {
    echo('<h1>' . $_GET['system'] . '</h1>');
  }
  $args = array(
    'q' => 'oslo', 
    'library' => $_GET['library'], 
    'format' => 'plugin.simple', 
    'page' => 1,
    'per_page' => 20,
  );
  echo(glitre_search($args));
}

?>

</body>
</html>