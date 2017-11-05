<?
	//primary controller class for NoQueue
	class noqueue{
		private $version = "1.0"; //current version of NoQueue

		//utility resources
		private $db_connection; //handler for connections to the databases
		private $request; //request variables
		private $valid_request = false;
		//container for formatting strings
		private $formats = array(
			'timestamp' => 'Y-m-d H:i:s'
		);
		private $valid_request_keys = array('method', 'id', 'key', 'request');//list of valid keys which make up a request

		// return variables
		private $return_string; //holds the return data
		private $return_type; //optional format type

		// ============= Public Functions =============
		// return the return values (if any)
		public function getReturn(){
			if($this->return_string != ''){//check for return string
				return $this->formatReturn($this->return_type);
			}else{//no return string
				return null;
			}//end check
		}//end getReturn


		// ============= Private Functions =============
		//executeRequest the current request
		private function executeRequest(){
			if($this->valid_request){
				switch($this->request['method']){
					case 'sensor': //sensor methods
						$this->return_type = 'raw';

						require_once('sensor.class.php');
						$sensor = new sensor($this->request, $this->db_connection);

						$this->return_string = $sensor->executeRequest();
					break;//sensor

					case 'site': // site methods
						echo "<h2>Site Method</h2>";
					break;

					case 'machine': //machine learning methods
						echo "<h2>Machine Method</h2>";
					break; //getData
				}//end action
			}//request check
		}//end executeRequest

		//handles the formatting of the return values including headers
		private function formatReturn($type){
			switch(strtoupper($type)){
				case 'JSON':
					header('Content-Type: application/json');
					return json_encode($this->return_string);
				break;//JSON formatting

				default://no formatting
					return $this->return_string;
			}//end return type 
		}//end formatReturn

		//validate and standardize a submitted request
		private function validateRequest($request){
			$return_request = array();
			$this->valid_request = true;
			
			foreach($request['GET'] as $key => $value){//parse get values for keys
				if(in_array($key, $this->valid_request_keys)){
					$return_request[$key] = $value;
				}else{
					$this->valid_request = false;
					break;
				}
			}

			if($this->valid_request){
				foreach($request['POST'] as $key => $value){//parse the post values and overwrite any matching get keys
					if(in_array($key, $this->valid_request_keys)){
						$return_request[$key] = $value;
					}else{
						$this->valid_request = false;
						break;
					}
				}
			}

			if($this->valid_request){
				return $return_request;
			}else{
				return array();
			}
		}//end validateRequest

		// ============= Database Functions =============
		//connect database for use
		private function connectDatabase($db_config){
			$db_handler = $db_config['type'].'_handler';

			//generic class
			require_once('db_handler.class.php');
			//db specific class
			require_once('/db_types/'.$db_handler.'.class.php');

			$this->db_connection = new $db_handler($db_config);
		}//end connectDatabase

		// ============= Constructors =============
		public function __construct($dev_mode, $request){
			// echo "<h1>Starting NoQueue".$this->version."</h1>";
			
			$this->request = $this->validateRequest($request);
			// print_r($this->request);

			if($this->valid_request){
				// perform initial configuration
				$system_config = file_get_contents('config/config.json');
				$system_config = json_decode($system_config);

				//load a config
				if($dev_mode){
					$database_config = $system_config->system->dev->database;
				}else{
					$database_config = $system_config->system->live->database;
				}

				//build db_config array
				$db_config = array(
					'location' => $database_config->location,
					'name' => $database_config->name,
					'username' => $database_config->username,
					'password' => $database_config->password,
					'type' => $database_config->type
				);

				$this->connectDatabase($db_config);
				$this->executeRequest();
			}
		}//end constructor

		// ============= Deconstructors =============
		public function __destruct(){
			
		}//end destructor
	}

?>