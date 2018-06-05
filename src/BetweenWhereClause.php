<?php
namespace WPQueryBuilder;

class BetweenWhereClause implements WhereClause {

	private $column;
	private $value1;
	private $value2;

	public function __construct($column, $value1, $value2){
		$this->column = $column;
		$this->value1 = $value1;
		$this->value2 = $value2;
	}

	public function buildSql() {
		return implode(' ', [$this->column, "BETWEEN", $this->getPlaceholder($this->value1), 'AND', $this->getPlaceholder($this->value2)]);
	}

	public function getBindings(){
		return [$this->value1,$this->value2];
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
