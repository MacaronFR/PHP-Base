<?php


namespace Macaron\Base;
use PDO;
use PDOException;

abstract class Model{
	/** @var PDO $bdd PDO object to database */
	protected PDO $bdd;
	protected string $table_name;
	protected string $id_name;
	protected array $column;
	protected int $max_row = 500;
	/**
	 * Models constructor.
	 * @codeCoverageIgnore
	 */
	public function __construct(){
		require "conf.php";
		$dbname = $db["dbname"];
		$host = $db["host"];
		$user = $db["user"];
		$passwd = $db["passwd"];
		$port = $db["port"];
		$this->bdd = new PDO("mysql:dbname=$dbname;host=$host:$port", $user, $passwd);
		$this->bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Query to Database
	 * @param string $statement Statement to query
	 * @param bool $unique
	 * @return array
	 * @throws ModelException
	 */
	protected function query(string $statement, bool $unique = false): array{
		try {
			$res = $this->bdd->query($statement);
			if($unique){
				return $res->fetch(PDO::FETCH_ASSOC);
			}
			return $res->fetchAll(PDO::FETCH_ASSOC);
		}catch(PDOException $e){
			throw new ModelException("Error during query\nSQL Query : $statement", ModelException::$QUERY_ERROR, $e);
		}
	}

	/**
	 * @param string $statement Statement to prepare and query
	 * @param array $param Array of param of form $param["param_name"] = $param_value
	 * @param bool $unique If the query return only one row
	 * @param bool $fetch Query have to be fetch ?
	 * @param bool $last_id Want to retrieve the last inserted ID (not compatible with fetch)
	 * @return array|bool|int
	 * @throws ModelException
	 */
	protected function preparedQuery(string $statement, array $param, bool $unique = false, bool $fetch = true, bool $last_id = false): array|bool|int{
		$req = $this->bdd->prepare($statement);
		foreach($param as $name => $value){
			$req->bindValue($name, $value);
		}
		try {
			$req->execute();
			if($fetch) {
				if ($unique)
					return $req->fetch(PDO::FETCH_ASSOC);
				return $req->fetchAll(PDO::FETCH_ASSOC);
			}else{
				if($last_id)
					return $this->bdd->lastInsertId();
				return $req->rowCount() !== 0;
			}
		}catch (PDOException $e){
			$errorMsg = "Error during prepared query\nSQL Query : $statement\nParam : [";
			foreach($param as $k => $v){
				$errorMsg .= "\n$k => $v";
			}
			$errorMsg .= "\n]";
			throw new ModelException($errorMsg, ModelException::$QUERY_PREPARED_ERROR, $e);
		}
	}

	/**
	 * Prepare from $fields the insert query associated
	 * @param array $fields all Fields to insert
	 * @return string|false Query or false on error
	 */
	protected function prepareInsertQuery(array $fields): string|false{
		$query = "INSERT INTO $this->table_name ";
		$column = "(";
		$param = "(";
		foreach($fields as $name => $value){
			$column_name = $this->column[$name];
			$column .= $column_name . ", ";
			$param .= ":" . $name . ", ";
		}
		$column = substr($column, 0, -2);
		$param = substr($param, 0, -2);
		$query .= $column . ") VALUES " . "$param" . ");";
		return $query;
	}

	/**
	 * Prepare from $fields the update query associated
	 * @param array $fields all fields to insert
	 * @return string|false Query or false on error
	 */
	protected function prepareUpdateQuery(array $fields): string|false{
		$query = "UPDATE " . $this->table_name . " SET ";
		$arg = "";
		foreach($fields as $name => $value){
			$column_name = $this->column[$name];
			$arg .= $column_name . "=:" . $name . ", ";
		}
		$arg = substr($arg, 0, -2);
		$query .= "$arg" . " WHERE " . $this->id_name . "=:id;";
		return $query;
	}

	protected function prepareSelectColumn(): string{
		$column = "";
		foreach($this->column as $key => $item){
			$column .= " $item as $key,";
		}
		$column .= " $this->id_name as id";
		return $column;
	}

	/**
	 * Select All Row from table, if limit is set to true, retrieve $this->max_row starting at $iteration * $this->max_row
	 * @param int $iteration
	 * @param bool $limit
	 * @return array|false
	 * @throws ModelException
	 */
	public function selectAll(int $iteration = 0, bool $limit = true): array|false{
		$start = $this->max_row * $iteration;
		$sql = "SELECT";
		foreach($this->column as $key => $item){
			$sql .= " $item as $key,";
		}
		$sql .= " $this->id_name as id";
		$sql .= " FROM $this->table_name WHERE $this->id_name<>0";
		if($limit)
			$sql .= " LIMIT $start, $this->max_row";
		return $this->query($sql);
	}

	/**
	 * @return int Select the total row in the table
	 * @throws ModelException
	 */
	public function selectTotal(): int{
		$sql = "SELECT COUNT($this->id_name) as count FROM $this->table_name WHERE $this->id_name<>0";
		try{
			$res = $this->query($sql);
			return (int)$res[0]['count'];
		}catch(ModelException $e){
			throw $e;
		}
	}

	/**
	 * @param string $search
	 * @param string $order
	 * @param string $sort
	 * @param int $iteration
	 * @return array
	 * @throws ModelException
	 */
	public function selectAllFilter(string $search, string $order, string $sort, int $iteration = 0): array{
		if($sort === 'id'){
			$sort = $this->id_name;
		}else{
			if(key_exists($sort, $this->column)){
				$sort = $this->column[$sort];
			}else{
				throw new ModelException("Key does not exist", ModelException::$COLUMN_NOT_EXIST_ERROR);
			}
		}
		$start = $this->max_row * $iteration;
		$sql = "SELECT" . $this->prepareSelectColumn();
		$sql .= " FROM $this->table_name WHERE (";
		foreach($this->column as $item){
			$sql .= "$item LIKE :search OR ";
		}
		$sql .= "$this->id_name LIKE :search ) AND $this->id_name<>0 ";
		$sql .= "ORDER BY $sort $order ";
		$sql .= "LIMIT $start, $this->max_row;";
		$search = "%" . $search . "%";
		return $this->preparedQuery($sql, ["search" => $search]);
	}

	/**
	 * @param string $search
	 * @param string $order
	 * @param string $sort
	 * @return int
	 * @throws ModelException
	 */
	public function selectTotalFilter(string $search, string $order, string $sort): int{
		if($sort === 'id'){
			$sort = $this->id_name;
		}else{
			if(key_exists($sort, $this->column)){
				$sort = $this->column[$sort];
			}else{
				throw new ModelException("Key does not exist", ModelException::$COLUMN_NOT_EXIST_ERROR);
			}
		}
		$sql = "SELECT COUNT($this->id_name) as count FROM $this->table_name WHERE (";
		foreach($this->column as $item){
			$sql .= "$item LIKE :search OR ";
		}
		$sql .= "$this->id_name LIKE :search ) AND $this->id_name<>0 ";
		$sql .= "ORDER BY $sort $order ";
		$search = "%" . $search . "%";
		$total = $this->preparedQuery($sql, ["search" => $search], unique: true);
		if($total === false){
			return $total;
		}
		return $total['count'];
	}

	/**
	 * @param array $value
	 * @param string $where
	 * @param string $group
	 * @param string $order
	 * @param int|null $start
	 * @param int|null $limit
	 * @param bool $unique
	 * @return array
	 * @throws ModelException
	 */
	public function select(array $value, string $where, string $group = "", string $order = "", int $start = null, int $limit = null, bool $unique = true): array{
		$sql = "SELECT" . $this->prepareSelectColumn();
		$sql .= " FROM $this->table_name WHERE $where";
		if($group !== ""){
			$sql .= " GROUP BY $group";
		}
		if($order !== ""){
			$sql .= " ORDER BY $order";
		}
		if($limit !== null && $start !== null){
			$sql .= "LIMIT $start, $limit";
		}
		return $this->preparedQuery($sql, $value, unique: $unique);
	}

	/**
	 * @param int $id
	 * @param array $value
	 * @return bool
	 * @throws ModelException
	 */
	public function update(int $id, array $value): bool{
		$sql = $this->prepareUpdateQuery($value);
		$value["id"] = $id;
		return $this->preparedQuery($sql, $value, fetch: false);
	}

	/**
	 * @param array $value
	 * @return int|false
	 * @throws ModelException
	 */
	public function insert(array $value): int|false{
		$sql = $this->prepareInsertQuery($value);
		return $this->preparedQuery($sql, $value, fetch: false, last_id: true);
	}

	/**
	 * @param int $id
	 * @return bool
	 * @throws ModelException
	 */
	public function delete(int $id): bool{
		$sql = "DELETE FROM $this->table_name WHERE $this->id_name=:id";
		return  $this->preparedQuery($sql, ["id" => $id], fetch: false);
	}
}