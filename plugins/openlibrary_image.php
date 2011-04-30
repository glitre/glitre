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

function openlibrary_image_get_image_url_s($record, $loggedin_user) { return _get_image_url($record, 'S'); }
function openlibrary_image_get_image_url_m($record, $loggedin_user) { return _get_image_url($record, 'M'); }
function openlibrary_image_get_image_url_l($record, $loggedin_user) { return _get_image_url($record, 'L'); }

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
