<?php

function openlibrary_image_get_image_url_s($record) { return _get_image_url($record, 'S'); }
function openlibrary_image_get_image_url_m($record) { return _get_image_url($record, 'M'); }
function openlibrary_image_get_image_url_l($record) { return _get_image_url($record, 'L'); }

function _get_image_url($record, $size) {
	
  if ($record->getField("020") && $record->getField("020")->getSubfield("a")) {
    $isbn = marctrim($record->getField("020")->getSubfield("a"));
    $isbn = str_replace('-', '', $isbn);
    $isbn = str_replace(' ', '', $isbn);
    return '<img src="http://covers.openlibrary.org/b/isbn/' . $isbn . '-' . $size . '.jpg" class="openlibrary_image cover_image_' . $size . '">';
  } else {
    return false;	
  }
	
}

?>