<?php
namespace WPQueryBuilder;

class JoinClause {

	const LEFT = 'LEFT';
	const RIGHT = 'RIGHT';
	const INNER = 'INNER';
	const FULL = 'FULL';
	const USING = 'USING';

	private $type = null;

	private $table = null;

	private $column1 = null;

	private $column2 = null;

	private $operator = null;

	public function __construct($type, $table){
		$this->assertValidJoinType($type);
		$this->type = $type;
		$this->table = $table;
	}

	public function on($first, $operator = JoinClause::USING, $second = null) {
		$this->column1 = $first;
		$this->column2 = $second;

		$this->assertValidOperator($operator);

		$this->operator = $operator;
	}


	public function buildSql() {
		if($this->operator === JoinClause::USING){
			$parts = [
				$this->type, "JOIN", $this->table, 'USING', $this->column1
			];
		} else {
			$parts = [
				$this->type, "JOIN", $this->table, 'ON', $this->column1,
				$this->operator, $this->column2
			];
		}

		return implode(' ', array_filter($parts));
	}

	private function assertValidJoinType($type){
		$allowed = [
			JoinClause::LEFT, JoinClause::RIGHT, JoinClause::INNER, JoinClause::FULL
		];
		if(!in_array($type, $allowed, true)){
			throw new \InvalidArgumentException(sprintf(
				"Invalid JOIN type. Allowed values are: %s. You gave: '%s'",
				implode(', ', $allowed),
				$type
			));
		}
	}

	private function assertValidOperator($operator){
		$allowed = [
			WhereClause::EQUALS, WhereClause::NOTEQUALS, WhereClause::GREATER,
			WhereClause::LESS, WhereClause::GREATEREQUALS, WhereClause::LESSEQUALS,
			JoinClause::USING
		];
		if(!in_array($operator, $allowed, true)){
			throw new \InvalidArgumentException(sprintf(
				"Invalid operator for ON. Allowed values are: %s. You gave: '%s'",
				implode(', ', $allowed),
				$operator
			));
		}
	}
}
