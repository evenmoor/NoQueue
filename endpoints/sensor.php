<?php
	/*

		Sensor Class

		Dev: Joshua Moor
		Last Modified: 10/21/2017

		Change Log: 10/20/2017 - Initial Class Created
		 -- 10/20/2017 - Predictive Scheduling added

	*/

	class sensor{
		// msyqlli connection to database
		private $db_connection;
		//mysql timestamp formats
		private $timestamp_format = 'Y-m-d H:i:s';

		// ============= Public Functons =============
		// log
		// logs the current state of the sensor based on id
		// requires id of the sensor to log and the current state to log
		// returns nothing
		public function log($id, $state){
			$sensor_state = 1;
			switch($state){
				case 1:
					$sensor_state = 2;
				break;
			}

			$query = 'INSERT INTO tbl_door_log(
								door_fk
								,door_state_fk) 
						VALUES("'.$this->cleanInput($id).'"
								, "'.$this->cleanInput($sensor_state).'")';

			$door_status_query = 'SELECT door_status_fk
									FROM tbl_doors
									WHERE door_id="'.$this->cleanInput($id).'"';

			$door_status = mysqli_query($this->db_connection, $door_status_query);
			$door_status = mysqli_fetch_assoc($door_status);
			

			$door_status_log_query = 'INSERT INTO tbl_door_flag_log(flag_log_door_fk
			,flag_log_statuses_fk)
				VALUES("'.$this->cleanInput($id).'"
						,"'.$this->cleanInput($door_status['door_status_fk']).'")';

			$insert = mysqli_query($this->db_connection, $query);
			$door_status_log = mysqli_query($this->db_connection, $door_status_log_query);

			return $insert;
		}//end log function

		// getMachineLearningStructBuilding
		// gets the machine learning informaiton for the entire buildijng
		// requires id of building to fetch data for
		// returns an associative array containing the building informaiton
		public function getMachineLearningStructBuilding($building_id){
			$query = 'SELECT door_id
						FROM tbl_doors
						WHERE building_fk = "'.$this->cleanInput($building_id).'"';

			$sensor_list = mysqli_query($this->db_connection, $query);

			$return = array();

			while($sensor = mysqli_fetch_assoc($sensor_list)){
				$sensor_id = $sensor['door_id'];
				$sensor_array = array(
					'id' => $sensor_id
					,'log' => $this->getMachineLearningStructSensor($sensor_id)
				);
				array_push($return, $sensor_array);
			}

			return $return;
		}//end getMachineLearningStructBuilding

		// get machine learning struct sensor
		// retrieves the sensor data for the machine learning algorithm
		// requires an id for sensor to retrieve
		// returns a struct 
		public function getMachineLearningStructSensor($sensor_id){
			$return = array();

			// $timestamp_format = 'Y-m-d H:i:s';

			$query = 'SELECT l.door_state_change_timestamp 
							,l.door_state_fk
							,d.building_fk
						FROM tbl_door_log l
							INNER JOIN tbl_doors d on l.door_fk = d.door_id
						WHERE door_fk = "'.$this->cleanInput($sensor_id).'"
						ORDER BY door_state_change_timestamp ASC';

			$data = mysqli_query($this->db_connection, $query);

			$last_closed;
			$phase_started = false;
			while($state_change = mysqli_fetch_assoc($data)){
				if($state_change['door_state_fk'] == 2){
					$phase_started = true;
					$last_closed = date_create_from_format($this->timestamp_format, $state_change['door_state_change_timestamp']);
				}

				if($phase_started == true && $state_change['door_state_fk'] == 1){
					$phase_started = false;

					$opened = date_create_from_format($this->timestamp_format, $state_change['door_state_change_timestamp']);

					$closed_time = $last_closed->diff($opened);

					$schedule_query = 'SELECT building_schedule_open
						,building_schedule_closed
					FROM tbl_building_schedule 
					WHERE building_schedule_building_fk = "'.$this->cleanInput($state_change['building_fk']).'"
						AND building_schedule_day = "'.$this->cleanInput($last_closed->format("D")).'"
					LIMIT 1';

					$schedule_data = mysqli_query($this->db_connection, $schedule_query);
					$schedule_data = mysqli_fetch_assoc($schedule_data);

					$building_state = 0;
					if($schedule_data['building_schedule_closed'] != NULL && $schedule_data['building_schedule_open'] != NULL){
						if($last_closed->format('H') <= $schedule_data['building_schedule_closed'] && $last_closed->format('H') >= $schedule_data['building_schedule_open']){
							$building_state = 1;
						}
					}

					$building_state = 1;
					if($last_closed->format('H') < 8 || $last_closed->format('H') > 17){
						$building_state = 0;
					}

					$log_entry = array(
						'closed' => array(
							'day' => $last_closed->format('d')
							,'month' => $last_closed->format('m')
							,'year' => $last_closed->format('Y')
							,'hour' => $last_closed->format('H')
							,'minute' => $last_closed->format('m')
							,'second' => $last_closed->format('s')
							,'building_state' => $building_state 
						)
						,'length' => array(
							'hour' => $closed_time->format('%H')
							,'minute' => $closed_time->format('%i')
							,'second' => $closed_time->format('%s')
						)
					);

					array_push($return, $log_entry);
				}
			}

			return $return;
		}// end getMachineLearningStructSensor

		// get Current Building State
		// get current state of all sensors in a list of buildings
		// requires a comma delimited list of building ids
		// returns a associative array
		public function getCurrentBuildingState($building_ids){
			$return = array();
			$buildings = explode(',', $building_ids);

			foreach($buildings as $building){
				$building_query = 'SELECT building_id
										,building_label
										,building_number
									FROM tbl_buildings
									WHERE building_id = "'.$this->cleanInput($building).'"
								LIMIT 1';

				$schedule_query = 'SELECT building_schedule_open
											,building_schedule_closed
										FROM tbl_building_schedule 
										WHERE building_schedule_building_fk = "'.$this->cleanInput($building).'"
											AND building_schedule_day = "'.date("D").'"
										LIMIT 1';

				$door_query = 'SELECT d.door_id
									,d.door_label
									,s.door_status_id
									,s.door_status
									,a.door_accessibility_id
									,a.door_accessibility_status
									,g.door_gender_id
									,g.door_gender
								FROM tbl_doors d
									INNER JOIN tbl_door_statuses s on d.door_status_fk = s.door_status_id
									INNER JOIN tbl_door_accessibility a on d.door_accessibility_fk = a.door_accessibility_id
									INNER JOIN tbl_door_gender g on d.door_gender_fk = g.door_gender_id
								WHERE building_fk = "'.$this->cleanInput($building).'"';

				$door_data = mysqli_query($this->db_connection, $door_query);
				$door_array = array();

				while($door = mysqli_fetch_assoc($door_data)){
					$door_state = $this->getCurrentSensorState($door['door_id']);
					array_push($door_array, 
						array(
							'id'=> $door['door_id']
							,'label' => $door['door_label']
							,'properties' => Array(
								'gender_id' => $door['door_gender_id']
								,'gender' => $door['door_gender']
								,'accessibility_id' => $door['door_accessibility_id']
								,'accessibility' => $door['door_accessibility_status']
							)
							,'status' => Array(
								'occupied' => $door_state['occupied']
								,'status' => $door['door_status']
								,'status_id' => $door['door_status_id']
								,'time' => $door_state['time']
							)
						)
					);
				}

				$building_data = mysqli_query($this->db_connection, $building_query);
				$building_data = mysqli_fetch_assoc($building_data);

				$schedule_data = mysqli_query($this->db_connection, $schedule_query);
				$schedule_data = mysqli_fetch_assoc($schedule_data);

				$building_open = 0;
				$current_hour = date('H');
				if($schedule_data['building_schedule_closed'] != NULL && $schedule_data['building_schedule_open'] != NULL){
					if($current_hour <= $schedule_data['building_schedule_closed'] && $current_hour >= $schedule_data['building_schedule_open']){
						$building_open = 1;
					}
				}

				$building_array = array(
					'status' => $building_open
					,'id' => $building_data['building_id']
					,'label' => $building_data['building_label']
					,'number' => $building_data['building_number']
					,'times' => array(
						'open' => $schedule_data['building_schedule_open']
						,'close' => $schedule_data['building_schedule_closed']
					)
					,'doors' => $door_array
				);

				array_push($return, $building_array);
			}

			return $return;
		}//end  getCurrentBuildingState

		// get current sensor state
		// retrieves the current state of the sensor
		// requires an id for the sensor to retrieve
		// returns a struct
		public function getCurrentSensorState($sensor_id){
			$sensor_query = 'SELECT door_state_fk
									,door_state_change_timestamp
								FROM tbl_door_log
								WHERE door_fk = "'.$this->cleanInput($sensor_id).'"
								ORDER BY door_log_id DESC
								LIMIT 1';

			$sensor_data = mysqli_query($this->db_connection, $sensor_query);
			$sensor_data = mysqli_fetch_assoc($sensor_data);

			$occupied = false;
			if($sensor_data['door_state_fk'] == 2){
				$occupied = true;
			}

			$return = array(
				'occupied' => $occupied
				,'time' => $sensor_data['door_state_change_timestamp']
			);

			return $return;
		}// end getCurrentState

		//set door status
		//sets the current status of the door
		//requires a status id to set and a door id 
		//returns nothing
		public function setDoorStatus($id, $status){
			$status_query = 'UPDATE tbl_doors
										SET door_status_fk = "'.$this->cleanInput($status).'"
										WHERE door_id="'.$this->cleanInput($id).'"
										LIMIT 1';

			$update_door_status = mysqli_query($this->db_connection, $status_query);
		}

		//get door details
		//get some fun facts about a given door
		//requires a door id
		//returns an associative array
		public function getDoorDetails($id){
			$door_log_query = 'SELECT door_state_fk
								,door_state_change_timestamp
							FROM tbl_door_log
							WHERE door_fk = "'.$this->cleanInput($id).'"
								AND door_state_change_timestamp <= "'.date('Y-m-d').' 23:59:59"
								AND door_state_change_timestamp >= "'.date('Y-m-d').' 00:00:00"
							ORDER BY door_log_id ASC';
			$door_detail_query = 'SELECT door_label
										,door_description
									FROM tbl_doors
									WHERE door_id = "'.$this->cleanInput($id).'"
									LIMIT 1';

			$door_logs = mysqli_query($this->db_connection, $door_log_query);

			// $timestamp_format = 'Y-m-d H:i:s';
			$last_closed;
			$phase_started = false;
			$times_used = 0;
			$last_used = "";
			$total_time = 0;

			while($log = mysqli_fetch_assoc($door_logs)){
				// print_r($log);
				if($log['door_state_fk'] == 2){
					$phase_started = true;
					$last_closed = date_create_from_format($this->timestamp_format, $log['door_state_change_timestamp']);
				}

				if($phase_started == true && $log['door_state_fk'] == 1){
					$phase_started = false;
					$times_used++;
					$opened = date_create_from_format($this->timestamp_format, $log['door_state_change_timestamp']);
					$last_used = $opened;
					$total_time += $last_closed->diff($opened);
				}
			}

			if($times_used != 0){
				$average_time = $total_time / $times_used;
			}else{
				$average_time = 0;
			}

			if($last_used != ''){
				$last_used = $last_used->diff(new DateTime("now"));
				$last_used = $last_used->format('%H')*60 + $last_used->format('%i');
			}else{
				$last_used = 0;
			}

			$door_details = mysqli_query($this->db_connection, $door_detail_query);
			$door_details = mysqli_fetch_assoc($door_details);

			$return = array(
				'average_time_taken' => $average_time
				,'times_used' => $times_used
				,'last_used' => $last_used
				,'label' => $door_details['door_label']
				,'description' => $door_details['door_description']
			);
			return $return;
		}

		public function getMLPredictiveSchedulingBuildingTest($building, $status, $test){
			if($test != "true"){
				return $this->getMLPredictiveSchedulingBuilding($building, $status);
			}else{
				$return = array(
					'id' => $building
					,'data' => array(
						array(
							'info' => array(
								'id'=>1
								,'gender' => 1
								,'maxuses' => rand(100, 110)
								,'avgperday' => rand(30, 50)
								,'numsincelast' => rand(6, 10)
								,'timesincelast' => array(
									'hours' => rand(0, 1)
									,'minutes' => rand(0, 60)
								)
							)
							,'flags' => array(
									array(
										'count' => 18
										,'timesince' => array(
											'hours' => 0
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 30
										,'timesince' => array(
											'hours' => 2
											,'minutes' => 02
										) 
									)
									,array(
										'count' => 26
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 31
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 17
										) 
									)
									,array(
										'count' => 40
										,'timesince' => array(
											'hours' => 3
											,'minutes' => 00
										) 
									)
									,array(
										'count' => 32
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 15
										) 
									)
									,array(
										'count' => 25
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 02
										) 
									)
									,array(
										'count' => 28
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 27
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 00
										) 
									)
									,array(
										'count' => 46
										,'timesince' => array(
											'hours' => 4
											,'minutes' => 00
										) 
									)
							)
						)
						,array(
							'info' => array(
								'id'=>2
								,'gender' => 1
								,'maxuses' => rand(100, 110)
								,'avgperday' => rand(30, 50)
								,'numsincelast' => rand(6, 10)
								,'timesincelast' => array(
									'hours' => rand(0, 1)
									,'minutes' => rand(0, 60)
								)
							)
							,'flags' => array(
									array(
										'count' => 18
										,'timesince' => array(
											'hours' => 0
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 30
										,'timesince' => array(
											'hours' => 2
											,'minutes' => 02
										) 
									)
									,array(
										'count' => 26
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 31
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 17
										) 
									)
									,array(
										'count' => 40
										,'timesince' => array(
											'hours' => 3
											,'minutes' => 00
										) 
									)
									,array(
										'count' => 32
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 15
										) 
									)
									,array(
										'count' => 25
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 02
										) 
									)
									,array(
										'count' => 28
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 27
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 00
										) 
									)
									,array(
										'count' => 46
										,'timesince' => array(
											'hours' => 4
											,'minutes' => 00
										) 
									)
							)
						)
						,array(
							'info' => array(
								'id'=>3
								,'gender' => 1
								,'maxuses' => rand(100, 110)
								,'avgperday' => rand(30, 50)
								,'numsincelast' => rand(6, 10)
								,'timesincelast' => array(
									'hours' => rand(0, 1)
									,'minutes' => rand(0, 60)
								)
							)
							,'flags' => array(
									array(
										'count' => 18
										,'timesince' => array(
											'hours' => 0
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 30
										,'timesince' => array(
											'hours' => 2
											,'minutes' => 02
										) 
									)
									,array(
										'count' => 26
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 31
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 17
										) 
									)
									,array(
										'count' => 40
										,'timesince' => array(
											'hours' => 3
											,'minutes' => 00
										) 
									)
									,array(
										'count' => 32
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 15
										) 
									)
									,array(
										'count' => 25
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 02
										) 
									)
									,array(
										'count' => 28
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 27
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 00
										) 
									)
									,array(
										'count' => 46
										,'timesince' => array(
											'hours' => 4
											,'minutes' => 00
										) 
									)
							)
						)
						,array(
							'info' => array(
								'id'=>4
								,'gender' => 1
								,'maxuses' => rand(100, 110)
								,'avgperday' => rand(30, 50)
								,'numsincelast' => rand(6, 10)
								,'timesincelast' => array(
									'hours' => rand(0, 1)
									,'minutes' => rand(0, 60)
								)
							)
							,'flags' => array(
									array(
										'count' => 18
										,'timesince' => array(
											'hours' => 0
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 30
										,'timesince' => array(
											'hours' => 2
											,'minutes' => 02
										) 
									)
									,array(
										'count' => 26
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 31
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 17
										) 
									)
									,array(
										'count' => 40
										,'timesince' => array(
											'hours' => 3
											,'minutes' => 00
										) 
									)
									,array(
										'count' => 32
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 15
										) 
									)
									,array(
										'count' => 25
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 02
										) 
									)
									,array(
										'count' => 28
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 27
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 00
										) 
									)
									,array(
										'count' => 46
										,'timesince' => array(
											'hours' => 4
											,'minutes' => 00
										) 
									)
							)
						)
						,array(
							'info' => array(
								'id'=>5
								,'gender' => 1
								,'maxuses' => rand(100, 110)
								,'avgperday' => rand(30, 50)
								,'numsincelast' => rand(6, 10)
								,'timesincelast' => array(
									'hours' => rand(0, 1)
									,'minutes' => rand(0, 60)
								)
							)
							,'flags' => array(
									array(
										'count' => 18
										,'timesince' => array(
											'hours' => 0
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 30
										,'timesince' => array(
											'hours' => 2
											,'minutes' => 02
										) 
									)
									,array(
										'count' => 26
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 31
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 17
										) 
									)
									,array(
										'count' => 40
										,'timesince' => array(
											'hours' => 3
											,'minutes' => 00
										) 
									)
									,array(
										'count' => 32
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 15
										) 
									)
									,array(
										'count' => 25
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 02
										) 
									)
									,array(
										'count' => 28
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 45
										) 
									)
									,array(
										'count' => 27
										,'timesince' => array(
											'hours' => 1
											,'minutes' => 00
										) 
									)
									,array(
										'count' => 46
										,'timesince' => array(
											'hours' => 4
											,'minutes' => 00
										) 
									)
							)
						)
					)
				);

				return $return;
			}
		}

		public function getMLPredictiveSchedulingBuilding($building, $status){
			// $timestamp_format = 'Y-m-d H:i:s';
			$doors_query = 'SELECT door_id
								,door_historical_maximum_use
								,door_gender_fk
								FROM tbl_doors
								WHERE building_fk = "'.$this->cleanInput($building).'"';

			$door_array = array();

			$doors = mysqli_query($this->db_connection, $doors_query);

			while($door = mysqli_fetch_assoc($doors)){
				$flag_array = array();

				$max = $door['door_historical_maximum_use'];

				$flags_query = 'SELECT flag_log_timestamp
										,flag_log_statuses_fk
									FROM tbl_door_flag_log
									WHERE flag_log_door_fk = "'.$this->cleanInput($door['door_id']).'"
									ORDER BY flag_log_id ASC ';

				$flags = mysqli_query($this->db_connection, $flags_query);

				$last_status = 0;
				$last_timestamp = "";
				$flagged_counter = 0;

				while($flag = mysqli_fetch_assoc($flags)){
					$flagged_counter++;
					$flag_status = $flag['flag_log_statuses_fk'];
					//kill off non-target stauses
					if($flag_status != $status && $flag_status != 1){
						$flag_status = $status;
					}
					
					$flag_timestamp = date_create_from_format($this->timestamp_format, $flag['flag_log_timestamp']);
					if($last_timestamp == ""){
						$last_timestamp = $flag_timestamp;
					}

					if($last_status != $flag['flag_log_statuses_fk']){
						if($last_status != 0){
							$time_in_status = $last_timestamp->diff(new DateTime("now"));

							array_push($flag_array, array(
								'count' => $flagged_counter
								,'timesince' => array(
									'hours' => $time_in_status->format('%H')
									,'minutes' => $time_in_status->format('%i')
								)
							));
						}

						$flagged_counter = 0;
						$last_status = $flag['flag_log_statuses_fk'];
					}

				}

				if($last_timestamp != ''){
					$time_since_last = $last_timestamp->diff(new DateTime("now"));
				}

				$door_log_query = 'SELECT door_state_fk
								,door_state_change_timestamp
							FROM tbl_door_log
							WHERE door_fk = "'.$this->cleanInput($door['door_id']).'"
							ORDER BY door_log_id ASC';

				$door_logs = mysqli_query($this->db_connection, $door_log_query);

				$times_used = 0;
				$total_used = 0;
				$days = 0;
				$last_day = "";
				$phase_started = false;

				while($log = mysqli_fetch_assoc($door_logs)){
					// print_r($log);

					$log_timestamp = date_create_from_format($this->timestamp_format, $log['door_state_change_timestamp']);
					if($last_day != $log_timestamp->format('%m-%d-%y')){
						$days++;
						$total_used += $times_used;
						if($max < $times_used){
							$max = $times_used;
						}
						$last_day = $log_timestamp->format('%m-%d-%y');
					}

					if($log['door_state_fk'] == 2){
						$phase_started = true;
					}

					if($phase_started == true && $log['door_state_fk'] == 1){
						$phase_started = false;
						$times_used++;
					}
				}

				$average_use = 0;
				if($days > 0){
					$average_use = $total_used / $days;
				}
				
				$hours_since_last = 0;
				$minutes_since_last = 0;
				
				if($time_since_last != ''){
					$hours_since_last = intval($time_since_last->format('%H')) + intval($time_since_last->format('%D')) * 24;
					$minutes_since_last = $time_since_last->format('%i');
				}

				$door_data = array(
					'id' => $door['door_id']
					,'maxuses' => $max
					,'avgperday' => $average_use
					,'numsincelast' => $flagged_counter
					,'gender' => $door['door_gender_fk']
					,'timesincelast' => array(
						'hours' => $hours_since_last
						,'minutes' => $minutes_since_last
					)
					,'flags' => $flag_array
				);

				array_push($door_array, $door_data);
			}

			$return = array(
				'id' => $building
				,'info' => $door_array
			);

			return $return;
		}

		// ============= Private Functions =============
		// private function to open a database connection
		private function connect_db(){
			$db_config = array (
				'location' => 'svhack2017.db.6743371.32c.hostedresource.net'
				,'name' => 'svhack2017'
				,'username' => 'svhack2017'
				,'password' => 'rsbr220Hack!'
			);

			$this->db_connection = mysqli_connect($db_config['location'], $db_config['username'], $db_config['password'], $db_config['name']) or die('<p class="error">I am unable to connect to the database.</p>');
		}//end connect db

		// private function to close a database connection
		private function disconnect_db(){
			$this->db_connection->close();
		}//end disconnect db

		//sanitize string for db input
		private function cleanInput($string){
			return $this->db_connection->real_escape_string($string);
		}//end cleanInput

		// ============= Constructors =============
		public function __construct(){
			$this->connect_db();
		}//end constructor

		// ============= Deconstructors =============
		public function __destruct(){
			$this->disconnect_db();
		}
	}//end sensor
?>