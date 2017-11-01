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
		//execute the current request
		private function execute(){
			if($this->valid_request){
				switch($this->request['method']){
					case 'sensor': //sensor methods
						$this->return_type = 'json';
						$this->return_string = array("foo" => "bar");
						// echo "<h2>Sensor Method</h2>";
					break;//sensor

					case 'report': // reporting methods
						echo "<h2>Report Method</h2>";
					break;

					case 'getData': //get data methods
						echo "<h2>Get Data Method</h2>";
					break; //getData
				}//end action
			}//request check
		}//end execute

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
			
			if(isset($request['GET']['method'])){
				$return_request['method'] = $request['GET']['method'];
			}

			if($this->valid_request){
				return $return_request;
			}else{
				return array();
			}
		}//end validateRequest

		// ============= Constructors =============
		public function __construct($request){
			// echo "<h1>Starting NoQueue".$this->version."</h1>";
			
			$this->request = $this->validateRequest($request);
			// print_r($this->request);

			if($this->valid_request){
				$this->execute();
			}
		}//end constructor

		// ============= Deconstructors =============
		public function __destruct(){
			
		}//end destructor
	}

?>