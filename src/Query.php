<?php
namespace WPQueryBuilder;

class Query{

	const SELECT = 'SELECT';
	const INSERT = 'INSERT';
	const DELETE = 'DELETE';
	const UPDATE = 'UPDATE';

	private $db;

	private $type = null;

	private $selectFields = [];

	private $selectDistinct = false;

	private $updateFields = [];

	private $countFoundRows = false;

	private $table;

	private $joins = [];

	private $wheres;

	private $bindings = [];

	private $order = [];

	private $limit = 0;

	private $offset = 0;

	private $groupBy;

	public function __construct($wpdb){
		$this->db = $wpdb;
		$this->wheres = new CompositeWhereClause();
	}

	public function select($fields = "*"){
		$this->type = self::SELECT;
		$this->selectDistinct = false;
		$this->selectFields = is_array($fields) ? $fields : [$fields];
		return $this;
	}

	public function selectDistinct($fields = "*"){
		$this->select($fields);
		$this->selectDistinct = true;
		return $this;
	}

	public function countFoundRows($bool = true){
		$this->countFoundRows = (bool) $bool;
		return $this;
	}

	public function from($table){
		return $this->table($table);
	}

	public function table($table){
		$this->table = $table;
		return $this;
	}

	public function insert($data = null){
		$this->type = self::INSERT;
		if(!$this->table){
			throw new \BadMethodCallException(
				"No table set. Please call ->table('tablename') before calling ->insert()."
			);
		}
		$this->db->insert($this->table, $data);
		if(!empty($this->db->last_error)){
			throw new QueryException($this->db->last_error);
		}
		return (int) $this->db->insert_id;
	}

	public function set($data){
		$this->updateFields = $data;
		return $this;
	}

	public function update($data = null, $force = false){
		$this->type = self::UPDATE;

		if(!is_null($data)){
			$this->updateFields = $data;
		}

		if(!$this->table){
			throw new \BadMethodCallException(
				"No table set. Please call ->table('tablename') before calling ->update()."
			);
		}

		if(!$force && $this->wheres->isEmpty()){
			throw new \BadMethodCallException(
				"No where condition set. Please use updateAll instead."
			);
		}

		$sql = $this->buildSqlAndPrepare();
		$this->db->query($sql);

		if(!empty($this->db->last_error)){
			throw new QueryException($this->db->last_error);
		}
	}

	public function updateAll($data = null){
		$this->update($data, true);
	}

	public function delete($force = false){
		$this->type = self::DELETE;

		if(!$this->table){
			throw new \BadMethodCallException(
				"No table set. Please call ->table('tablename') before calling ->delete()."
			);
		}

		if(!$force && $this->wheres->isEmpty()){
			throw new \BadMethodCallException(
				"No where condition set. Please use deleteAll instead."
			);
		}

		$sql = $this->buildSqlAndPrepare();

		$this->db->query($sql);

		if(!empty($this->db->last_error)){
			throw new QueryException($this->db->last_error);
		}
	}

	public function deleteAll(){
		$this->delete(true);
	}

	public function leftJoin($table, $firstColumn, $operator = JoinClause::USING, $secondColumn = null){
		$this->join(JoinClause::LEFT, $table, $firstColumn, $operator, $secondColumn);
		return $this;
	}

	public function rightJoin($table, $firstColumn, $operator = JoinClause::USING, $secondColumn = null){
		$this->join(JoinClause::RIGHT, $table, $firstColumn, $operator, $secondColumn);
		return $this;
	}

	public function innerJoin($table, $firstColumn, $operator = JoinClause::USING, $secondColumn = null){
		$this->join(JoinClause::INNER, $table, $firstColumn, $operator, $secondColumn);
		return $this;
	}

	public function fullJoin($table, $firstColumn, $operator = JoinClause::USING, $secondColumn = null){
		$this->join(JoinClause::FULL, $table, $firstColumn, $operator, $secondColumn);
		return $this;
	}

	private function join($type, $table, $firstColumn, $operator, $secondColumn){
		$join = new JoinClause($type, $table);
		$join->on($firstColumn, $operator, $secondColumn);
		$this->joins[] = $join;
	}


