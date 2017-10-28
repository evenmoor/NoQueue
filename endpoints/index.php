<?php
	header('Access-Control-Allow-Origin: *');  

	/*

		Hack-a-thon sensor data endpoint

		Dev: Joshua Moor
		Last Modified: 10/21/2017

		Change Log: Created Initial code 10/20/2017
		-- added predictive scheduling support 10/21/2017

	*/

	require('sensor.php');
	$sensor = new sensor();

	switch($_GET['action']){
		case "log":
			$log_attempt = $sensor->log($_GET['id'], $_GET['state']);
		break;

		case "getMLStructSensor":
			header('Content-Type: application/json');
			echo json_encode($sensor->getMachineLearningStructSensor($_GET['id']), JSON_NUMERIC_CHECK);
		break;

		case "getMLStructBuilding":
			header('Content-Type: application/json');
			echo json_encode($sensor->getMachineLearningStructBuilding($_GET['id']), JSON_NUMERIC_CHECK);
		break;

		case "getMLPredictiveSchedulingBuilding":
			header('Content-Type: application/json');
			if(isset($_GET['test'])){
				echo json_encode($sensor->getMLPredictiveSchedulingBuildingTest($_GET['id'], $_GET['status'], $_GET['test']), JSON_NUMERIC_CHECK);
			}else{
				echo json_encode($sensor->getMLPredictiveSchedulingBuilding($_GET['id'], $_GET['status']), JSON_NUMERIC_CHECK);
			}
		break;

		case "getCurrentBuildingState":
			header('Content-Type: application/json');
			echo json_encode($sensor->getCurrentBuildingState($_GET['ids']), JSON_NUMERIC_CHECK);
		break;

		case "setDoorStatus":
			$sensor->setDoorStatus($_GET['id'], $_GET['status']);
		break;

		case "getDoorDetails":
			header('Content-Type: application/json');
			echo json_encode($sensor->getDoorDetails($_GET['id']), JSON_NUMERIC_CHECK);
		break;
	}


?>