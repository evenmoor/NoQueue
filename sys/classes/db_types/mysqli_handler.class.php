<?
	/*
		mysqli handler v1.0.1

		MySQLi connection handler for Architect 2.0
		-- modified for noQueue

		Development by: Joshua Moor
		Last Modified: 11-05-2017

		Change Log:
		11-05-2017 | 1.0.1 | Tweaked for use with the noQueue system
		07-24-2017 | 1.0.0 | Initial class created
	*/

	class mysqli_handler extends database_handler{
		//connect to database
		protected function connect(){
			$this->database_connection = new mysqli($this->database_location, $this->database_username, $this->database_password, $this->database_name) 
			or die ("<p class='error'>I am unable to connect to the database</p>");

			$this->connection_status = "connected";
		}//end connect

		//disconnect from database
		protected function disconnect(){
			$this->database_connection->close();

			$this->connection_status = "disconnected";
		}//end disconnect

		//run a query
		public function query($query_string){
			// make sure the conneciton can be used
			if($this->connection_status != "connected"){
				$this->connect();
			}

			$results = $this->database_connection->query($query_string);
			
			return $this->standardizeQuery($results);
		}//end query

		public function clean($string){
			$return_string = '';
			// make sure the conneciton can be used
			if($this->connection_status != "connected"){
				$this->connect();
			}

			$return_string = mysqli_real_escape_string($this->database_connection, $string);

			return $return_string;
		}

		public function getDatabaseServerInfo(){
			$db_server_info;

			$db_server_info['type'] = 'MySQL';
			$db_server_info['ver'] = mysqli_get_server_info($this->database_connection);
			$db_server_info['licence'] = 'http://www.google.com';

			return $db_server_info;
		}

		//standardize the query output into associative array
		protected function standardizeQuery($results){
			$result_array = array();

			if($results == ''){
				array_push($result_array, array('status' => 'error', 'message' => mysqli_error($this->database_connection)));
			}elseif(!(is_object($results)) && $results == 1){
				array_push($result_array, array('status' => 'success', 'id' => $this->database_connection->insert_id));
			}else{
				while($row = $results->fetch_assoc()){
					array_push($result_array, $row);
				}

				$results->free();
			}

			return $result_array;
		}//end standardizeQuery
	}

?>