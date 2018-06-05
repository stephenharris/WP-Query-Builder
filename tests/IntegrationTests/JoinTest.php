<?php
use WPQueryBuilder\Query;

class JoinTest extends WP_UnitTestCase
{
	public function setUp(){
		parent::setUp();
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->authorId = wp_create_user('author', 'password', 'author@example.com');
		wp_create_user('nemo', 'password', 'nemo@example.com');

		$this->post = wp_insert_post(array(
			'post_title' => 'My Published Post',
			'post_status' => 'publish',
			'post_author' => $this->authorId,
			'post_date'   => '2018-05-02 19:00:00'
		));
		$this->post2 = wp_insert_post(array(
			'post_title' => 'My Second Post',
			'post_status' => 'draft',
			'post_author' => $this->authorId,
			'post_date'   => '2018-05-07 19:00:00'
		));
		$this->post3 = wp_insert_post(array(
			'post_title' => 'My Third Post',
			'post_status' => 'publish',
			'post_author' => $this->authorId,
			'post_date'   => '2018-05-12 19:00:00'
		));
		$this->post4 = wp_insert_post(array(
			'post_title' => 'Admin Post',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_date'   => '2018-05-27 19:00:00'
		));
	}

	public function testGetColumn()
	{
		$qb = new Query($this->wpdb);
		$titles = $qb->select("post_title")->from("wptests_posts")
			->where("post_status","=", "publish")
			->getColumn();

		$this->assertEquals(
			['My Published Post', 'My Third Post', 'Admin Post'],
			$titles
		);
	}

	public function testJoin()
	{
		$qb = new Query($this->wpdb);
		$authors = $qb->select(["user_login", "COUNT(*) AS posts"])
			->from("wptests_users")
			->innerJoin("wptests_posts AS p", "wptests_users.ID", "=", "p.post_author")
			->where("post_status","=", "publish")
			->groupBy("p.post_author")
			->orderBy("posts", "DESC")
			->get();

		$this->assertEquals([
			(object)['user_login' => 'author', 'posts' => 2],
			(object)['user_login' => 'admin', 'posts' => 1],
		],$authors);
	}


}
