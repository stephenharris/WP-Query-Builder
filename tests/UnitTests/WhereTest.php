<?php
use PHPUnit\Framework\TestCase;
use WPQueryBuilder\Query;
use WPQueryBuilder\WhereClause;
use WPQueryBuilder\NullWhereClause;

final class WhereTest extends TestCase {

	public function setUp(){
		parent::setUp();
		$this->wpdbSpy = new MockWpdb();
		$this->wpdb = $this->createMock(\WPQueryBuilder\Wpdb::class);
	}

	/**
	 * @dataProvider whereOperatorProvider
	 */
	public function testWithWhereCondition($operator, $expectedSql){
		$qb = new Query($this->wpdbSpy);
		$qb->select(["field","field2"])
			->from("tablename")
			->where("field", $operator, 'foobar')
			->get();
		$this->assertEquals($expectedSql, $this->wpdbSpy->getLastInvocation());
	}

	public function whereOperatorProvider(){
		return [
			['=',"SELECT field, field2 FROM tablename WHERE field = 'foobar';"],
			[WhereClause::EQUALS,"SELECT field, field2 FROM tablename WHERE field = 'foobar';"],
			['!=',"SELECT field, field2 FROM tablename WHERE field != 'foobar';"],
			[WhereClause::NOTEQUALS,"SELECT field, field2 FROM tablename WHERE field != 'foobar';"],
			['<',"SELECT field, field2 FROM tablename WHERE field < 'foobar';"],
			[WhereClause::LESS,"SELECT field, field2 FROM tablename WHERE field < 'foobar';"],
			['>',"SELECT field, field2 FROM tablename WHERE field > 'foobar';"],
			[WhereClause::GREATER,"SELECT field, field2 FROM tablename WHERE field > 'foobar';"],
			['<=',"SELECT field, field2 FROM tablename WHERE field <= 'foobar';"],
			[WhereClause::LESSEQUALS,"SELECT field, field2 FROM tablename WHERE field <= 'foobar';"],
			['>=',"SELECT field, field2 FROM tablename WHERE field >= 'foobar';"],
			[WhereClause::GREATEREQUALS,"SELECT field, field2 FROM tablename WHERE field >= 'foobar';"]
		];

	}

	public function testEqualsConciseSyntax(){
		$qb = new Query($this->wpdbSpy);
		$qb->select(["field","field2"])
			->from("tablename")
			->where("field", 'foobar')
			->get();
		$this->assertEquals("SELECT field, field2 FROM tablename WHERE field = 'foobar';", $this->wpdbSpy->getLastInvocation());
	}

	public function testEqualsAndOrWhereConciseSyntax(){
		$qb = new Query($this->wpdbSpy);
		$qb->select(["field","field2"])
			->from("tablename")
			->andWhere("field", 'foo')
			->orWhere("field", 'bar')
			->get();
		$this->assertEquals("SELECT field, field2 FROM tablename WHERE field = 'foo' OR field = 'bar';", $this->wpdbSpy->getLastInvocation());
	}

	/**
	 * @dataProvider whereNullOperatorProvider
	 */
	public function testWithWherenNullCondition($operator, $expectedSql){
		$qb = new Query($this->wpdbSpy);
		$qb->select(["field","field2"])
			->from("tablename")
			->where(new NullWhereClause('field', $operator))
			->get();
		$this->assertEquals($expectedSql, $this->wpdbSpy->getLastInvocation());
	}

	public function whereNullOperatorProvider(){
		return [
			['IS NULL',"SELECT field, field2 FROM tablename WHERE field IS NULL;"],
			[WhereClause::ISNULL,"SELECT field, field2 FROM tablename WHERE field IS NULL;"],
			['IS NOT NULL',"SELECT field, field2 FROM tablename WHERE field IS NOT NULL;"],
			[WhereClause::ISNOTNULL,"SELECT field, field2 FROM tablename WHERE field IS NOT NULL;"],
		];

	}

	public function testWithWhereNullConditionWithoutClass(){
		$qb = new Query($this->wpdbSpy);
		$qb->select(["field","field2"])
			->from("tablename")
			->andWhereNull("field")
			->get();
		$this->assertEquals("SELECT field, field2 FROM tablename WHERE field IS NULL;", $this->wpdbSpy->getLastInvocation());
	}

	public function testOrNull(){
		$qb = new Query($this->wpdbSpy);
		$qb->select(["field","field2"])
			->from("tablename")
			->where("field", "=", 5)
			->orWhereNull("field")
			->get();
		$this->assertEquals("SELECT field, field2 FROM tablename WHERE field = 5 OR field IS NULL;", $this->wpdbSpy->getLastInvocation());
	}

