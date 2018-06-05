<?php
namespace WPQueryBuilder;

interface WhereClause {

	const EQUALS = '=';
	const IN = 'IN';
	const NOTEQUALS = '!=';
	const GREATER = '>';
	const LESS = '<';
	const GREATEREQUALS = '>=';
	const LESSEQUALS = '<=';

	public function buildSql();
	public function getBindings();

}
