<?php
	/*
		Database Handler Version 2.0.1

		Handles data connections for the architect system
		-- modified for noQueue

		Development by: Joshua Moor
		Last Modified: 11/5/2017

		Change Log:
		11-05-17 | 2.0.1 | Tweaked for use with the noQueue system
		07-24-17 | 2.0.0 | Overhaul to move implementation into individual classes
		08-14-16 | 1.0.0 | Initial class created
	*/

	class database_handler{
		//variables
		protected $database_connection; //the actual database connection
		//end variables
		protected $database_location; //server location
		protected $database_name; //name of the database to load
		protected $database_username; //connection username
		protected $database_password; //connection password
		protected $database_type; //type of database connection e.g. MySql, T-sql, etc. requires a class for that connection type to be defined

		protected $connection_type; //connection type read-only, full access, etc.
		protected $connection_status = "disconnected"; //status of the connection

		//connect to database
		protected function connect(){
			//to be implemented in specific objects
		}//end connect

		//disconnect from database
		protected function disconnect(){
			//to be implemented in specific objects
		}//end disconnect

		//run a query
		public function query($query_string){
			//to be implemented in specific objects
		}//end query

		public function clean($string){
			//to be implemented in specific objects
		}

		public function getDatabaseServerInfo(){
			//to be implemented in specific objects
		}

		//standardize the query output into associative array
		protected function standardizeQuery($results){
			//to be implemented in specific objects
		}//end standardizeQuery

		//constructor
		public function __construct($config){
			$this->database_location = $config['location'];
			$this->database_name = $config['name'];
			$this->database_username = $config['username'];
			$this->database_password = $config['password'];
			$this->database_type = $config['type'];
			$this->connection_type = "full";

			//$this->connect();
		}//end constructor

		//destructor
		public function __destruct(){
			if($this->connection_status == "connected"){
				$this->disconnect();
			}
		}//end destructor
	}
?>