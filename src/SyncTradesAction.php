<?php

namespace CapitolHillBets;

class SyncTradesAction {

	// How many trades to save per SQL query
	const BATCH_SIZE = 1000;

	function handle() {
		global $wpdb;
		$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}chb_trades");

		$api = new QuiverApi();
		$trades = $api->get_all_trades();
		$batches_count = ceil(count($trades) / static::BATCH_SIZE);

		for($i = 1; $i <= $batches_count; $i++) {
			$batch = array_slice(
				$trades,
				($i - 1) * static::BATCH_SIZE,
				static::BATCH_SIZE
			);
			$this->save_batch($batch);
		}
	}

	private function remove_non_digits($value) {
		return intval(preg_replace('~\D~', '', $value));
	}
	function save_batch(array $trades) {
		global $wpdb;

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
			];
			$sql_parts[] = '("' . implode('", "', $fields) . '")';
		}
		$values = implode(", \n", $sql_parts);

		$sql = <<<SQL
			INSERT INTO {$wpdb->prefix}chb_trades
			(representative, bio_guide_id, report_date, transaction_date, ticker, transaction, range_min, range_max, house, amount, party, last_modified)
			VALUES
			$values
		SQL;
		$wpdb->query($sql);
	}

}

