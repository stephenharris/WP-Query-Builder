<?php
use WPQueryBuilder\Query;

class ResultsTest extends WP_UnitTestCase
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

	public function testGetSingleResult()
	{
		$qb = new Query($this->wpdb);
		$latestPost = $qb->select(["post_title", "post_author"])
			->from("wptests_posts")
			->where("post_status","=", "publish")
			->orderby('post_date', 'DESC')
			->limit(1)
			->first();

		$this->assertEquals(
			(object)['post_title' => 'Admin Post', 'post_author' => 1],
			$latestPost
		);
	}

	public function testGetScalar()
	{
		$qb = new Query($this->wpdb);
		$postCount = $qb->select("COUNT(*)")->from("wptests_posts")
			->where("post_author","=", $this->authorId)
			->getScalar();

		$this->assertEquals(3,$postCount);
	}


	public function testGetFoundRows()
	{
		$qb = new Query($this->wpdb);
		$posts = $qb->select("*")
			->from("wptests_posts")
			->countFoundRows()
			->limit(2)
			->get();

		$this->assertCount(2, $posts);
		$this->assertEquals(4, $qb->getTotalRowCount());
	}


	public function testGetNamedColumn()
	{
		$qb = new Query($this->wpdb);
		$posts = $qb->select("*")
			->from("wptests_posts")
			->getColumn('post_title');

		$this->assertEquals(['My Published Post', 'My Second Post', 'My Third Post', 'Admin Post'], $posts);
	}
}
