<?php
use PHPUnit\Framework\TestCase;
use WPQueryBuilder\Query;
use WPQueryBuilder\JoinClause;

final class JoinTest extends TestCase {

	public function setUp(){
		parent::setUp();
		$this->wpdb = $this->createMock(\WPQueryBuilder\Wpdb::class);
	}

	public function testLeftJoin(){
		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT * FROM posts LEFT JOIN users AS u ON posts.author = u.ID;"
		);

		$qb = new Query($this->wpdb);
		$qb->select()->from("posts")
			->leftJoin('users AS u', 'posts.author', '=', 'u.ID' )
			->get();
	}

	public function testRightJoin(){
		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT * FROM posts RIGHT JOIN users AS u ON posts.author = u.ID;"
		);

		$qb = new Query($this->wpdb);
		$qb->select()->from("posts")
			->rightJoin('users AS u', 'posts.author', '=', 'u.ID' )
			->get();
	}

	public function testFullOuterJoin(){
		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT * FROM posts FULL JOIN users AS u ON posts.author = u.ID;"
		);

		$qb = new Query($this->wpdb);
		$qb->select()->from("posts")
			->fullJoin('users AS u', 'posts.author', '=', 'u.ID' )
			->get();
	}

	public function testInnerJoin(){
		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT * FROM posts INNER JOIN users AS u ON posts.author = u.ID;"
		);

		$qb = new Query($this->wpdb);
		$qb->select()->from("posts")
			->innerJoin('users AS u', 'posts.author', '=', 'u.ID' )
			->get();
	}

	public function testJoinWithOutAlias(){
		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT * FROM posts LEFT JOIN users ON posts.author = users.ID;"
		);

		$qb = new Query($this->wpdb);
		$qb->select()->from("posts")
			->leftJoin('users', 'posts.author', '=', 'users.ID' )
			->get();
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Invalid operator for ON. Allowed values are: =, !=, >, <, >=, <=, USING. You gave: 'users.ID'
	 */
	public function testJoinInvalidOperator(){
		$qb = new Query($this->wpdb);
		$qb->select()->from("posts")
			->leftJoin('users', 'posts.author', 'users.ID', null)
			->get();
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Invalid JOIN type. Allowed values are: LEFT, RIGHT, INNER, FULL. You gave: 'NOT A JOIN'
	 */
	public function testInvalidJoinType(){
		new JoinClause('NOT A JOIN', 'tablename');
	}

	public function testJoinWithWhere(){
		$this->wpdb->expects($this->once())->method('prepare')->with(
			"SELECT * FROM posts LEFT JOIN users AS u ON posts.author = u.ID WHERE status = %s ORDER BY date DESC LIMIT 80, 20;",
			['publish']
		);

		$qb = new Query($this->wpdb);
		$qb->select()->from("posts")
			->leftJoin('users AS u', 'posts.author', '=', 'u.ID' )
			->where('status', '=', 'publish')
			->orderBy( 'date', 'DESC')
			->limit(20)
			->offset(80)
			->get();
	}


	public function testJoinUsing(){
		$this->wpdb->expects($this->once())->method('get_results')->with(
			"SELECT * FROM table LEFT JOIN othertable AS o USING commonColumn;"
		);

		$qb = new Query($this->wpdb);
		$qb->select()->from("table")
			->leftJoin('othertable AS o', 'commonColumn')
			->get();
	}
}
