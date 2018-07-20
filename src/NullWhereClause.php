<?php
namespace WPQueryBuilder;

class NullWhereClause implements WhereClause {

	private $column;
	private $operator;

	public function __construct($column, $isNull = WhereClause::ISNULL){
		$this->column = $column;
		$this->operator = $isNull;
		$this->assertValidOperator($isNull);
	}

	public function buildSql() {
		return implode(' ', [$this->column, $this->operator]);
	}

	public function getBindings(){
		return [];
	}

	private function assertValidOperator($operator){
		$allowed = [
			WhereClause::ISNULL, WhereClause::ISNOTNULL
		];
		if(!in_array($operator, $allowed, true)){
			throw new \InvalidArgumentException(sprintf(
				"Invalid operator for NullWhereClause clause. Allowed values are: %s. You gave: '%s'",
				implode(', ', $allowed),
				$operator
			));
		}
	}

}
