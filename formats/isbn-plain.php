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

function format($records, $num_of_records, $first_record, $last_record) {
	
	$out = '';

	foreach ($records as $rec) {
		$isbns = $rec->getFields('020');
		if ($isbns) {
			foreach ($isbns as $isbn) {
				$thisisbn = marctrim($isbn->getSubfield("a"));
				if (strlen($thisisbn) >= 10) {
					$out .= "$thisisbn\n";	
				}
	    	}
		}
	}
	
	$ret = array(
		'data' => $out, 
		'content_type' => 'text/plain'
	);	
	return $ret;

}

?>
