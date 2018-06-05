<?php
namespace WPQueryBuilder;

class WhereLikeClause implements WhereClause {

	private $column;
	private $value1;
	private $value2;

	public function __construct($column, $searchTerm){
		$this->column = $column;
		$this->searchTerm = '%' . addcslashes( $searchTerm, '_%\\' ) . '%';
	}

	public function buildSql() {
		return implode(' ', [$this->column, "LIKE", '%s']);
	}

	public function getBindings(){
		return [$this->searchTerm];
	}

}
