<?php

namespace CapitolHillBets;

/**
 * Simple data fetching helper
 */
class DataApi {
	private $wpdb;
	private $table_name;

	function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
		$this->table_name = $wpdb->prefix . 'chb_trades';
	}

	function get_trades(int $page = 1, int $per_page = 30) {
		$start = ($page - 1) * $per_page;
		return $this->wpdb->get_results("SELECT * FROM $this->table_name order by id DESC LIMIT $start, $per_page");
	}
}
