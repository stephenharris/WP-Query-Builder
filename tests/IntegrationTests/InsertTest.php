<?php
use WPQueryBuilder\Query;

class InsertTest extends WP_UnitTestCase
{
	public function setUp(){
		parent::setUp();
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function testInsert()
	{
		$qb = new Query($this->wpdb);
		$id = $qb->table("wptests_posts")->insert([
			'post_title' => 'My Published Post',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_date'   => '2018-05-02 19:00:00'
		]);

		$this->assertInternalType('integer', $id);

		$qb = new Query($this->wpdb);
		$ids = $qb->select('ID')
			->from("wptests_posts")
			->where("post_title", "=", "My Published Post")
			->where("post_status", "=", "publish")
			->getColumn();

		$this->assertCount(1, $ids);
		$this->assertEquals($id, $ids[0]);
	}

}
