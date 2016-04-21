<?php

final class APDOMySQL{
	/**
	 * @var PDO
	 */
	private $connection = null;
	/**
	 * @var PDOStatement
	 */
	private $statement;
	/**
	 * @var Registry
	 */
	private $registry;

	public function __construct($hostname, $username, $password, $database, $new_link = false, $port = "3306"){
		try{
			$this->connection = new PDO("mysql:host=" . $hostname . ";port=" . $port . ";dbname=" . $database, $username, $password, array (PDO::ATTR_PERSISTENT => true));
		} catch(AException $e){
			throw new AException(AC_ERR_MYSQL, 'Error: Could not make a database connection to database ' . $database . ' using ' . $username . '@' . $hostname);
		}
		$this->registry = Registry::getInstance();

		$this->connection->exec("SET NAMES 'utf8'");
		$this->connection->exec("SET CHARACTER SET utf8");
		$this->connection->exec("SET CHARACTER_SET_CONNECTION=utf8");
		$this->connection->exec("SET SQL_MODE = ''");
		$this->connection->exec("SET session wait_timeout=60;");
		$this->connection->exec("SET SESSION SQL_BIG_SELECTS=1;");

	}

	public function prepare($sql){
		$this->statement = $this->connection->prepare($sql);
	}

	public function bindParam($parameter, $variable, $data_type = PDO::PARAM_STR, $length = 0){
		if ($length){
			$this->statement->bindParam($parameter, $variable, $data_type, $length);
		} else{
			$this->statement->bindParam($parameter, $variable, $data_type);
		}
	}

	public function query($sql, $noexcept = false, $params = array ()){
		if (!$noexcept){
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}

		$this->statement = $this->connection->prepare($sql);
		$result = false;

		$time_start = microtime(true);
		try{
			if ($this->statement && $this->statement->execute($params)){
				$data = array ();
				if ($this->statement->columnCount()){
					while ($row = $this->statement->fetch(PDO::FETCH_ASSOC)){
						$data[] = $row;
					}

					$result = new stdClass();
					$result->row = (isset($data[0]) ? $data[0] : array ());
					$result->rows = $data;
					$result->num_rows = $this->statement->rowCount();
				}
			}
		} catch(PDOException $e){
			if ($noexcept){
				$this->error = 'AbanteCart Error: ' . $result->error . '<br />' . $sql;
				return false;
			} else{
				$er = new AError(var_export($this->statement->rowCount(), true));
				$er->toLog();

				throw new AException(AC_ERR_MYSQL, 'Error: ' . $e->getMessage() . '<br />Error No: ' . $e->getCode() . '<br />' . $sql);
			}
		}

		$time_exec = microtime(true) - $time_start;

		// to avoid debug class init while setting was not yet loaded
		if ($this->registry->get('config')){
			if ($this->registry->get('config')->has('config_debug')){
				$backtrace = debug_backtrace();
				ADebug::set_query($sql, $time_exec, $backtrace[2]);
			}
		}

		if ($result){
			return $result;
		} else{
			$result = new stdClass();
			$result->row = array ();
			$result->rows = array ();
			$result->num_rows = 0;
			return $result;
		}
	}

	public function escape($value){

		if (is_array($value)){
			$dump = var_export($value,true);
		    $backtrace = debug_backtrace();
		    $dump .= ' (file: '.$backtrace[1]['file'] .' line '.$backtrace[1]['line'].')';
			$message = 'aMySQLi class error: Try to escape non-string value: ' . $dump;
			$error = new AError($message);
			$error->toLog()->toDebug()->toMessages();
			return false;
		}

		$search = array ("\\", "\0", "\n", "\r", "\x1a", "'", '"');
		$replace = array ("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"');
		return str_replace($search, $replace, $value);
	}

	public function countAffected(){
		if ($this->statement){
			return $this->statement->rowCount();
		} else{
			return 0;
		}
	}

	public function getLastId(){
		return $this->connection->lastInsertId();
	}

	public function __destruct(){
		$this->connection = null;
	}
}
