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
					case 'getSleepTime'://calculate the amount of time to put the sensor to sleep in order to save power
						$time_to_sleep = 0;
						$now = strtotime(date('m/d/y H:i:s'));
						$today = date('N');
						$tomorrow = $today + 1;
						if($tomorrow > 7){//ISO-8601
							$tomorrow = 1;
						}

						$time_query = 'SELECT b.building_open_'.$today.'
											,b.building_open_'.$tomorrow.'
											,b.building_close_'.$today.'
										FROM tbl_sensors s
											INNER JOIN tbl_buildings b ON s.sensor_building_fk = b.building_id
										WHERE s.sensor_id = "'.$this->db_connection->clean($this->sensor_id).'"
										LIMIT 1';

						$time_query = $this->db_connection->query($time_query);
						$time_query = $time_query[0];

						$open_today = strtotime(date('m/d/y').' '.$time_query['building_open_'.$today]);
						$close_today = strtotime(date('m/d/y').' '.$time_query['building_close_'.$today]);
						$open_tomorrow = strtotime(date('m/d/y').' '.$time_query['building_open_'.$tomorrow].' +1 day');

						if($now < $close_today){
							if($now < $open_today){
								$time_to_sleep = $open_today - $now;
							}else{
								$time_to_sleep = 0;
							}
						}else{
							$time_to_sleep = $open_tomorrow - $now;
						}

						$time_to_sleep = ceil($time_to_sleep / 60);//convert to minutes and round up
						if($time_to_sleep > 60){//sensors can only sleep for an hour at a time
							$time_to_sleep = 60;
						}

						return $time_to_sleep;
					break;

					case 'setStatus'://set a status variable associated with this sensor
						$status_query = 'INSERT INTO tbl_sensor_status_log(sensor_status_log_sensor_fk
																		,sensor_status_log_status_fk)
											VALUES("'.$this->db_connection->clean($this->sensor_id).'"
													,"'.$this->db_connection->clean($this->request['status']).'")';
						$status_query = $this->db_connection->query($status_query);
					break;

					case 'setState'://sets a state for a sensor
						$state_query = 'INSERT INTO tbl_sensor_state_log(sensor_state_log_sensor_fk
																		,sensor_state_log_state_fk)
											VALUES("'.$this->db_connection->clean($this->sensor_id).'"
													,"'.$this->db_connection->clean($this->request['state']).'")';
						$state_query = $this->db_connection->query($state_query);
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
		}//end validate sensor

		// ============= Constructors =============
		public function __construct($request, $db_connection){
			$this->db_connection = $db_connection;
			$this->request = $request;
			$this->validateSensor();
		}//end constructor
	}
?>