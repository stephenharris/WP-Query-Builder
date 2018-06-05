<?php
namespace WPQueryBuilder;

class BasicWhereClause implements WhereClause {

	private $column;
	private $operator;
	private $value;

	public function __construct($column, $operator, $value){
		$this->column = $column;
		$this->operator = $operator;
		$this->value = $value;

		$this->assertValidOperator($operator);
	}

	public function buildSql() {
		return implode(' ', [$this->column, $this->operator, $this->getPlaceholder($this->value)]);
	}

	public function getBindings(){
		return [$this->value];
	}

	private function assertValidOperator($operator){
		$allowed = [
			WhereClause::EQUALS, WhereClause::NOTEQUALS, WhereClause::GREATER,
			WhereClause::LESS, WhereClause::GREATEREQUALS, WhereClause::LESSEQUALS
		];
		if(!in_array($operator, $allowed, true)){
			throw new \InvalidArgumentException(sprintf(
				"Invalid operator for WHERE clause. Allowed values are: %s. You gave: '%s'",
				implode(', ', $allowed),
				$operator
			));
		}
	}

	/**
	 * @param string | float | integer $value
	 * @return string
	 */
	private function getPlaceholder($value) {
		$placeholder = '%s';
		if(is_int($value)) {
			$placeholder = '%d';
		} else if(is_float($value)) {
			$placeholder = '%f';
		}
		return $placeholder;
	}
}
