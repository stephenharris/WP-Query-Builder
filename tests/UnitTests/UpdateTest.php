<?php
use PHPUnit\Framework\TestCase;
use WPQueryBuilder\Query;
use WPQueryBuilder\WhereClause;

final class UpdateTest extends TestCase {

	public function setUp(){
		parent::setUp();
		$this->wpdb = $this->createMock(\WPQueryBuilder\Wpdb::class);
	}

	public function testUpdate(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"UPDATE tablename SET field1 = %s, field2 = %s WHERE id = %d;",
			["value", "value2", 123]
		);

		$qb = new Query($this->wpdb);
		$qb->table("tablename")
			->set([
				'field1' => 'value',
				'field2' => 'value2',
			])
			->where("id", "=", 123)
			->update();
	}

	public function testSetValuesUsingUpdate(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"UPDATE tablename SET field1 = %s, field2 = %s WHERE id = %d;",
			["value", "value2", 123]
		);

		$qb = new Query($this->wpdb);
		$qb->table("tablename")
			->where("id", "=", 123)
			->update([
				'field1' => 'value',
				'field2' => 'value2',
			]);
	}

	/**
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage No where condition set. Please use updateAll instead.
	 */
	public function testUpdateWithoutWhere(){
		$qb = new Query($this->wpdb);
		$qb->table("tablename")
			->set([
				'field1' => 'value',
				'field2' => 'value2',
			])
			->update();
	}

	/**
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage No where condition set. Please use updateAll instead.
	 */
	public function testSetValuesUsingUpdateWithoutWhere(){
		$qb = new Query($this->wpdb);
		$qb->table("tablename")
			->update([
				'field1' => 'value',
				'field2' => 'value2',
			]);
	}


	public function testUpdateAll(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"UPDATE tablename SET field1 = %s, field2 = %s;",
			["value", "value2"]
		);

		$qb = new Query($this->wpdb);
		$qb->table("tablename")
			->set([
				'field1' => 'value',
				'field2' => 'value2',
			])
			->updateAll();
	}

	public function testUpdateAllUsingUpdateAlll(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"UPDATE tablename SET field1 = %s, field2 = %s;",
			["value", "value2"]
		);

		$qb = new Query($this->wpdb);
		$qb->table("tablename")
			->updateAll([
				'field1' => 'value',
				'field2' => 'value2',
			]);
	}


	/**
	 * @expectedException WPQueryBuilder\QueryException
	 * @expectedExceptionMessage Error in MySql statement...
	 */
	public function testUpdateError(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"UPDATE tablename SET field1 = %s, field2 = %s WHERE id = %d;",
			["value", "value2", 123]
		);
		$this->wpdb->last_error = "Error in MySql statement...";

		$qb = new Query($this->wpdb);
		$qb->table("tablename")
			->set([
				'field1' => 'value',
				'field2' => 'value2',
			])
			->where("id", "=", 123)
			->update();
	}

	/**
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage No table set. Please call ->table('tablename') before calling ->update().
	 */
	public function testUpdateWithoutSettingTable(){
		$qb = new Query($this->wpdb);
		$qb->set([
			'field1' => 'value',
			'field2' => 'value2',
		])
		->where("id", "=", 123)
		->update();
	}

	/**
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage update() can only be called if you have called set() or passed in an array of fields to update.
	 */
	public function testUpdateWithoutSettingFields(){
		$qb = new Query($this->wpdb);
		$qb->table('tablename')->where("id", "=", 123)->update();
	}


	public function testUpdateNull(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"UPDATE tablename SET field1 = %s, field2 = NULL WHERE id = %d;",
			["value", 123]
		);

		$qb = new Query($this->wpdb);
		$qb->table("tablename")
			->set([
				'field1' => 'value',
				'field2' => null,
			])
			->where("id", "=", 123)
			->update();

	}
}