	public function andWhere($columnOrWhereInstance, $operator = '=', $value = null) {
		if($columnOrWhereInstance instanceof WhereClause){
			$this->wheres->andWhere($columnOrWhereInstance);
		} else {
			if(func_num_args() == 2){
				$value = $operator;
				$operator = WhereClause::EQUALS;
			}
			$this->wheres->andWhere(new BasicWhereClause($columnOrWhereInstance, $operator, $value));
		}
		return $this;
	}

	/**
	 * @param string $column
	 * @param string $operator
	 * @param null $value
	 * @return $this
	 */
	public function where($column, $operator = '=', $value = null) {
		if(func_num_args() == 2){
			$value = $operator;
			$operator = WhereClause::EQUALS;
		}
		$this->andWhere($column, $operator, $value);
		return $this;
	}

	/**
	 * @param string $column
	 * @param string $operator
	 * @param null $value
	 * @return $this
	 */
	public function orWhere($columnOrWhereInstance, $operator = '=', $value = null) {
		if($columnOrWhereInstance instanceof WhereClause){
			$this->wheres->orWhere($columnOrWhereInstance);
		} else {
			if(func_num_args() == 2){
				$value = $operator;
				$operator = WhereClause::EQUALS;
			}
			$this->wheres->orWhere(new BasicWhereClause($columnOrWhereInstance, $operator, $value));
		}
		return $this;
	}


	/**
	 * @param string $column
	 * @param string $value1
	 * @param string $value2
	 * @return $this
	 */
	public function whereBetween($column, $value1, $value2) {
		$this->wheres->andWhere(new BetweenWhereClause($column,$value1,$value2));
		return $this;
	}

	public function andWhereNull($column) {
		$this->wheres->andWhere(new NullWhereClause($column,WhereClause::ISNULL));
		return $this;
	}

	public function orWhereNull($column) {
		$this->wheres->orWhere(new NullWhereClause($column,WhereClause::ISNULL));
		return $this;
	}

	/**
	 * @param string $column
	 * @param array $values
	 * @return $this
	 */
	public function whereIn($column, $values) {
		$this->wheres->andWhere(new WhereInClause($column,$values));
		return $this;
	}

	public function search($fieldOrFields, $searchTerm){
		$search = new CompositeWhereClause();
		$fields = is_array($fieldOrFields) ? $fieldOrFields : [$fieldOrFields];
		foreach($fields as $field){
			$search->orWhere(new WhereLikeClause($field, $searchTerm));
		}
		$this->wheres->andWhere($search);

		return $this;
	}

	public function orderBy($table, $order){
		$this->order = [];
		$this->thenOrderBy($table, $order);
		return $this;
	}


	public function thenOrderBy($column, $order){
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$this->order[] = "$column $order";
		return $this;
	}

	public function limit($limit){
		$this->limit = max(0, (int) $limit);
		return $this;
	}

	public function offset($offset){
		$this->offset = max(0, (int) $offset);
		return $this;
	}

	public function groupBy($column){
		$this->groupBy = $column;
		return $this;
	}

	public function get(){
		$sql = $this->buildSqlAndPrepare();
		$results  = $this->db->get_results($sql);
		if(!empty($this->db->last_error)){
			throw new QueryException($this->db->last_error);
		}
		return $results;
	}

	public function first(){
		$results = $this->limit(1)->get();

		if(count($results) === 0){
			return null;
		}

		return array_shift($results);
	}

	public function getColumn($column = null){
		$sql = $this->buildSqlAndPrepare();
		$results = $this->db->get_results($sql);

		$values = [];
		if($results){
			foreach($results as $result) {
				$resultValues = get_object_vars($result);
				if(is_null($column)){
					$values[] = array_shift($resultValues);
				} else {
					if(!array_key_exists($column, $resultValues)){
						$columns = implode(', ', array_keys($resultValues));
						throw new \InvalidArgumentException(
							"Column {$column} not found in returned set. Must be one of {$columns}."
						);
					}
					$values[] = $resultValues[$column];
				}
			}
		}

		return $values;
	}


