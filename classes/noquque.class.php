<?
	//primary controller class for NoQueue
	class noqueue{
		private $version = "1.0";
		private $timestamp_format = 'Y-m-d H:i:s';
		private $db_connection;
		private $request;

		// ============= Public Functions =============

		// ============= Private Functions =============

		// ============= Constructors =============
		public function __construct($request){
			echo "<h1>Starting NoQueue".$this->version."</h1>";
			print_r($request);
			$this->request = $request;
		}//end constructor

		// ============= Deconstructors =============
		public function __destruct(){
			
		}
	}

?>