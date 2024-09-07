<?php

namespace CapitolHillBets;

use wpdb;

/**
 * Perform a full sync for trades
 *
 *  1. Get data from the API
 *  2. Insert the data in the table
 *  3. Do a sanity check on the data
 *  4. Delete old data if new data seems OK
 */
class SyncTradesAction {
	/**
	 * ISO formatted datetime string for the start of the currently running sync process.
	 */
	private string $sync_start_time = '';

	/**
	 * MySQL table name for trades, e.g. wp_chb_trades
	 */
	private string $trades_table_name;

	/**
	 * How many trades to save per SQL query
	 */
	const BATCH_SIZE = 1000;

	/**
	 * Allows `$this->wpdb` instead of `global $wpdb`
	 */
	private wpdb $wpdb;

	/**
	 * How many rows do we have from old syncs
	 */
	private int $old_trades_count;

	/**
	 * Entrypoint
	 */
	function handle() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->trades_table_name = $wpdb->prefix . 'chb_trades';

		$this->old_trades_count = intval($this->wpdb->get_var("
			SELECT COUNT(*)
			FROM `{$this->trades_table_name}`
		"));

		$this->sync_start_time = date('Y-m-d H:i:s');
		$this->save_all_trades();

		// Do sanity checks on the old data only when there is some old data.
		// When the plugin is installed initially, the table would be empty,
		// so sanity checks and cleanup doesn't make sense.
		if ($this->old_trades_count > 0) {
			$this->do_sanity_check_for_new_data();
			$this->cleanup_old_data();
		}
	}

	private function save_all_trades() {
		$api = new QuiverApi();
		$trades = $api->get_all_trades();
		$batches_count = ceil(
			count($trades) / static::BATCH_SIZE
		);

		for($i = 1; $i <= $batches_count; $i++) {
			$batch = array_slice(
				$trades,
				($i - 1) * static::BATCH_SIZE,
				static::BATCH_SIZE
			);
			$this->save_batch($batch);
		}
	}

	/**
	 * Normalize numeric strings to numbers
	 */
	private function remove_non_digits($value): int {
		return intval(preg_replace('~\D~', '', $value));
	}

	/**
	 * Save a batch of trades to the database with single SQL query
	 */
	function save_batch(array $trades): void {
		$sql_parts = [];
		foreach ($trades as $trade) {
			[$range_min, $range_max] = explode(' - ', $trade['Range']);

			$fields = [
				esc_sql($trade['Representative']),
				esc_sql($trade['BioGuideID']),
				esc_sql($trade['ReportDate']),
				esc_sql($trade['TransactionDate']),
				esc_sql($trade['Ticker']),
				esc_sql($trade['Transaction']),
				$this->remove_non_digits($range_min),
				$this->remove_non_digits($range_max),
				esc_sql($trade['House']),
				$this->remove_non_digits($trade['Amount']),
				esc_sql($trade['Party']),

				esc_sql($trade['last_modified']), // weirdly, only this is in snake_case
				$this->sync_start_time,
			];
			$sql_parts[] = '("' . implode('", "', $fields) . '")';
		}
		$values = implode(", \n", $sql_parts);

		$sql = <<<SQL
			INSERT INTO {$this->trades_table_name} (
				representative,
				bio_guide_id,
				report_date,
				transaction_date,
				ticker,
				transaction,
				range_min,
				range_max,
				house,
				amount,
				party,
				last_modified,
				synced_at
			)
			VALUES
			$values
		SQL;
		$this->wpdb->query($sql);
	}

	/**
	 * Performs basic checks over the newly synced data and throw an exception if
	 * something seems fishy
	 */
	private function do_sanity_check_for_new_data() {
		$new_trades_count = $this->wpdb->get_var("
			SELECT COUNT(*)
			FROM {$this->trades_table_name}
			WHERE `synced_at`='{$this->sync_start_time}'
		");

		// get_var would return a `string "0"` here
		if (intval($new_trades_count) === 0) {
			throw new \RuntimeException("No new trades were added");
		}
	}

	/**
	 * Delete data from previous syncs
	 */
	private function cleanup_old_data() {
		$this->wpdb->get_var("
			DELETE FROM {$this->trades_table_name}
			WHERE `synced_at` <> '{$this->sync_start_time}'
		");
	}
}

