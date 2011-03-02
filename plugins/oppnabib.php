<?php

function oppnabib_detail($record) {
	
  global $config;

  $title = urlencode(marctrim($record->getField("245")->getSubfield("a")));
  $authorfirstname = 'Tom';
  $authorlastname = 'Clancy';
  
  $out = '<div class="oppnabib"><h2>Öppna bibliotek - ' . urldecode($title) . '</h2>';

  // Get the ID of the book
  $idurl = $config['plugins']['oppnabib']['baseurl'] . "books?book[title]=$title&book[authorfirstname]=$authorfirstname&book[authorlastname]=$authorlastname";
  $book = simplexml_load_string(getOppnabib($idurl, true));
  $id = $book->book->id;
  
  // Get any data about the book identified by the ID
  $dataurl = $config['plugins']['oppnabib']['baseurl'] . "assessments?assessment[book_id]=" . $id;
  $bookdata = simplexml_load_string(getOppnabib($dataurl));
  
  // $out .= '<p>Antall meninger: ' . $bookdata->hitcount . '</p>';
  $out .= '<p>Gjennomsnittlig karakter: <span class="oppnabib_average_grade">' . $bookdata->average_grade . '</span></p>';
  
  // Output every assessment
  foreach ($bookdata->assessment as $assessment) {
    $out .= '<p class="oppnabib_assessment">';
    $header = $assessment->comment_header;
    $text   = $assessment->comment_text;
    if ($header != '' || $text != '') {
    	if ($header != '') {
    	  $out .= '<strong class="oppnabib_assessment_header">' . $header . '</strong> ';
    	}
    	if ($header != '') {
    	  $out .= '<span class="oppnabib_assessment_text">' . $text . '</span>';
    	}
    	$out .= "<br />\n";
    }
    $out .= '<span class="oppnabib_grade">' . str_repeat('&#10029;', $assessment->grade) . '</span> ';
    $out .= '<span class="oppnabib_username">' . $assessment->username . '</span> ';
    $out .= '(<span class="oppnabib_user_lib_name">' . $assessment->user_lib_name . '</span>)';
    $out .= "</p>\n";
  }
  
  $out .= '</div>';
  
  // DEBUG exit(print_r($bookdata));
  
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