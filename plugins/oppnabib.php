<?php

function oppnabib_detail_compact($record, $loggedin_user) { return oppnabib_detail($record, 'compact', $loggedin_user); }
function oppnabib_detail_full($record, $loggedin_user)    { return oppnabib_detail($record, 'full',    $loggedin_user); }

function oppnabib_detail($record, $style, $loggedin_user) {
	
  global $config;

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
    }
  }
  // DEBUG exit("$authorlastname $authorfirstname");
  
  // Get the ID of the book
  $idurl = $config['plugins']['oppnabib']['baseurl'] . "books?book[title]=$title&book[authorfirstname]=$authorfirstname&book[authorlastname]=$authorlastname";
  $book = simplexml_load_string(getOppnabib($idurl, true));
  $id = $book->book->id;
  
  // Get any data about the book identified by the ID
  $dataurl = $config['plugins']['oppnabib']['baseurl'] . "assessments?assessment[book_id]=" . $id;
  $bookdata = simplexml_load_string(getOppnabib($dataurl));
  
  $out = '';
  
  if ($style == 'full') {
  
	  $out = '<div class="oppnabib"><h2>Öppna bibliotek - ' . urldecode($title) . '</h2>';
	  
	  if (!empty($loggedin_user)) {
	  	  // Check if a user has added an opinion
	  	  if (!empty($_POST['oppnabib_assessment_header_input'])) {
	  	  	  $header = strip_tags($_POST['oppnabib_assessment_header_input']);
	  	  	  $text = strip_tags($_POST['oppnabib_assessment_text_input']);
	  	  	  // check that the grade is an integer, set to zero otherwise
	  	  	  $grade = 0;
	  	  	  if (is_int(strip_tags($_POST['oppnabib_grade_input']))) {
	  	  	    $grade = $_POST['oppnabib_grade_input'];
	  	  	  } 
	  	  	  // This is probably not needed, since the user's input will be in the list below
	  	  	  $out .= format_assessment($header, $text, $grade, $loggedin_user);
	  	  } else {
			  $out .= '<h3>Si din mening</h3>';
			  $out .= '<p>Du er logget inn som: ' . $loggedin_user . '</p>';
			  $out .= '<form method="post">';
			  $out .= 'Omtalens tittel:<br /><input type="text" name="oppnabib_assessment_header_input" size="40" /><br />';
			  $out .= 'Omtale:<br /><textarea name="oppnabib_assessment_text_input" cols="40" rows="10" ></textarea><br />';
			  $out .= '<select name="oppnabib_grade_input">';
			  $out .= '<option value="">Gi karakter</option>';
			  $out .= '<option value="1">&#10029;</option>';
			  $out .= '<option value="2">&#10029;&#10029;</option>';
			  $out .= '<option value="3">&#10029;&#10029;&#10029;</option>';
			  $out .= '<option value="4">&#10029;&#10029;&#10029;&#10029;</option>';
			  $out .= '<option value="5">&#10029;&#10029;&#10029;&#10029;&#10029;</option>';
			  $out .= '<option value="5">&#10029;&#10029;&#10029;&#10029;&#10029;&#10029;</option>';
			  $out .= '</select><br />';
			  $out .= '<input type="submit">';
			  $out .= '</form>';
	  	  }
	  }
	  
	  $out .= '<h3>Hva andre mener</h3>';
	  
	  // $out .= '<p>Antall meninger: ' . $bookdata->hitcount . '</p>';
	  if ($bookdata->average_grade) {
	    $out .= '<p>Gjennomsnittlig karakter: <span class="oppnabib_average_grade">' . $bookdata->average_grade . '</span></p>';
	  } else {
	  	$out .= '<p>Bli den første til å mene noe om denne boka!</p>';
	  }
	  
	  // Output every assessment
	  foreach ($bookdata->assessment as $assessment) {
	    $out .= '<p class="oppnabib_assessment">';
	    $out .= format_assessment($assessment->comment_header, $assessment->comment_text, $assessment->grade, $assessment->username);
	    $out .= '(<span class="oppnabib_user_lib_name">' . $assessment->user_lib_name . '</span>)';
	    $out .= "</p>\n";
	  }
	  
	  $out .= '</div>';

  } elseif ($style == 'compact') {
  	
    $out .= '<br /><span class="oppnabib_compact">Öppna bibliotek: <span class="oppnabib_hitcount">' . $bookdata->hitcount . '</span> mening(er). ';
    if ($bookdata->average_grade) {
      $out .= 'Gjennomsnitt: <span class="oppnabib_average_grade">' . $bookdata->average_grade . '</span>';
    } 
    $out .= '</span>';
  	
  }
  
  // DEBUG exit(print_r($bookdata));
  
  return $out;

}

function format_assessment($header, $text, $grade, $loggedin_user) {
	
	$out = '';

	if ($header != '' || $text != '') {
		if ($header != '') {
			$out .= '<strong class="oppnabib_assessment_header">' . $header . '</strong> ';
		}
		if ($header != '') {
			$out .= '<span class="oppnabib_assessment_text">' . $text . '</span>';
		}
		$out .= "<br />\n";
	}
	$out .= '<span class="oppnabib_grade">' . str_repeat('&#10029;', $grade) . '</span> ';
	$out .= '<span class="oppnabib_username">' . $loggedin_user . '</span> ';
	
	return $out;
	
}

function getOppnabib($url, $auth = false) {

  // Session
  // session_start();
  // if(isset($_SESSION['login']) ) {
  //   session_unregister($_SESSION);
  //   session_unset();
  // }

  $curl_opt = array(
    CURLOPT_URL            => $url, 
    CURLOPT_RETURNTRANSFER => true,     // return web page
    CURLOPT_HEADER         => false,    // don't return headers
    CURLOPT_FOLLOWLOCATION => true,     // follow redirects
    CURLOPT_ENCODING       => "",       // handle all encodings
    CURLOPT_USERAGENT      => "Glitre", 
    CURLOPT_AUTOREFERER    => true,     // set referer on redirect
    CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
    CURLOPT_TIMEOUT        => 120,      // timeout on response
    CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
    CURLOPT_HTTPHEADER     => array("Accept: application/xml"), 
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC, 
  );
  
  $ch = curl_init();
  curl_setopt_array($ch, $curl_opt);

  // We only need to authenticate on the first request
  if ($auth) {
    curl_setopt($ch, CURLOPT_USERPWD, $config['plugins']['oppnabib']['username'] . ":" . $config['plugins']['oppnabib']['password']);
  }

  if (curl_errno($ch)) {
  	exit(curl_error($ch));
  } else {
    $content = curl_exec( $ch );
    return $content;
  }
  curl_close( $ch );
	
}

?>