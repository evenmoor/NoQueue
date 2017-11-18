<?
	class site{
		private $db_connection; //handler for connections to the databases
		private $request; //request variables
		
		// ============= Public Functions =============
		public function executeRequest(){
			switch($this->request['request']){
				case 'getAreaData'://grab the building and sensor data for a given area
					$return_array = array('buildings' => array());
					
					$building_query = 'SELECT building_id
											,building_name
											,building_number
											,building_svg
										FROM tbl_buildings
										WHERE building_area_fk = "'.$this->db_connection->clean($this->request['id']).'"';
					$building_query = $this->db_connection->query($building_query);
					
					foreach($building_query as $building){//parse buildings
						$building_array = array(
							'id' => $building['building_id']
							,'name' => $building['building_name']
							,'number' => $building['building_number']
							,'svg' => $building['building_svg']
							,'sensors' => array()
						);

						$sensor_query = 'SELECT s.sensor_id
												,s.sensor_name
												,max(st.sensor_state_log_id)
												,st.sensor_state_log_state_fk
											FROM tbl_sensors s
												INNER JOIN tbl_sensor_state_log st ON s.sensor_id = st.sensor_state_log_sensor_fk
											WHERE sensor_building_fk = "'.$this->db_connection->clean($building['building_id']).'"';
						$sensor_query = $this->db_connection->query($sensor_query);

						foreach($sensor_query as $sensor){//parse sensors
							$sensor_array = array(
								'id' => $sensor['sensor_id']
								,'name' => $sensor['sensor_name']
								,'state' => $sensor['sensor_state_log_state_fk']
							);

							array_push($building_array['sensors'], $sensor_array);
						}//end sensor loop

						array_push($return_array['buildings'], $building_array);
					}//end building loop

					return $return_array;
				break;//end getAreaData

				case 'getSensorData': //get the current data of a single sensor
					$return_array = array('sensors' => array());

					$sensor_query = 'SELECT sensor_state_log_sensor_fk
										,sensor_state_log_state_fk
									FROM tbl_sensor_state_log
									WHERE sensor_state_log_sensor_fk = "'.$this->db_connection->clean($this->request['id']).'"
									ORDER BY sensor_state_log_id DESC 
									LIMIT 1';
									
					$sensor_query = $this->db_connection->query($sensor_query);
					$sensor_query = $sensor_query[0];
					$sensor_array = array(
						'id' => $sensor_query['sensor_state_log_sensor_fk']
						,'state' => $sensor_query['sensor_state_log_state_fk']
					);

					array_push($return_array['sensors'], $sensor_array);

					return $return_array;
				break;//end getSensorState

				case 'getBuildingSensorData': //get the current data of sensors in a building
					$return_array = array('sensors' => array());				

					$sensor_query = 'SELECT distinct(sl.sensor_state_log_sensor_fk), sl.sensor_state_log_state_fk
									FROM tbl_sensors s
									INNER JOIN tbl_sensor_state_log AS sl
									WHERE s.sensor_building_fk = "'.$this->db_connection->clean($this->request['id']).'"
									AND sl.sensor_state_log_id = (SELECT MAX(sensor_state_log_id)
				                    FROM tbl_sensor_state_log as i
				                    WHERE i.sensor_state_log_sensor_fk = sl.sensor_state_log_sensor_fk)';  
				    $sensor_query = $this->db_connection->query($sensor_query);

					foreach($sensor_query as $sensor){//parse the list of sensors
						$sensor_array = array(
							'id' => $sensor['sensor_state_log_sensor_fk']
							,'state' => $sensor['sensor_state_log_state_fk']
						);

						array_push($return_array['sensors'], $sensor_array);
					}//end sensor loop

					return $return_array;
				break;//end getSensorState

				case 'getAreaSensorData': //get the current data of sensors in an area
					$return_array = array('sensors' => array());

					$sensor_query = 'SELECT sl.sensor_state_log_sensor_fk
										,sl.sensor_state_log_state_fk
										,max(sl.sensor_state_log_id)
									FROM tbl_sensors s
										INNER JOIN tbl_sensor_state_log sl ON s.sensor_id = sl.sensor_state_log_sensor_fk
										INNER JOIN tbl_buildings b ON s.sensor_building_fk = b.building_id
									WHERE b.building_area_fk = "'.$this->db_connection->clean($this->request['id']).'"';
					$sensor_query = $this->db_connection->query($sensor_query);

					foreach($sensor_query as $sensor){//parse the list of sensors
						$sensor_array = array(
							'id' => $sensor['sensor_state_log_sensor_fk']
							,'state' => $sensor['sensor_state_log_state_fk']
						);

						array_push($return_array['sensors'], $sensor_array);
					}//end sensor loop

					return $return_array;
				break;//end getSensorState
			}//end request switch
		}//end executeRequest

		// ============= Private Functions =============

		// ============= Constructors =============
		public function __construct($request, $db_connection){
			$this->db_connection = $db_connection;
			$this->request = $request;
		}//end constructor
	}
?>