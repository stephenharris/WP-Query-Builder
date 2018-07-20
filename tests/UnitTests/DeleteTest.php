<?php
use PHPUnit\Framework\TestCase;
use WPQueryBuilder\Query;
use WPQueryBuilder\WhereClause;

final class DeleteTest extends TestCase {

	public function setUp(){
		parent::setUp();
		$this->wpdb = $this->createMock(\WPQueryBuilder\Wpdb::class);
	}

	public function testDelete(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"DELETE FROM tablename WHERE id = %d;",
			[123]
		);

		$qb = new Query($this->wpdb);
		$qb->table("tablename")
			->where("id", "=", 123)
			->delete();
	}

	/**
	 * @expectedException WPQueryBuilder\QueryException
	 * @expectedExceptionMessage Error in MySql statement...
	 */
	public function testDeleteError(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"DELETE FROM tablename WHERE id = %d;",
			[123]
		);
		$this->wpdb->last_error = "Error in MySql statement...";

		$qb = new Query($this->wpdb);
		$qb->table("tablename")
			->where("id", "=", 123)
			->delete();
	}

	/**
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage No table set. Please call ->table('tablename') before calling ->delete().
	 */
	public function testDeleteWithoutSettingTable(){
		$qb = new Query($this->wpdb);
		$qb->where("id", "=", 123)->delete();
	}

	/**
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage No where condition set. Please use deleteAll instead.
	 */
	public function testDeleteWithoutSettingWhereConditions(){
		$qb = new Query($this->wpdb);
		$qb->table("tablename")->delete();
	}

	public function testDeleteAll(){
		$this->wpdb->expects($this->once())->method('query')->with("DELETE FROM tablename;");

		$qb = new Query($this->wpdb);
		$qb->table("tablename")
			->deleteAll();
	}

}
