<?php
use PHPUnit\Framework\TestCase;
use WPQueryBuilder\Query;
use WPQueryBuilder\WhereClause;

final class InsertTest extends TestCase {

	public function setUp(){
		parent::setUp();
		$this->wpdb = $this->createMock(\WPQueryBuilder\Wpdb::class);
	}

	public function testInsert(){
		$this->wpdb->expects($this->once())->method('insert')->with(
			"tablename",
			[
				'field1' => 'value',
				'field2' => 'value2',
			]
		);
		$this->wpdb->insert_id = 42;

		$qb = new Query($this->wpdb);
		$id = $qb->table("tablename")->insert([
			'field1' => 'value',
			'field2' => 'value2',
		]);

		$this->assertEquals(42, $id);

	}

	/**
	 * @expectedException WPQueryBuilder\QueryException
	 * @expectedExceptionMessage Error in MySql statement...
	 */
	public function testInsertError(){
		$this->wpdb->expects($this->once())->method('insert')->with(
			"tablename",
			[
				'field1' => 'value',
				'field2' => 'value2',
			]
		);
		$this->wpdb->last_error = "Error in MySql statement...";

		$qb = new Query($this->wpdb);
		$id = $qb->table("tablename")->insert([
			'field1' => 'value',
			'field2' => 'value2',
		]);
	}

	/**
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage No table set. Please call ->table('tablename') before calling ->insert().
	 */
	public function testInsertWithoutSettingTable(){
		$qb = new Query($this->wpdb);
		$id = $qb->insert([
			'field1' => 'value',
			'field2' => 'value2',
		]);
	}

	public function testInsertNull(){
		$this->wpdb->expects($this->once())->method('insert')->with(
			"tablename",
			[
				'field1' => null,
				'field2' => 'value2',
			]
		);
		$this->wpdb->insert_id = 42;

		$qb = new Query($this->wpdb);
		$id = $qb->table("tablename")->insert([
			'field1' => null,
			'field2' => 'value2',
		]);

		$this->assertEquals(42, $id);

	}
}
