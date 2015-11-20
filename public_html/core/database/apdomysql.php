<?php

final class APDOMySQL {
	private $connection = null;
	private $statement = null;
	/**
	 * @var Registry
	 */
	private $registry;

	public function __construct($hostname, $username, $password, $database, $new_link=false, $port = "3306") {
		try {
			$this->connection = new \PDO("mysql:host=" . $hostname . ";port=" . $port . ";dbname=" . $database, $username, $password, array(\PDO::ATTR_PERSISTENT => true));
		} catch(AException $e) {
			//throw new AException(AC_ERR_MYSQL, 'Error: Could not make a database connection to database ' . $database.' using ' . $username . '@' . $hostname);
		}
		$this->registry = Registry::getInstance();

		$this->connection->exec("SET NAMES 'utf8'");
		$this->connection->exec("SET CHARACTER SET utf8");
		$this->connection->exec("SET CHARACTER_SET_CONNECTION=utf8");
		$this->connection->exec("SET SQL_MODE = ''");



	}

	public function prepare($sql) {
		$this->statement = $this->connection->prepare($sql);
	}

	public function bindParam($parameter, $variable, $data_type = \PDO::PARAM_STR, $length = 0) {
		if ($length) {
			$this->statement->bindParam($parameter, $variable, $data_type, $length);
		} else {
			$this->statement->bindParam($parameter, $variable, $data_type);
		}
	}

	public function execute() {
		try {
			if ($this->statement && $this->statement->execute()) {
				$data = array();

				while ($row = $this->statement->fetch(\PDO::FETCH_ASSOC)) {
					$data[] = $row;
				}

				$result = new \stdClass();
				$result->row = (isset($data[0])) ? $data[0] : array();
				$result->rows = $data;
				$result->num_rows = $this->statement->rowCount();
			}
		} catch(\PDOException $e) {
			trigger_error('Error: ' . $e->getMessage() . ' Error Code : ' . $e->getCode());
		}
	}

	public function query($sql, $noexcept = false, $params = array()) {
		$this->statement = $this->connection->prepare($sql);
		$result = false;

        $time_start = microtime(true);



		try {
			if ($this->statement && $this->statement->execute($params)) {
				$data = array();

				while ($row = $this->statement->fetch(\PDO::FETCH_ASSOC)) {
					$data[] = $row;
				}

				$result = new \stdClass();
				$result->row = (isset($data[0]) ? $data[0] : array());
				$result->rows = $data;
				$result->num_rows = $this->statement->rowCount();
			}
		} catch (AException $e) {
			if($noexcept){
				$this->error = 'AbanteCart Error: ' . $result->error . '<br />' . $sql;
				return FALSE;
			}else{
				trigger_error('Error: ' . $e->getMessage() . ' Error Code : ' . $e->getCode() . ' <br />' . $sql);
				exit();
			}
		}

		$time_exec = microtime(true) - $time_start;

        // to avoid debug class init while setting was not yet loaded
		if($this->registry->get('config')){
			if ( $this->registry->get('config')->has('config_debug') ) {
				$backtrace = debug_backtrace();
				ADebug::set_query($sql, $time_exec, $backtrace[2] );
			}
		}

		if ($result) {
			return $result;
		} else {
			$result = new \stdClass();
			$result->row = array();
			$result->rows = array();
			$result->num_rows = 0;
			return $result;
		}
	}

	public function escape($value) {

		if(is_array($value)){
		    $dump = var_export($value,true);
		    $message = 'aMySQLi class error: Try to escape non-string value: '.$dump;
		    $error = new AError($message);
		    $error->toLog()->toDebug()->toMessages();
		    return false;
	    }

		$search = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');
		$replace = array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"');
		return str_replace($search, $replace, $value);
	}

	public function countAffected() {
		if ($this->statement) {
			return $this->statement->rowCount();
		} else {
			return 0;
		}
	}

	public function getLastId() {
		return $this->connection->lastInsertId();
	}

	public function __destruct() {
		$this->connection = null;
	}
}