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

	function get_trades(
		array $filter = [],
		int $page = 1,
		int $per_page = 30,
	) {
		$start_row = ($page - 1) * $per_page;
		$allowed_columns = [
			'representative', 'bio_guide_id', 'report_date',
			'transaction_date', 'ticker', 'transaction',
			'range_min', 'range_max', 'house',
			'amount', 'party',
		];

		$filter_sql_pieces = ['1 = 1'];
		foreach ($filter as $column => $search_value) {
			if (!in_array($column, $allowed_columns)) {
				throw new \RuntimeException("Unknown columng $column");
			}

			$filter_sql_pieces[] = '`' . $column . '`="' . esc_sql($search_value) . '"';
		}

		return $this->wpdb->get_results("
			SELECT *
			FROM $this->table_name
			WHERE " . implode( ' AND ', $filter_sql_pieces ) . "
			ORDER BY transaction_date DESC
			LIMIT $start_row, $per_page
		");
	}

}
