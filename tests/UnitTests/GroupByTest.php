<?php
use PHPUnit\Framework\TestCase;
use WPQueryBuilder\Query;

final class GroupByTest extends TestCase {

	public function setUp(){
		parent::setUp();
		$this->wpdb = $this->createMock(\WPQueryBuilder\Wpdb::class);
	}

	public function testGroupBy(){
		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT * FROM tablename GROUP BY field2 ORDER BY field DESC;"
		);

		$qb = new Query($this->wpdb);
		$qb->select()
			->from("tablename")
			->orderby('field', 'DESC')
			->groupBy('field2')
			->get();
	}

}
