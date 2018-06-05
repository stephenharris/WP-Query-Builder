<?php
namespace WPQueryBuilder;

interface Wpdb{

	public function query($query);
	public function get_results($query);
	public function get_var($query);
	public function insert($table,$data,$placeholders = []);
	public function prepare($query, $bindings);

}
