<?php

// Thanks to http://stackoverflow.com/questions/1728937/how-to-plot-a-realtime-graph-histogram-using-data-obtained-in-a-text-file

if (!empty($_GET['data'])) {
	
	// poll.php
	$index = (isset($_GET['index'])) ? (int)$_GET['index'] : 0;
	$file = fopen('/tmp/glitrecache.log', 'r');
	$data = array(
	    'index' => null,
	    'data'  => array()
	);
	if ($file) {
		// move forward to the designated position
		fseek($file, $index, SEEK_SET);
		while (!feof($file)) {
		    list($time, $page, $library, $q, $result) = explode("\t", trim(fgets($file)), 5);
		    if ($time) {
		    	$data['data'][] = array('time' => $time, 'page' => $page, 'library' => $library, 'q' => $q, 'result' => $result);
		    }
		}
		// set the new index
		$data['index'] = ftell($file);
		fclose($file);
		
		header('Content-Type: application/json');
		echo json_encode($data);
		exit();
	}
		
} else {
	
?>

<html>
<head>
<title>Caching visualized</title>
<script type="text/javascript" src="http://www.google.com/jsapi?key=ABQIAAAANSKgnCUB21DQsQbD6X_50xQDsLG1UMFxQYnA3JSfnYmW00iVvhQOQay4Fo9Y4EykGA07Ezhb4VAacQ"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script src="flot/jquery.flot.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {

	// Set up Flot
	var options = {
	    lines: { show: true },
	    points: { show: true },
	    xaxis: { tickDecimals: 0, tickSize: 1 }, 
	    grid: { show: true }, 
	};
	var placeholder = $('#cachepercent');
	var data = new Array();
	data['label'] = 'Results';
	var results = new Array();
	results['raw'] = 0;
	results['sorted'] = 0;
	results['nocache'] = 0;
	
	// the jQuery code to poll the script
	var current = 0;
	
	// Call pollData now
	pollData();
	// call pollData() every X x 1000 seconds
	var timer = window.setInterval(pollData, 5000);

	function pollData() {
	    $.getJSON('cache.php', { 'data': 1, 'index': current }, function(data) {
	        current = data.index;
	        if (data.data.length > 0) {
	        	for (var i= 0; i < data.data.length; i++) {
		            var time = data.data[i].time;
		            var page = data.data[i].page;
		            var library = data.data[i].library;
		            var q = data.data[i].q;
		            var result = data.data[i].result;
		            // do your plotting here
		            $('#stack').prepend('<li class="' + result + '" style="visbility: hidden;">' + time + ' | ' + page + ' | ' + library + ' | ' + q + ' | ' + result + '</li>').fadeIn("slow");
		            // Do calculations
		            results[result] = results[result] + 1;
	        	}
	        }
	        $.plot(placeholder, [
		        {
		        	label: "nocache", 
		            data: [[1, results['nocache']]],
		            bars: { show: true, barWidth: 0.5, align: "center" }, 
		            color: "red"
		        },
		        {
		        	label: "raw", 
		            data: [[2, results['raw']]],
		            bars: { show: true, barWidth: 0.5, align: "center" }, 
		            color: "blue"
		        },
		        {
		        	label: "sorted", 
		            data: [[3, results['sorted']]],
		            bars: { show: true, barWidth: 0.5, align: "center" }, 
		            color: "green"
		        }
    		], options);
	    });
	}

});
</script>
<style type="text/css">
.raw { color: blue; }
.sorted { color: green; }
.nocache { color: red }
</style>
</head>
<body>
<h1>Caching visualized</h1>
<div id="cachepercent" style="width:600px; height:300px;"></div>
<ul id="stack"></ul>
</body>
</html>

<?php

}

?>
