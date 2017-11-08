<?
	// allow access for AJAX requests
	header('Access-Control-Allow-Origin: //yetilair.com');  

	//load in noqueue system
	require_once(__DIR__.'/classes/noqueue.class.php');

	//build out the request params as an associative array for use within the classes
	$request = array(
		'GET' => $_GET
		,'POST' => $_POST
	);

	$dev_mode = true;

	//start the system
	$system = new noqueue($dev_mode, $request);
	echo $system->getReturn();
?>