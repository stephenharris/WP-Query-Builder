<?php
use PHPUnit\Framework\TestCase;
use WPQueryBuilder\Query;

final class LimitOffsetTest extends TestCase {

	public function setUp(){
		parent::setUp();
		$this->wpdb = $this->createMock(\WPQueryBuilder\Wpdb::class);
	}

	/**
	 * @dataProvider limitOffsetProvider
	 */
	public function testOrderDesc($limit, $offset, $expectedSql){

		$this->wpdb->expects($this->once())->method('get_results')->with($expectedSql);

		$qb = new Query($this->wpdb);
		$qb->select()->from("tablename")->limit($limit)->offset($offset)->get();
	}

	public function limitOffsetProvider(){
		return [
			[10, 0, "SELECT * FROM tablename LIMIT 10;"],
			[10, 5, "SELECT * FROM tablename LIMIT 5, 10;"],
			[null, null, "SELECT * FROM tablename;"],
			[null, 10, "SELECT * FROM tablename;"],
			[0, 10, "SELECT * FROM tablename;"],
		];
	}


}
