<?php

/**
 * Implement the Iterator interface to be able to go through a Twitter timeline using PHP's native functions
 *
 * @package twitterapio
 */

namespace j3j5;

/**
 * Iterator class to quickly iterate through an API
 */
abstract class TwitterIterator implements \Iterator
{
	protected $api;
	protected $endpoint;
	protected $arguments;

	protected $sleep_on_rate_limit;
	protected $response_array;

	public function __construct($api, $endpoint, $arguments) {
		$this->api = $api;
		$this->endpoint = $endpoint;
		$this->arguments = $arguments;

		include __DIR__ . '/config.php';
		$this->sleep_on_rate_limit = $general_config['sleep_on_rate_limit'];
		$this->response_array = isset($general_config['json_decode']) && $general_config['json_decode'] == 'array' ? TRUE : FALSE ;
	}
}
