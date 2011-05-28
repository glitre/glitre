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

function oppnabib_detail_compact($record, $loggedin_user) { return oppnabib_detail($record, 'compact', $loggedin_user); }
function oppnabib_detail_full($record, $loggedin_user)    { return oppnabib_detail($record, 'full',    $loggedin_user); }

function oppnabib_detail($record, $style, $loggedin_user) {
	
  global $config;

  // Extract some data about the record
  // These will be sent to Oppna bibliotek if the book is not there yet
  $title = '';
  if ($record->getField("245") && $record->getField("245")->getSubfield("a")) {
    $title = urlencode(marctrim($record->getField("245")->getSubfield("a")));
  } 
  
  $authorfirstname = '';
  $authorlastname  = '';
  if ($record->getField("100") && $record->getField("100")->getSubfield("a")) {
    $author = marctrim($record->getField("100")->getSubfield("a"));
    if (substr_count($author, ',') > 0) {
      list($authorlastname, $authorfirstname) = explode(", ", $author);
      $authorlastname = urlencode($authorlastname);
      $authorfirstname = urlencode($authorfirstname);
    }
  }
  
  $isbn = '';
  if ($record->getField("020") && $record->getField("020")->getSubfield("a")) {
    $isbn = marctrim($record->getField("020")->getSubfield("a"));
  }
  
  // Get the ID of the book
  $idurl = $config['plugins']['oppnabib']['baseurl'] . "books?book[title]=$title&book[authorfirstname]=$authorfirstname&book[authorlastname]=$authorlastname";
  $book = simplexml_load_string(getOppnabib($idurl, true));
  $bookid = $book->book->id;
  
  if ($bookid) {
    // Get any data about the book identified by the ID
    $dataurl = $config['plugins']['oppnabib']['baseurl'] . "assessments?assessment[book_id]=" . $bookid;
    $bookdata = simplexml_load_string(getOppnabib($dataurl));
  }
  
  $out = '';
  $new_assessment_added = false;
  
  if ($style == 'full') {
  
	  $out = '<div class="oppnabib"><h2>Öppna bibliotek - ' . urldecode($title) . '</h2>' . "\n";
	  $out .= '<!-- bookid: ' . $bookid . ' -->' . "\n";
	  
	  // Do we have alogged in user? 
	  if (!empty($loggedin_user)) {
	  	
	  	  // Check if a user has added an opinion
	  	  if (!empty($_POST['oppnabib_assessment_header_input'])
	  	   || !empty($_POST['oppnabib_assessment_text_input']) 
	  	   || !empty($_POST['oppnabib_grade_input'])) {
	  	  	
	  	  	  $header = strip_tags($_POST['oppnabib_assessment_header_input']);
	  	  	  $text = strip_tags($_POST['oppnabib_assessment_text_input']);
	  	  	  // check that the grade is an integer, set to zero otherwise
	  	  	  $grade = 0;
	  	  	  if (is_int((int) strip_tags($_POST['oppnabib_grade_input']))) {
	  	  	  	// TODO: check that $grade is between 1 and 6
	  	  	    $grade = $_POST['oppnabib_grade_input'];
	  	  	  } 
	  	  	  // Collect the data that will be sent to Oppna bibliotek
	  	  	  $book = array();
	  	  	  if (!empty($bookid) && !empty($bookdata)) {
	  	  	    // This book is already in Oppna bibliotek, so we only need to send the ID
			    $book['assessment[grade]']= $grade;
			    $book['assessment[comment_header]']= $header;
			    $book['assessment[comment_text]']= $text;
			    $book['assessment[book_id]']= $bookid;
			    $book['assessment[published]'] = 1;
	  	      } else {
	  	  	    // This book is not in Oppna bibliotek, we need to add it
			    $book['assessment[grade]'] = $grade;
			    $book['assessment[comment_header]'] =  $header;
			    $book['assessment[comment_text]'] = $text;
			    $book['assessment[title]'] = $title;
			    $book['assessment[authorfirstname]'] = $authorfirstname;
			    $book['assessment[authorlastname]'] = $authorlastname;
			    $book['assessment[isbn]'] = $isbn;
			    $book['assessment[published]'] = 1;
	  	  	 }
	  	  	 $newassessment = simplexml_load_string(postOppnabib($book, $loggedin_user));
	  	  	 if ($newassessment->error) {
	  	  	    $out .= '<p class="error">Noe gikk galt: ' . "\n";
	  	  	    $out .= '"' . $newassessment->error . '"</p>' . "\n";
				$report = print_r($assessment, true);
	  	  	 	$out .= '<!-- ' . $report . ' -->';
	  	  	 } else {
	  	  	 	$out .= '<p>Takk for at du bidro med din vurdering!</p>' . "\n";
				$out .= format_assessment($header, $text, $grade, $loggedin_user);
				$report = print_r($newassessment, true);
	  	  	 	$out .= '<!-- ' . $report . ' -->';
	  	  	 	$new_assessment_added = true;
	  	  	 }
	  	  	  
	  	  // Create the form for adding an assessment  
	  	  } else {
	  	  	
			  $out .= '<h3>Si din mening</h3>' . "\n";
			  $out .= '<p>Du er logget inn som: ' . $loggedin_user . '</p>' . "\n";
			  $out .= '<form method="post">' . "\n";
			  $out .= 'Omtalens tittel:<br /><input type="text" name="oppnabib_assessment_header_input" size="40" /><br />' . "\n";
			  $out .= 'Omtale:<br /><textarea name="oppnabib_assessment_text_input" cols="40" rows="10" ></textarea><br />' . "\n";
			  $out .= '<select name="oppnabib_grade_input">' . "\n";
			  $out .= '<option value="">Gi karakter</option>' . "\n";
			  $out .= '<option value="1">&#10029;</option>' . "\n";
			  $out .= '<option value="2">&#10029;&#10029;</option>' . "\n";
			  $out .= '<option value="3">&#10029;&#10029;&#10029;</option>' . "\n";
			  $out .= '<option value="4">&#10029;&#10029;&#10029;&#10029;</option>' . "\n";
			  $out .= '<option value="5">&#10029;&#10029;&#10029;&#10029;&#10029;</option>' . "\n";
			  $out .= '<option value="6">&#10029;&#10029;&#10029;&#10029;&#10029;&#10029;</option>' . "\n";
			  $out .= '</select><br />' . "\n";
			  $out .= '<input type="submit">' . "\n";
			  $out .= '</form>' . "\n";
	  	  }
	  }
	  
	  // Display existing assessments
	  $out .= '<h3>Hva andre mener</h3>' . "\n";
	  
	  // $out .= '<p>Antall meninger: ' . $bookdata->hitcount . '</p>';
	  if ($bookdata->average_grade) {
	    $out .= '<p>Gjennomsnittlig karakter: <span class="oppnabib_average_grade">' . $bookdata->average_grade . '</span></p>' . "\n";
	  } else {
	  	if ($new_assessment_added == false) {
	  	  $out .= '<p>Bli den første til å mene noe om denne boka!</p>' . "\n";
	  	}
	  }
	  
	  // Output every assessment
	  if ($bookdata->assessment) {
  	    foreach ($bookdata->assessment as $thisassessment) {
	      $out .= '<p class="oppnabib_assessment">' . "\n";
	      $out .= format_assessment($thisassessment->comment_header, $thisassessment->comment_text, $thisassessment->grade, $thisassessment->username);
	      $out .= '(<span class="oppnabib_user_lib_name">' . $thisassessment->user_lib_name . '</span>)' . "\n";
	      $out .= "</p>\n";
	    }
	  }
	  
	  $out .= '</div>' . "\n";

  } elseif ($style == 'compact') {
  	
  	$hitcount = 0;
  	if ($bookdata->hitcount) {
  		$hitcount = $bookdata->hitcount;
  	}
    $out .= '<br /><span class="oppnabib_compact">Öppna bibliotek: <span class="oppnabib_hitcount">' . $hitcount . '</span> mening(er). ' . "\n";
    if ($bookdata->average_grade) {
      $out .= 'Gjennomsnitt: <span class="oppnabib_average_grade">' . $bookdata->average_grade . '</span>' . "\n";
    } 
    $out .= '</span>' . "\n";
  	
  }
  
  // DEBUG exit(print_r($bookdata));
  
  return $out;

}

