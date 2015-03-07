<?php

/**
 * TwitterApio
 *
 * A wrapper to make easier to use Twitter's API with tmhOAuth library.
 *
 * @author Julio Foulquié
 * @version 0.1.0
 *
 * 06 Mar 2015
 */

namespace j3j5;

use tmhOAuth;

class TwitterApio extends tmhOAuth {

	private static $debug = FALSE;

	protected $api_settings;
	protected $general_config;

	/**
	 * Create an instance of the TwitterApio class
	 *
	 * @param Array $settings The settings to start the OAuth library (consumers and tokens)
	 * @param Array $config The application settings to start the OAuth library
	 *
	 * @return void
	 */
	public function __construct($settings = array(), $config = array()) {
		include __DIR__ . '/config.php';
		$this->general_config = array_merge($general_config, $config);
		if(isset($this->general_config['debug'])) {
			self::$debug = $this->general_config['debug'];
			// Don't allow ouput if not running from the CLI
			if(PHP_SAPI != 'cli') {
				self::$debug = FALSE;
			}
		}
		parent::__construct(array_merge($twitter_settings, $settings));
	}

	/**
	 * Do a GET request to the Twitter API
	 *
	 * @param string $slug The slug to retrieve from Twitter's API
	 * @param string $parameters Optional parameters to set for the request
	 *
	 * @return Bool|Array A response array, or FALSE
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function get($slug, $parameters = array()) {
		$code = $this->request('GET', $this->url("{$this->general_config['api_version']}/$slug{$this->general_config['result_format']}"), $parameters);
		return $this->response($code);
	}

	/**
	 * Do a POST request to the Twitter API
	 *
	 * @param string $slug The slug to retrieve from Twitter's API
	 * @param string $parameters Optional parameters to set for the request
	 *
	 * @return Bool|Array A response array, or FALSE
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function post($slug, $parameters = array()) {
		$code = $this->request('POST', $this->url("{$this->general_config['api_version']}/$slug{$this->general_config['result_format']}"), $parameters);
		return $this->response($code);
	}

	/**
	 * Get all possible tweets from a timeline endpoint.
	 * This function returns an iterator so must be used with a function that implements the Iterator
	 * interface
	 *
	 * @param String $slug The timeline endpoint to be used on the iterator
	 * @param Array $arguments [Optional] Extra argument for the request
	 *
	 * @return TimelineIterator
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function get_timeline($slug, $arguments = array()) {
		return new TimelineIterator($this, $slug, $arguments);
	}

	/**
	 * Get all possible followers IDs for a given user
	 *
	 * @param Array $arguments Arguments for the request ('user_id' or 'screen_name' at least, but also 'count' or others are possible)
	 *
	 * @return FollowerIterator
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function get_followers($arguments) {
		$slug = 'followers/ids';
		return new FollowerIterator($this, $slug, $arguments);
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
		$json_output = $this->general_config['json_decode'] == 'array' ? TRUE : FALSE ;

		return json_decode($this->response['response'], $json_output);
	}

	/**
	 * Return the error response when the requested values (or the endpoint) do not exist.
	 * ex. a requested user does not exist or has been suspended, a tweet has been deleted...
	 *
	 * @return array
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	private function request_does_not_exist() {
		///TODO :Log error
		return array(
			'code'		=> $this->response['code'],
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
		///TODO :Log error
		return array(
			'code'		=> $this->response['code'],
			'errors'	=> $this->response['response'],
			'tts'		=> isset($this->response['headers']['x-rate-limit-reset']) ? // Time To Sleep
							( $this->response['headers']['x-rate-limit-reset'] - time() ):
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
		///TODO :Log error
		return array(
			'code'		=> $this->response['code'],
			'errors'	=> $this->response['response'],
			'tts'		=> 0,
		);
	}

	public static function debug($msg) {
		if(self::$debug) {
			echo date('Y-m-d H:i:s --> ') . $msg . PHP_EOL;
		} else {
			///TODO: Add some logger
		}
	}

}
