<?
	class sensor{
		private $db_connection; //handler for connections to the databases
		private $request; //request variables
		private $valid_sensor = false; //boolean indicating whether or not a given sensor has passed a validity check
		private $sensor_id; //id number of the sensor

		// ============= Public Functions =============
		public function executeRequest(){
			if($this->valid_sensor){
				switch($this->request['request']){
					case 'getSleepTime':
						return 60;
						// number of minutes (up to 60) to sleep before this sensor needs to be active
					break;

					case 'setStatus':
						// set a status variable associated with this sensor
					break;

					case 'setState':
						// set a state assoicated with this sensor
					break;
				}//end request switch
			}//end valid sensor check
		}//end executeRequest

		// ============= Private Functions =============
		private function validateSensor(){
			$check = $this->db_connection->query('SELECT sensor_id
													FROM tbl_sensors
													WHERE sensor_id = "'.$this->db_connection->clean($this->request['id']).'"
														AND sensor_key = "'.$this->db_connection->clean($this->request['key']).'"
													LIMIT 1');
			if(count($check) == 1){
				$this->valid_sensor = true;
				$this->sensor_id = $check[0]['sensor_id'];
			}
		}//end validat sensor

		// ============= Constructors =============
		public function __construct($request, $db_connection){
			$this->db_connection = $db_connection;
			$this->request = $request;
			$this->validateSensor();
		}//end constructor
	}
?>