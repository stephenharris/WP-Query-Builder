<?php
use PHPUnit\Framework\TestCase;
use WPQueryBuilder\Query;
use WPQueryBuilder\WhereClause;

final class SelectTest extends TestCase {

	public function setUp(){
		parent::setUp();
		$this->wpdbSpy = new MockWpdb();
		$this->wpdb = $this->createMock(\WPQueryBuilder\Wpdb::class);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testgetWithoutType(){
		$qb = new Query($this->wpdbSpy);
		$qb->from("tablename")->get();
	}

	public function testtStarFromTable(){
		$qb = new Query($this->wpdbSpy);
		$qb->select()->from("tablename")->get();
		$this->assertEquals("SELECT * FROM tablename;", $this->wpdbSpy->getLastInvocation());
	}

	public function testtFieldFromTable(){
		$qb = new Query($this->wpdbSpy);
		$qb->select("field")->from("tablename")->get();
		$this->assertEquals("SELECT field FROM tablename;", $this->wpdbSpy->getLastInvocation());
	}

	public function testtMultipleFieldsFromTable(){
		$qb = new Query($this->wpdbSpy);
		$qb->select(["field","field2"])->from("tablename")->get();
		$this->assertEquals("SELECT field, field2 FROM tablename;", $this->wpdbSpy->getLastInvocation());
	}

	public function testSelectFoundRows(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT SQL_CALC_FOUND_ROWS * FROM tablename WHERE field = %s LIMIT 3;",
			['something']
		);

		$this->wpdb->expects($this->once())->method('get_var')->with(
			"SELECT FOUND_ROWS();"
		);

		$qb = new Query($this->wpdb);
		$qb->select()
			->from("tablename")
			->where("field", "=", "something")
			->countFoundRows()
			->limit(3)
			->get();

		$qb->getTotalRowCount();
	}

	/**
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage getTotalRowCount() can only be called if you have called countFoundRows() before executing the query.
	 */
	public function testCantGetTotalRowWithoutCallingFoundRows(){
		$qb = new Query($this->wpdb);
		$qb->select()
			->from("tablename")
			->where("field", "=", "something")
			->limit(3)
			->get();

		$qb->getTotalRowCount();
	}

	public function testSelectDistnct(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT DISTINCT field1, field2 FROM tablename WHERE field = %s;",
			['something']
		);

		$qb = new Query($this->wpdb);
		$qb->selectDistinct(['field1', 'field2'])
			->from("tablename")
			->where("field", "=", "something")
			->get();
	}

	public function testSelectColumn(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT field FROM tablename WHERE field = %s;",
			['something']
		)->willReturn("SELECT field FROM tablename WHERE field = 'something';");

		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT field FROM tablename WHERE field = 'something';"
		);

		$qb = new Query($this->wpdb);
		$qb->select('field')
			->from("tablename")
			->where("field", "=", "something")
			->getColumn();
	}


	public function testSelectNamedColumn(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT field, field2 FROM tablename WHERE field = %s;",
			['something']
		)->willReturn("SELECT field, field2 FROM tablename WHERE field = 'something';");

		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT field, field2 FROM tablename WHERE field = 'something';"
		)->willReturn([
			(object) ['field' => 'foo', 'field2' => 'bar' ],
			(object) ['field' => 'baz', 'field2' => 'qux'],
			(object) ['field' => 'quux', 'field2' => 'corge' ],
			(object) ['field' => 'uier', 'field2' => 'grault' ],
		]);

		$qb = new Query($this->wpdb);
		$columnValues = $qb->select(['field','field2'])
			->from("tablename")
			->where("field", "=", "something")
			->getColumn('field2');

		$this->assertEquals(['bar', 'qux', 'corge', 'grault'], $columnValues);
	}


	public function testSelectColumnNoResults(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT field, field2 FROM tablename WHERE field = %s;",
			['something']
		)->willReturn("SELECT field, field2 FROM tablename WHERE field = 'something';");

		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT field, field2 FROM tablename WHERE field = 'something';"
		)->willReturn([]);

		$qb = new Query($this->wpdb);
		$columnValues = $qb->select(['field','field2'])
			->from("tablename")
			->where("field", "=", "something")
			->getColumn('field2');

		$this->assertEquals([], $columnValues);
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Column field3 not found in returned set. Must be one of field, field2.
	 */
	public function testSelectColumnInvalidColumn(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT field, field2 FROM tablename WHERE field = %s;",
			['something']
		)->willReturn("SELECT field, field2 FROM tablename WHERE field = 'something';");

		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT field, field2 FROM tablename WHERE field = 'something';"
		)->willReturn([
			(object) ['field' => 'foo', 'field2' => 'bar' ],
			(object) ['field' => 'baz', 'field2' => 'qux']
		]);

		$qb = new Query($this->wpdb);
		$columnValues = $qb->select(['field','field2'])
			->from("tablename")
			->where("field", "=", "something")
			->getColumn('field3');
	}


	public function testSelectScalar(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT MAX(field) FROM tablename WHERE field2 = %s;",
			['something']
		)->willReturn("SELECT MAX(field) FROM tablename WHERE field2 = 'something';");

		$this->wpdb->expects($this->once())->method('get_var')->with(
			"SELECT MAX(field) FROM tablename WHERE field2 = 'something';"
		);

		$qb = new Query($this->wpdb);
		$qb->select('MAX(field)')
			->from("tablename")
			->where("field2", "=", "something")
			->getScalar();
	}


	public function testfirstSingleResultFound(){

		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT field FROM tablename LIMIT 1;"
		)->willReturn([
			(object) ['field' => 'value']
		]);

		$qb = new Query($this->wpdb);
		$result = $qb->select('field')
			->from("tablename")
			->first();

		$this->assertEquals((object) ['field' => 'value'], $result);
	}


	public function testfirstNoResultsFound(){

		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT field FROM tablename LIMIT 1;"
		)->willReturn([]);

		$qb = new Query($this->wpdb);
		$result = $qb->select('field')
			->from("tablename")
			->first();

		$this->assertEquals(null, $result);
	}

	public function testfirstMultipleResultsFound(){

		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT field FROM tablename LIMIT 1;"
		)->willReturn(
			[
				(object) ['field' => 'value'],
				(object) ['field' => 'anothervalue'],
				(object) ['field' => 'yetanothervalue'],
			]
		);

		$qb = new Query($this->wpdb);
		$result = $qb->select('field')
			->from("tablename")
			->first();

		$this->assertEquals((object) ['field' => 'value'], $result);
	}

}
