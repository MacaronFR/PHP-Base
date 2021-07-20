<?php


namespace Hyperion\API;
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
	 * @return false|array
	 */
	protected function query(string $statement, bool $unique = false): false|array{
		try {
			$res = $this->bdd->query($statement);
			if($unique){
				return $res->fetch(PDO::FETCH_ASSOC);
			}
			return $res->fetchAll(PDO::FETCH_ASSOC);
		}catch(PDOException){
			return false;
		}
	}

	/**
	 * @param string $statement Statement to prepare and query
	 * @param array $param Array of param of form $param["param_name"] = $param_value
	 * @param bool $unique If the query return only one row
	 * @param bool $fetch Query have to be fetch ?
	 * @param bool $last_id Want to retrieve the last inserted ID (not compatible with fetch)
	 * @return array|bool|int
	 */
	protected function prepared_query(string $statement, array $param, bool $unique = false, bool $fetch = true, bool $last_id = false): array|bool|int{
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
			echo "Error : ".$e->getMessage();
			return false;
		}
	}

	/**
	 * Prepare from $fields the insert query associated
	 * @param array $fields all Fields to insert
	 * @return string|false Query or false on error
	 */
	protected function prepare_insert_query(array $fields): string|false{
		$query = "INSERT INTO " . $this->table_name . " ";
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
	protected function prepare_update_query(array $fields): string|false{
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

	/**
	 * Select All Row from table, if limit is set to true, retrieve $this->max_row starting at $iteration * $this->max_row
	 * @param int $iteration
	 * @param bool $limit
	 * @return array|false
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
	 * @return int|false Select the total row in the table
	 */
	public function selectTotal(): int|false{
		$sql = "SELECT COUNT($this->id_name) as count FROM $this->table_name WHERE $this->id_name<>0";
		$res = $this->query($sql);
		if($res !== false){
			return (int)$res[0]['count'];
		}
		return false;
	}

	public function selectAllFilter(string $search, string $order, string $sort, int $iteration = 0): array|false{
		if($sort === 'id'){
			$sort = $this->id_name;
		}else{
			if(key_exists($sort, $this->column)){
				$sort = $this->column[$sort];
			}else{
				return false;
			}
		}
		$start = $this->max_row * $iteration;
		$sql = "SELECT";
		foreach($this->column as $key => $item){
			$sql .= " $item as $key,";
		}
		$sql .= " $this->id_name as id FROM $this->table_name ";
		$sql .= "WHERE (";
		foreach($this->column as $item){
			$sql .= "$item LIKE :search OR ";
		}
		$sql .= "$this->id_name LIKE :search ) AND $this->id_name<>0 ";
		$sql .= "ORDER BY $sort $order ";
		$sql .= "LIMIT $start, $this->max_row;";
		$search = "%" . $search . "%";
		return $this->prepared_query($sql, ["search" => $search]);
	}

	public function selectTotalFilter(string $search, string $order, string $sort): int|false{
		if($sort === 'id'){
			$sort = $this->id_name;
		}else{
			if(key_exists($sort, $this->column)){
				$sort = $this->column[$sort];
			}else{
				return false;
			}
		}
		$sql = "SELECT COUNT($this->id_name) as count FROM $this->table_name WHERE (";
		foreach($this->column as $item){
			$sql .= "$item LIKE :search OR ";
		}
		$sql .= "$this->id_name LIKE :search ) AND $this->id_name<>0 ";
		$sql .= "ORDER BY $sort $order ";
		$search = "%" . $search . "%";
		$total = $this->prepared_query($sql, ["search" => $search], unique: true);
		if($total === false){
			return $total;
		}
		return $total['count'];
	}

	public function select(mixed $value, string $column = ""): array|false{
		if(!in_array($column, array_keys($this->column))){
			$column = $this->id_name;
		}else{
			$column = $this->column[$column];
		}
		$sql = "SELECT";
		foreach($this->column as $name => $item){
			$sql .= " $item as $name,";
		}
		$sql .= " $this->id_name as id";
		$sql .= " FROM $this->table_name WHERE $column=:id ";
		return $this->prepared_query($sql, ["id" => $value], unique: true);
	}
	public function update(int $id, array $value): bool{
		$sql = $this->prepare_update_query($value);
		$value["id"] = $id;
		return $this->prepared_query($sql, $value, fetch: false);
	}
	public function insert(array $value): int|false{
		$sql = $this->prepare_insert_query($value);
		return $this->prepared_query($sql, $value, fetch: false, last_id: true);
	}
	public function delete(int $id): bool{
		$sql = "DELETE FROM $this->table_name WHERE $this->id_name=:id";
		return  $this->prepared_query($sql, ["id" => $id], fetch: false);
	}
}