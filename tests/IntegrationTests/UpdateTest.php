<?php
use WPQueryBuilder\Query;

class UpdateTest extends WP_UnitTestCase
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
			->set([
				'post_status' => 'draft'
			])
			->where('ID', '=', $this->post)
			->update();

		$qb = new Query($this->wpdb);
		$post = $qb->select('*')
			->from("wptests_posts")
			->where('ID', '=', $this->post)
			->first();

		$this->assertEquals($this->post, $post->ID);
		$this->assertEquals('My Published Post', $post->post_title);
		$this->assertEquals('draft', $post->post_status);
	}

}
