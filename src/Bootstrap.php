<?php
namespace CapitolHillBets;

class Bootstrap {
	public static function boot() {
		static::register_activation_and_deactivation_hooks();

		add_action('init', function () {
			static::register_wp_cli_commands();
		});
	}

	/**
	 * Create the custom table when the plugin is activated
	 */
	static function register_activation_and_deactivation_hooks() {
		register_activation_hook(CHB_ENTRY_FILE_PATH, function () {
			global $wpdb;
			$table_name = $wpdb->prefix . 'chb_trades';

			$sql = "CREATE TABLE $table_name (
				`id` INT NOT NULL auto_increment ,
				`representative` VARCHAR(255) NOT NULL,
				`bio_guide_id` VARCHAR(255) NOT NULL,
				`report_date` DATETIME NOT NULL,
				`transaction_date` DATETIME NOT NULL,
				`ticker` VARCHAR(5) NOT NULL,
				`transaction` VARCHAR(255) NOT NULL,
				`range_min` BIGINT NOT NULL,
				`range_max` BIGINT NOT NULL,
				`house` VARCHAR(255) NOT NULL,
				`amount` BIGINT NOT NULL,
				`party` VARCHAR(255) NOT NULL,
				`last_modified` DATETIME NOT NULL,
				`synced_at` DATETIME NOT NULL,
				PRIMARY KEY  (`id`),
				INDEX(representative),
				INDEX(ticker),
				INDEX(transaction_date)
			)";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		});

		register_deactivation_hook(CHB_ENTRY_FILE_PATH, function () {
			global $wpdb;
			$table_name = $wpdb->prefix . 'chb_trades';
			$wpdb->query( "DROP TABLE $table_name");
		});
	}

	static function register_wp_cli_commands() {
		if (!class_exists("\WP_CLI")) {
			return;
		}

		\WP_CLI::add_command('capital-hill-bets:sync-trades', function () {
			(new SyncTradesAction())->handle();
		});
	}

}
