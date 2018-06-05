<?php
use WPQueryBuilder\Query;

class DeleteTest extends WP_UnitTestCase
{
	public function setUp(){
		parent::setUp();
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->post = wp_insert_post(array(
			'post_title' => 'My Published Post',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_date'   => '2018-05-02 19:00:00'
		));
	}

	public function testDelete()
	{
		$qb = new Query($this->wpdb);
		$id = $qb->table("wptests_posts")
			->where('post_title', '=', 'My Published Post')
			->where('post_status', '=', 'publish')
			->delete();

		$qb = new Query($this->wpdb);
		$ids = $qb->select('ID')
			->from("wptests_posts")
			->getColumn();

		$this->assertCount(0, $ids);
	}

}