	public function testWithMultipleWhereCondition(){
		$qb = new Query($this->wpdbSpy);
		$qb->select(["field","field2"])
			->from("tablename")
			->where("field", "=", 'foobar')
			->where("field2", WhereClause::GREATER, 5)
			->get();
		$this->assertEquals("SELECT field, field2 FROM tablename WHERE field = 'foobar' AND field2 > 5;", $this->wpdbSpy->getLastInvocation());
	}


	public function testWithOrWhereCondition(){
		$qb = new Query($this->wpdbSpy);
		$qb->select(["field","field2"])
			->from("tablename")
			->where("field", "=", 'foobar')
			->orWhere("field2", WhereClause::GREATER, 5)
			->get();
		$this->assertEquals("SELECT field, field2 FROM tablename WHERE field = 'foobar' OR field2 > 5;", $this->wpdbSpy->getLastInvocation());
	}


	public function testWithNestedWhere(){
		$qb = new Query($this->wpdbSpy);

		$composite = new WPQueryBuilder\CompositeWhereClause();
		$composite->andWhere(new WPQueryBuilder\BasicWhereClause('field3', '!=', 3.1));
		$composite->orWhere(new WPQueryBuilder\BasicWhereClause('field4', '>=', 4));
		$composite->orWhere(new WPQueryBuilder\BasicWhereClause('field5', '<=', 5));

		$composite2 = new WPQueryBuilder\CompositeWhereClause();
		$composite2->andWhere(new WPQueryBuilder\BasicWhereClause('field2', '=', 2));
		$composite2->orWhere($composite);

		$qb->select()
			->from("tablename")
			->where("field1", '=', 1)
			->andWhere($composite2)
			->get();
		$this->assertEquals(
			"SELECT * FROM tablename WHERE field1 = 1 AND (field2 = 2 OR (field3 != 3.100000 OR field4 >= 4 OR field5 <= 5));",
			$this->wpdbSpy->getLastInvocation()
		);
	}


	public function testWithOrWhereInstance(){
		$qb = new Query($this->wpdbSpy);

		$composite = new WPQueryBuilder\CompositeWhereClause();
		$composite->andWhere(new WPQueryBuilder\BasicWhereClause('field3', '!=', 3.1));
		$composite->orWhere(new WPQueryBuilder\BasicWhereClause('field4', '>=', 4));
		$composite->orWhere(new WPQueryBuilder\BasicWhereClause('field5', '<=', 5));

		$composite2 = new WPQueryBuilder\CompositeWhereClause();
		$composite2->andWhere(new WPQueryBuilder\BasicWhereClause('field2', '=', 2));
		$composite2->andWhere($composite);

		$qb->select()
			->from("tablename")
			->where("field1", '=', 1)
			->orWhere($composite2)
			->get();
		$this->assertEquals(
			"SELECT * FROM tablename WHERE field1 = 1 OR (field2 = 2 AND (field3 != 3.100000 OR field4 >= 4 OR field5 <= 5));",
			$this->wpdbSpy->getLastInvocation()
		);
	}

	public function testWithBetweenWhereCondition(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT * FROM tablename WHERE field BETWEEN %s AND %s;",
			['2018-05-31 00:37:00', '2018-05-31 07:00:00']
		);

		$qb = new Query($this->wpdb);
		$qb->select()
			->from("tablename")
			->whereBetween("field", "2018-05-31 00:37:00", "2018-05-31 07:00:00")
			->get();
	}


	public function testWithBetweenIntegerWhereCondition(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT * FROM tablename WHERE field BETWEEN %d AND %d;",
			[3, 7]
		);

		$qb = new Query($this->wpdb);
		$qb->select()
			->from("tablename")
			->whereBetween("field", 3, 7)
			->get();
	}

	public function testWithBetweenFloatWhereCondition(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT * FROM tablename WHERE field BETWEEN %f AND %f;",
			[3.5, 7.5]
		);

		$qb = new Query($this->wpdb);
		$qb->select()
			->from("tablename")
			->whereBetween("field", 3.5, 7.5)
			->get();
	}

	public function testWithWhereInCondition(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT * FROM tablename WHERE field IN (%s, %s, %s);",
			['foo', 'bar', 'baz']
		);

		$qb = new Query($this->wpdb);
		$qb->select()
			->from("tablename")
			->whereIn("field", ['foo', 'bar', 'baz'])
			->get();
	}

	public function testSearchSingleColumn(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT * FROM tablename WHERE (field LIKE %s) AND field2 = %s;",
			['%something%','value']
		);

		$qb = new Query($this->wpdb);
		$qb->select()
			->from("tablename")
			->search("field", "something")
			->where("field2", "=", "value")
			->get();
	}

	public function testSearchMulitpleColumns(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT * FROM tablename WHERE (field LIKE %s OR field2 LIKE %s OR field3 LIKE %s);",
			['%something%', '%something%', '%something%']
		);

		$qb = new Query($this->wpdb);
		$qb->select()
			->from("tablename")
			->search(["field", "field2", "field3"], "something")
			->get();
	}

}
