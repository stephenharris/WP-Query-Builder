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
	const ISNULL = 'IS NULL';
	const ISNOTNULL = 'IS NOT NULL';

	public function buildSql();
	public function getBindings();

}