function format_assessment($header, $text, $grade, $loggedin_user) {
	
	$out = '';

	if ($header != '' || $text != '') {
		if ($header != '') {
			$out .= '<strong class="oppnabib_assessment_header">' . $header . '</strong> ';
			if ($text != '') { $out .= '<br />'; }
		}
		if ($text != '') {
			$out .= '<span class="oppnabib_assessment_text">' . $text . '</span>';
		}
		$out .= "<br />\n";
	}
	$out .= '<span class="oppnabib_grade">' . str_repeat('&#10029;', $grade) . '</span> ';
	$out .= '<span class="oppnabib_username">' . $loggedin_user . '</span> ';
	
	return $out;
	
}

// Add a new assessment
function postOppnabib($data, $user) {
	
  global $config;

  $curl_opt = array(
    CURLOPT_URL            => $config['plugins']['oppnabib']['baseurl'] . 'assessments', 
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,     
    CURLOPT_HEADER         => false,    
    CURLOPT_FOLLOWLOCATION => true,     
    CURLOPT_ENCODING       => "",       
    CURLOPT_USERAGENT      => "Glitre", 
    CURLOPT_AUTOREFERER    => true,     
    CURLOPT_CONNECTTIMEOUT => 120,      
    CURLOPT_TIMEOUT        => 120,      
    CURLOPT_MAXREDIRS      => 10,       
    CURLOPT_HTTPHEADER     => array("Accept: application/xml"), 
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC, 
    CURLOPT_USERPWD        => $user . ":" . $config['plugins']['oppnabib']['userpassword'], 
    CURLOPT_POSTFIELDS     => $data, 
    CURLINFO_HEADER_OUT    => true, # for debug purposes
  );
  
  $ch = curl_init();
  curl_setopt_array($ch, $curl_opt);
  
  $content = curl_exec($ch);
  
  // Debug
  // $info = curl_getinfo($ch);
  // print_r($info);
  
  if (curl_errno($ch)) {
  	exit(curl_error($ch));
  } else {
    return $content;
  }
  curl_close($ch);
	
}

