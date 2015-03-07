<?php
/**
 * Documentation, License etc.
 *
 * @package twitterapio
 */

namespace j3j5;

use tmhOAuth;

class TwitterApio extends tmhOAuth {

	protected $api_settings;
	protected $config;

	/**
	 * Create an instance of the TwitterApi class
	 *
	 * @param Array $settings The application settings to start the OAuth library
	 * @return void
	 */
	public function __construct($settings = array(), $config = array()) {
		require 'config.php';
		$this->config = array_merge($general_config, $config);
		parent::__construct(array_merge($twitter_settings, $settings));
	}

	/**
	 * Do a request to the Twitter API
	 *
	 * @param string $slug The slug to retrieve from Twitter's API
	 * @param string $parameters Optional parameters to set for the request
	 *
	 * @return Bool|Array A response array, or FALSE
	 */
	public function get($slug, $parameters = array()) {
		$code = $this->request('GET', $this->url("{$this->config['api_version']}/$slug{$this->config['result_format']}"), $parameters);
		return $this->response($code);
	}

	/**
	 * Make a POST request to the Twitter API
	 *
	 * @param string $url The url to request from the Twitter API
	 * @param string $parameters Optional parameters to set for the request
	 * @return mixed A response array, or null
	 */
	public function post($url, $parameters = array()) {
		return $this->request('POST', $this->url($this->api_version . '/' . $url), $parameters);
	}

	/**
	 * Make a DELETE request to the Twitter API
	 *
	 * @param string $url The url to request from the Twitter API
	 * @param string $parameters Optional parameters to set for the request
	 * @return mixed A response array, or null
	 */
	public function delete($url, $parameters = array()) {
		return $this->request('DELETE', $this->url($this->api_version . '/' . $url), $parameters);
	}

	public function cursorize_timeline($endpoint, $arguments = array()) {
		return new FastTwitterAPI_Iterator_TimelineCursor($this, $endpoint, $arguments);
	}

	/**
	 * Parse the response depending on the provided code
	 *
	 * @param p1
	 *
	 * @return return
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	private function response($code) {
		switch($code) {
			// Successful
			case 200:
			case 304:
				return $this->success();
			// Non existent user
			case 403: // Suspended:
			case 404: // Removed
				return $this->request_does_not_exist();
			// Rate limit
			case 429:
				return $this->rate_limit();
			// OAuth Credentials are NOT VALID or others
			case 400:
			default:
				return $this->general_error();
		}
	}

	/**
	 * Process a successful response and return the parsed output
	 *
	 * @return Object|Array|Bool Return the decoded output depending on the values of the config object,
	 * 							FALSE on error.
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	private function success() {

		if(!isset($this->response['response']) OR !is_string($this->response['response'])) {
			///TODO: Log Error
			return FALSE;
		}
		$json_output = $this->config['json_decode'] == 'array' ? TRUE : FALSE ;

		return json_decode($this->response['response'], $json_output);
	}

	/**
	 * Return the error response when the requested values do not exist.
	 * ex. a requested user does not exist or has been suspended, a tweet has been deleted...
	 *
	 * @return array
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	private function request_does_not_exist() {
		return array(
			'code'		=> $code,
			'errors'	=> $this->response['response'],
			'tts'		=> 0,
		);
	}

	/**
	 * Return the error response when a rate limit has been reached on a given endpoint.
	 *
	 * @return Array Includes the 'tts' in case this is run from a CLI and you want to
	 * 				sleep the process till the rate limit is gone.
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	private function rate_limit() {
		return array(
			'code'		=> $code,
			'errors'	=> $this->response['response'],
			'tts'		=> isset($this->response['headers']['x-rate-limit-reset']) ? // Time To Sleep
							$this->response['headers']['x-rate-limit-reset'] :
							FALSE
		);
	}

	/**
	 * Return the error response for general errors from the API.
	 *
	 * @return String|Bool The response string or FALSE
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	private function general_error() {
		return isset($this->response['response']) ? $this->response['response'] : FALSE
	}

}

/**
 * Iterator class to quickly iterate through an API
 */
abstract class FastTwitterAPI implements \Iterator
{
	protected $api;
	protected $endpoint;
	protected $arguments;

	public function __construct($api, $endpoint, $arguments) {
		$this->api = $api;
		$this->endpoint = $endpoint;
		$this->arguments = $arguments;
	}
}

class FastTwitterAPI_Iterator_TimelineCursor extends FastTwitterAPI {
	private $since_id = -1;
	private $latest_tweet_id;
	private $max_id = -1;
	private $first_tweet_id;
	private $use_since_id = FALSE;

	function __construct($api, $endpoint, $arguments) {
		$this->use_since_id = TRUE;
		if(isset($arguments['use_since_id'])) {
			$this->use_since_id = (bool)$arguments['use_since_id'];
		}
		parent::__construct($api, $endpoint, $arguments);

	}

	function rewind() {
		$this->since_id = $this->max_id = -1;
	}

	function current() {
		$arguments = $this->arguments;
		if($this->max_id > 0) {
			$arguments['max_id'] = $this->max_id;
		}

		if($this->since_id > 0 && $this->use_since_id) {
			$arguments['since_id'] = $this->since_id;
		}

		$i = 0;
		do {
			$resp = $this->api->get($this->endpoint, $arguments);
		} while (is_array($resp)  && ++$i <= 3); // Retry 3 times if the response isn't present

		if(is_array($resp)) {
			$first_tweet = end($resp);
			$latest_tweet = reset($resp);
		} else {
			$this->since_id = $this->max_id = 0;
		}

		if (!empty($first_tweet)) {
			$this->max_id = $first_tweet['id'] -1;
		} else {
			$this->max_id = 0;
		}

		if(!empty($latest_tweet)) {
			$this->since_id = $latest_tweet['id'];
		}

		if($this->since_id == $this->max_id) {
			// This means, on the last request, the oldest and the latest were the same tweet, so stop here
			$this->max_id = 0;
		}
		if(is_array($resp)) {
			return $resp;
		} else {
			return FALSE;
		}
	}

	function key() {
		return $this->max_id;
	}

	function next() {
		// For the next, max_id should already be set
		return;
	}

	function valid() {
		return ($this->max_id !== 0);
	}
}
