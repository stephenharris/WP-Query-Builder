<?php
use WPQueryBuilder\Query;

class SqlInjectionTest extends WP_UnitTestCase
{
	public function setUp(){
		parent::setUp();
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function testEscapeWhereField()
	{
		$qb = new Query($this->wpdb);
		$injected = "x' OR post_status!='published";
		$re = $qb->select("post_title")->from("wptests_posts")
			->where("post_status",'=', "publish")
			->where("post_title",'=', $injected)
			->get();

		$this->assertEquals(
			"SELECT post_title FROM wptests_posts WHERE post_status = 'publish' AND post_title = 'x\' OR post_status!=\'published';",
			$this->wpdb->last_query
		);
	}

	public function testTableNameIsNotEscaped()
	{
		global $wpdb;
		$qb = new Query($this->wpdb);
		$injected = "wptests_posts WHERE 1=1; --";
		$re = $qb->select("post_title")->from($injected)
			->where("post_status",'=', "publish")
			->get();

		$this->assertEquals(
			"SELECT post_title FROM wptests_posts WHERE 1=1; -- WHERE post_status = 'publish';",
			$this->wpdb->last_query
		);
	}


	public function testBetweenColumnNameIsNotEscaped()
	{
		$qb = new Query($this->wpdb);
		$injected = "2018-05-31 00:37:00' AND '2018-05-31 07:00:00' OR 1=1; --";
		$qb->select()
			->from("wptests_posts")
			->whereBetween("post_date", $injected, "2018-05-31 07:00:00")
			->get();

		$this->assertEquals(
			"SELECT * FROM wptests_posts WHERE post_date BETWEEN '2018-05-31 00:37:00\' AND \'2018-05-31 07:00:00\' OR 1=1; --' AND '2018-05-31 07:00:00';",
			$this->wpdb->last_query
		);
	}

	public function testLimitEscaped()
	{
		$qb = new Query($this->wpdb);
		$injected = "5; DROP wptests_users; --";
		$re = $qb->select()->from("wptests_posts")
			->limit($injected)
			->get();

		$this->assertEquals(
			"SELECT * FROM wptests_posts LIMIT 5;",
			$this->wpdb->last_query
		);
	}


	public function testOffsetEscaped()
	{
		$qb = new Query($this->wpdb);
		$injected = "10; DROP wptests_users; --";
		$re = $qb->select()->from("wptests_posts")
			->limit(5)
			->offset($injected)
			->get();

		$this->assertEquals(
			"SELECT * FROM wptests_posts LIMIT 10, 5;",
			$this->wpdb->last_query
		);
	}

	public function testOrderASCEscaped()
	{
		$qb = new Query($this->wpdb);
		$injected = "ASC; DROP wptests_users; --";
		$re = $qb->select()->from("wptests_posts")
			->orderBy('post_date', $injected)
			->get();

		$this->assertEquals(
			"SELECT * FROM wptests_posts ORDER BY post_date ASC;",
			$this->wpdb->last_query
		);
	}

	public function testOrderDESCEscaped()
	{
		$qb = new Query($this->wpdb);
		$injected = "DESC; DROP wptests_users; --";
		$re = $qb->select()->from("wptests_posts")
			->orderBy('post_date', $injected)
			->get();

		$this->assertEquals(
			"SELECT * FROM wptests_posts ORDER BY post_date ASC;",
			$this->wpdb->last_query
		);
	}

	public function testOrderColumnNotEscaped()
	{
		$qb = new Query($this->wpdb);
		$injected = "post_date DESC; --";
		$re = $qb->select()->from("wptests_posts")
			->orderBy($injected, 'ASC')
			->get();

		$this->assertEquals(
			"SELECT * FROM wptests_posts ORDER BY post_date DESC; -- ASC;",
			$this->wpdb->last_query
		);
	}

	public function testSearchSingleColumn(){
		$qb = new Query($this->wpdb);
		$qb->select()
			->from("wptests_posts")
			->search("post_name", "some_thing with %")
			->get();

		$this->assertEquals(
			"SELECT * FROM wptests_posts WHERE (post_name LIKE '%some\\\_thing with \\\%%');",
			$this->wpdb->last_query
		);
	}

	public function testSearchTermInjection(){
		$qb = new Query($this->wpdb);

		$injected = "search term') OR 1=1; --";
		$re =$qb->select()
			->from("wptests_posts")
			->search("post_title", $injected)
			->get();

		$this->assertEquals(
			"SELECT * FROM wptests_posts WHERE (post_title LIKE '%search term\') OR 1=1; --%');",
			$this->wpdb->last_query
		);
	}

	public function testSearchMulitpleColumns(){
		$qb = new Query($this->wpdb);
		$qb->select()
			->from("wptests_posts")
			->search(["post_name", "post_content", "post_excerpt"], "some_thing with %")
			->get();

		$this->assertEquals(
			"SELECT * FROM wptests_posts WHERE (post_name LIKE '%some\\\_thing with \\\%%' OR post_content LIKE '%some\\\_thing with \\\%%' OR post_excerpt LIKE '%some\\\_thing with \\\%%');",
			$this->wpdb->last_query
		);
	}
}
