<?
	class site{
		private $db_connection; //handler for connections to the databases
		private $request; //request variables
		
		// ============= Public Functions =============
		public function executeRequest(){
			
		}//end executeRequest

		// ============= Private Functions =============

		// ============= Constructors =============
		public function __construct($request, $db_connection){
			$this->db_connection = $db_connection;
			$this->request = $request;
		}//end constructor
	}
?>