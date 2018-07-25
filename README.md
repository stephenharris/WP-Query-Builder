# WP Query builder

An expressive query builder for WordPress based on Laravel's Query Builder. Wraps the `$wpdb` global.

[![Build Status](https://travis-ci.org/stephenharris/WP-Query-Builder.svg?branch=master)](https://travis-ci.org/stephenharris/WP-Query-Builder)

## How to use in managed environments

If you wish to use this extension in a managed environment, simply install using `composer`:

```
composer require stephenharris/wp-query-builder
```

To use the Query builder

```php
include('vendor/autoload.php');

global $wpdb;
$qb = new WPQueryBuilder\Query($wpdb);
```

## How to use in distributed plug-ins

Since there is no way to manage dependencies between plug-ins, bundling this library
inside a plug-in will likely cause errors (possibly fatal errors) if used with
another plug-in that also includes it.

The safe way to bundle this library inside your plug-in is to use [Mozart](https://github.com/coenjacobs/mozart).
This copies it the codebase, but wraps it inside a custom namespace.

You'll need to handle autoloading of the library classes. They can be autoloaded
according to PSR-4 from your Mozart destination directory.

```php
global $wpdb;
$qb = new YourPluginNameSpace\WPQueryBuilder\Query($wpdb);
```

## Data Sanitisation

The purpose of this library is to provide an **expressive** and **safe*** way
to run queries against your WordPress database (typically involving custom tables).

To this end all **values** provided are escaped, but note that **column and table**
names are not yet escaped. In any case, even if they were you should be whitelisting
any allowed columns/tables: otherwise using user-input, or other untrusted data to
determine the column/table could allow an attacker to retrieve data they shouldn't
or generate a map of your database.


## Querying Results

### Retrieving all rows from a table

```php
$qb->select()->from("wp_users")->get();
//SELECT * FROM wp_users;
```

### Retrieve a single row

```php
$qb->select()->from("wp_users")->where("user_email", "=", "admin@example.com")->first();
//SELECT * FROM wp_users WHERE user_email = 'admin@example.com' LIMIT 1;
```

### Retrieve a single column
To retrieve the first column in the returned results:

```php
$usernames = $qb->select(["user_login", "user_email"])->from("wp_users")->getColumn();
```

To retrieve a particular column

```php
$emails = $qb->select(["user_login", "user_email"])->from("wp_users")->getColumn("user_email");
```

### To retrieve a value

```php
$email = $qb->select("user_email")->from("wp_users")->where("ID", "=", 123)->getScalar();
```

### Basic Where clauses

Adds a `WHERE` clause, matching records where the column value equals/not equals/
greater than/ less than / greater than or equals / less than equals (`=`/`, !=`,
`>`, `<`, `>=`, `<=`);

```php
$qb->select()
    ->from("wp_posts")
    ->where("post_status", "=", "publish")
		->andWhere("post_date", ">=", "2018-05-31 10:00:00")
		->get();
//SELECT * FROM wp_posts WHERE post_status='publish' AND post_date >= "2018-05-31 10:00:00";
```

To add an `OR` condition, use `orWhere($column, $operator, $value)`.


### Where column value IN

Adds a `WHERE` clause, matching records which have a column value in the array provided:

```php
$qb->select()->from("wp_users")->whereIn("ID", [1, 2, 3])->get();
//SELECT * FROM wp_users WHERE ID IN (1, 2, 3);
```

### Where column value IN

Adds a `WHERE` clause, matching records which have a column value in the array provided:

```php
$qb->select()->from("wp_users")->whereIn("ID", [1, 2, 3])->get();
//SELECT * FROM wp_users WHERE ID IN (1, 2, 3);
```


### Where column value BETWEEN

Adds a `WHERE` clause, matching records which have a column value between two specified values:

```php
$qb->select()->from("wp_posts")->whereBetween("post_date", '2018-05-01 00:00:00', '2018-05-31 23:59:59')->get();
//SELECT * FROM wp_posts BETWEEN '2018-05-01 00:00:00' AND '2018-05-31 23:59:59';
```

### Search for a field

Performs a `LIKE` comparison on one ore more fields fields.

```php
$qb->select()->from("wp_posts")->search("post_title", "foo")->get();
//SELECT * FROM wp_posts WHERE post_title='%foo%';
```

To search in multiple columns you can pass an array of columns:

```php
$qb->select()->from("wp_posts")->search(["post_title", "post_content", "post_excerpt"], "foo")->get();
//SELECT * FROM wp_posts WHERE post_title='%foo%' OR post_content='%foo%' OR post_excerpt='%foo%';
```

### Complex Where clauses

The `andWhere` (and its alias `where`), and `orWhere` all also accept a `WhereCause` instances.
This allows you to build more complex queries, such as nested `WHERE` conditions.

```php
$privateAndAuthor = new WPQueryBuilder\CompositeWhereClause();
$privateAndAuthor->andWhere(new WPQueryBuilder\BasicWhereClause('post_status', '==', 'private'))
$privateAndAuthor->andWhere(new WPQueryBuilder\BasicWhereClause('post_author', '==', 1));

$qb->select()
	->from("wp_posts")
	->where($privateAndAuthor)
	->orWhere("post_status", '=', "publish")
	->get();

//SELECT * FROM wp_posts WHERE (post_status = 'private' AND  post_author = 1) OR post_status = 'publish';

```


## Joining

There are four methods for joining:

```
$query->select(['wp_posts.*', 'u.user_nicename'])
 ->('wp_posts')
 ->leftJoin('wp_users as u', 'wp_posts.post_author', '=', 'u.ID');

// SELECT wp_posts.*, u.user_nicename FROM wp_posts LEFT JOIN wp_users as u ON wp_posts.post_author = u.ID;
```

Methods

- `leftJoin($table, $firstColumn, $operator, $secondColumn)`
- `rightJoin($table, $firstColumn, $operator, $secondColumn)`
- `innerJoin($table, $firstColumn, $operator, $secondColumn)`
- `fullJoin($table, $firstColumn, $operator, $secondColumn)`


## Insert records

The `Query::insert` method will insert a record with the given column / values
(given an array, where the column is the key). You must call `Query::table` with
the name of the table specified.

```php
$qb->table("wp_posts")
	->insert(
		"post_title" => "My Post Title",
		"post_content" => "My post content...",
		"post_status" => "publish",
	);

// INSERT INTO wp_posts (post_title, post_content, post_status) VALUES ('My Post Title', 'My post content...', 'publish');
```

## Delete records

The `Query::delete` method will execute a delete command for the table specified,
via the `Query::table` or `Query::from` command. The `delete` method must be
called **after** any where conditions, as otherwise you will delete the entire table.

```php
$qb->table("wp_posts")
	->where("ID", "=", 123)
	->delete();

// DELETE FROM wp_posts WHERE ID='123';
```


## Error handling

Calling a method incorrectly (e.g. calling `Query::get` without first calling
`Query::table`, or passing a field to `getColumn` that was not included in the
query Results) will results in a `LogicException` (either `BadMethodCallException`
or `InvalidArgumentException`).

If the SQL query fails then a `WPQueryBuilder\QueryException` is thrown.

```php
try {
	$qb->set([
			'ID' => 5,
			'post_title' => 'Updating a post'
   	])
	 	->where('ID', '=', 3)
	 	->update();
} catch (\LogicException $e) {

	// There is an error in your code

} catch (\WPQueryBuilder\QueryException $e) {

	// This could be data conflict

}
```
