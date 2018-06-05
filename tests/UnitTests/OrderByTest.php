<?php
use PHPUnit\Framework\TestCase;
use WPQueryBuilder\Query;

final class OrderByTest extends TestCase {

	public function setUp(){
		parent::setUp();
		$this->wpdb = $this->createMock(\WPQueryBuilder\Wpdb::class);
	}

	/**
	 * @dataProvider orderByDESCProvider
	 */
	public function testOrderDesc($order){

		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT * FROM tablename ORDER BY field DESC;"
		);

		$qb = new Query($this->wpdb);
		$qb->select()->from("tablename")->orderby('field', $order)->get();
	}

	public function orderByDESCProvider(){
		return [
			['DESC'],
			['DeSc'],
			['desc'],
		];
	}

	/**
	 * @dataProvider orderByASCProvider
	 */
	public function testOrderAsc($order){

		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT * FROM tablename ORDER BY field ASC;"
		);

		$qb = new Query($this->wpdb);
		$qb->select()->from("tablename")->orderby('field', $order)->get();
	}

	public function orderByASCProvider(){
		return [
			'uppercase' => ['ASC'],
			'random casing' => ['AsC'],
			'lowercase' => ['asc'],
			'random string' => ['foobar!'],
		];
	}

	public function testMultipleOrderBys(){

		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT * FROM tablename ORDER BY field ASC, field2 DESC;"
		);

		$qb = new Query($this->wpdb);
		$qb->select()->from("tablename")
			->orderby('field', 'ASC')
			->thenOrderBy('field2', 'DESC')
			->get();
	}

}
