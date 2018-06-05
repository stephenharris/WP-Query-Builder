<?php

class MockWpdb implements WPQueryBuilder\Wpdb {

	private $invocations = [];

	public function query($query){
		$this->invocations[] = $query;
	}

	public function get_results($query){
		return $this->query($query);
	}

	public function get_var($query){
		return $this->query($query);
	}

	public function get_col($query){
		return $this->query($query);
	}

	public function insert($table,$data,$placeholders = []){

	}

	public function prepare( $query, $args ) {

		if ( is_null( $query ) ) {
			return;
		}

		$args = func_get_args();
		array_shift( $args );

		// If args were passed as an array (as in vsprintf), move them up.
		$passed_as_array = false;
		if ( is_array( $args[0] ) && count( $args ) == 1 ) {
			$passed_as_array = true;
			$args            = $args[0];
		}
		foreach ( $args as $arg ) {
			if ( ! is_scalar( $arg ) && ! is_null( $arg ) ) {
				throw new \Exception(sprintf('Unsupported value type (%s).', gettype($arg)));
			}
		}

		$allowed_format = '(?:[1-9][0-9]*[$])?[-+0-9]*(?: |0|\'.)?[-+0-9]*(?:\.[0-9]+)?';

		$query = str_replace( "'%s'", '%s', $query ); // Strip any existing single quotes.
		$query = str_replace( '"%s"', '%s', $query ); // Strip any existing double quotes.
		$query = preg_replace( '/(?<!%)%s/', "'%s'", $query ); // Quote the strings, avoiding escaped strings like %%s.
		$query = preg_replace( "/(?<!%)(%($allowed_format)?f)/", '%\\2F', $query ); // Force floats to be locale unaware.
		$query = preg_replace( "/%(?:%|$|(?!($allowed_format)?[sdF]))/", '%%\\1', $query ); // Escape any unescaped percents.

		// Count the number of valid placeholders in the query.
		$placeholders = preg_match_all( "/(^|[^%]|(%%)+)%($allowed_format)?[sdF]/", $query, $matches );
		if ( count( $args ) !== $placeholders ) {
			throw new \Exception(sprintf(
				'The query does not contain the correct number of placeholders (%1$d) for the number of arguments passed (%2$d).',
				$placeholders,
				count( $args )
			));
		}

		array_walk( $args, array( $this, 'escape_by_ref' ) );
		$query = @vsprintf( $query, $args );
		return $query;
	}

	public function escape_by_ref( &$string ) {
		if ( ! is_float( $string ) ) {
			//mysqli_real_escape_string
			//$string = $this->_real_escape( $string );
		}
	}


	public function getLastInvocation(){
		return end($this->invocations);
	}

}
