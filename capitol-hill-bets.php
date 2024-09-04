<?php
/**
 * Plugin Name: Capitol Hill Bets
 */

use CapitolHillBets\Bootstrap;

require __DIR__ . '/vendor/autoload.php';

// This is needed for activation and deactivation hooks
define('CHB_ENTRY_FILE_PATH', __FILE__);

Bootstrap::boot();
