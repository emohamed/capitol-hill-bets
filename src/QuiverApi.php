<?php
namespace CapitolHillBets;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class QuiverApi {
	/**
	 * The guzzle client
	 */
	private Client $client;

	function __construct()
	{
		if (!defined('QUIVER_API_AUTH_TOKEN')) {
			throw new \RuntimeException("Define QUIVER_API_AUTH_TOKEN in wp-config.php in order to use " . __CLASS__);
		}

		$this->client = new Client([
			'base_uri' => 'https://api.quiverquant.com/',
			'headers' => [
				'Authorization' => 'Bearer ' . QUIVER_API_AUTH_TOKEN,
			]
		]);
	}

	/**
	 * Normalize API response to associative array
	 */
	private function process_response(Response $response) {
		return json_decode((string) $response->getBody(), true);
	}

	/**
	 * Fetch all activity; this is somewhat slow and might be inefficient
	 */
	function get_all_trades() {
		return $this->process_response(
			$this->client->get('/beta/bulk/congresstrading')
		);
	}
}