	public function getScalar(){
		$sql = $this->buildSqlAndPrepare();
		$scalar = $this->db->get_var($sql);
		if(!empty($this->db->last_error)){
			throw new QueryException($this->db->last_error);
		}
		return $scalar;
	}

	public function getTotalRowCount(){

		//TODO Check query has been executed
		//TODO support total row count where no limits have been placed

		if(!$this->countFoundRows){
			throw new \BadMethodCallException(
				"getTotalRowCount() can only be called if you have called countFoundRows() before executing the query."
			);
		}

		$foundRows = (int) $this->db->get_var("SELECT FOUND_ROWS();");

		if(!empty($this->db->last_error)){
			throw new QueryException($this->db->last_error);
		}

		return $foundRows;
	}

	private function buildSqlAndPrepare(){
		$sql = $this->buildSql();
		if(count($this->bindings) > 0){
			$sql = $this->db->prepare($sql, $this->bindings);
		}
		return $sql;
	}

	private function buildSql(){
		$sql = '';

		switch($this->type){
			case self::SELECT:
				$foundRows = $this->countFoundRows && $this->limit > 0 ? 'SQL_CALC_FOUND_ROWS' : '';
				$distinct  = $this->selectDistinct ? 'DISTINCT' : '';
				$parts = [
					'SELECT', $foundRows, $distinct, implode(', ', $this->selectFields), 'FROM', $this->table
				];
				$sql = implode(' ', array_filter($parts));
				break;

			case self::UPDATE:
				$sqlParts = ['UPDATE', $this->table, $this->buildSetSQL(), $this->buildWhereSql()];
				return trim(implode(' ', $sqlParts)). ';';
				break;

			case self::DELETE:
				$sqlParts = ['DELETE FROM', $this->table, $this->buildWhereSql()];
				return trim(implode(' ', $sqlParts)). ';';
				break;

			//case self::INSERT is an easy case, handled in ->insert()

			default:
				throw new \Exception('SQL statement not set. You must call ->select(), ->update(), ->insert() or ->delete()');
		}

		$sqlParts = array_filter([
			$sql, $this->buildJoinSql(), $this->buildWhereSql(), $this->buildGroupBySql(),
			$this->buildOrderBy(), $this->buildLimitOffset()
		]);

		return implode(' ', $sqlParts). ';';
	}

	private function buildJoinSql() {
		if(count($this->joins) === 0){
			return '';
		}

		$joinSql = [];
		foreach($this->joins as $join){
			$joinSql[] = $join->buildSql();
		}

		return implode(' ', $joinSql);
	}

	private function buildWhereSql() {
		$sql = $this->wheres->buildSql();
		if($sql === ''){
			return '';
		}
		$this->bindings = array_merge($this->bindings, $this->wheres->getBindings());
		return 'WHERE ' . $sql;
	}

	private function buildSetSql() {
		if(count($this->updateFields) === 0) {
			throw new \BadMethodCallException(
				"update() can only be called if you have called set() or passed in an array of fields to update."
			);
		}

		$parts = [];
		$bindings = [];
		foreach($this->updateFields as $key => $value){
			if(is_null($value)){
				$parts[] = "{$key} = NULL";
			} else {
				$bindings[] = $value;
				$parts[] = "{$key} = %s";
			}
		}
		$this->bindings = array_merge($this->bindings, $bindings);
		return 'SET ' . implode(', ', $parts);
	}

	private function buildGroupBySql() {
		if(!$this->groupBy){
			return '';
		}

		return sprintf("GROUP BY %s", $this->groupBy);
	}


	private function buildOrderBy() {
		if(count($this->order) === 0){
			return '';
		}

		return 'ORDER BY ' . implode(', ', $this->order);
	}

	private function buildLimitOffset(){
		if($this->limit <= 0) {
			return '';
		}

		if($this->offset > 0){
			return sprintf("LIMIT %d, %d", $this->offset, $this->limit);
		}

		return sprintf("LIMIT %d", $this->limit);
	}

}