// Retrieve an existing assessment
function getOppnabib($url, $auth = false) {

  $curl_opt = array(
    CURLOPT_URL            => $url, 
    CURLOPT_RETURNTRANSFER => true,     
    CURLOPT_HEADER         => false,    
    CURLOPT_FOLLOWLOCATION => true,     
    CURLOPT_ENCODING       => "",       
    CURLOPT_USERAGENT      => "Glitre", 
    CURLOPT_AUTOREFERER    => true,     
    CURLOPT_CONNECTTIMEOUT => 120,      
    CURLOPT_TIMEOUT        => 120,      
    CURLOPT_MAXREDIRS      => 10,       
    CURLOPT_HTTPHEADER     => array("Accept: application/xml"), 
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC, 
  );
  
  $ch = curl_init();
  curl_setopt_array($ch, $curl_opt);

  // We only need to authenticate on the first request
  if ($auth) {
    curl_setopt($ch, CURLOPT_USERPWD, $config['plugins']['oppnabib']['username'] . ":" . $config['plugins']['oppnabib']['password']);
  }

  $content = curl_exec($ch);
  
  // Debug
  // $info = curl_getinfo($ch);
  // print_r($info);
  
  if (curl_errno($ch)) {
  	exit(curl_error($ch));
  } else {
    return $content;
  }
  curl_close($ch);
	
}

?>
