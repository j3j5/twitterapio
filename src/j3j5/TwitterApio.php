<?php

/**
    TwitterApio - A wrapper to make easier to use Twitter's API with tmhOAuth library.
    Copyright (C) 2015  Julio Foulquie <jfoulquie@gmail.com>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published
    by the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace j3j5;

use tmhOAuth;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class TwitterApio extends tmhOAuth {

	protected $api_settings;
	protected $general_config;
	private $log;

	/**
	 * Max value for count parameters on different endpoints.
	 */
	protected $max_counts = array(
		'statuses/user_timeline'	=> 200,
		'followers/ids'				=> 5000,
		'search/tweets'				=> 100,
		'users/lookup'				=> 100,
	);

	/**
	 * Create an instance of the TwitterApio class.
	 *
	 * @param Array $settings The settings to start the OAuth library (consumers and tokens)
	 * @param Array $config The application settings to start the OAuth library
	 *
	 * @return void
     *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function __construct($settings = array(), $config = array()) {
		include __DIR__ . '/config.php';
		$this->general_config = array_merge($general_config, $twitter_settings);
		$this->general_config = array_merge($this->general_config, $config);

		$this->log = new Logger('twitter-apio');
		if(PHP_SAPI == 'cli') {
			$this->log->pushHandler(new StreamHandler("php://stdout", Logger::DEBUG));
		} else {
			$this->log->pushHandler(new StreamHandler(dirname(__DIR__) . '/data/logs/twitterapio.log', Logger::DEBUG));
		}

		parent::__construct(array_merge($twitter_settings, $settings));
	}

	/**
	 * Set new config values for the OAuth class like different tokens.
	 *
	 * @param Array $config An array containing the values that should be overwritten.
	 *
	 * @return void
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function reconfigure($config = array()) {
		// The consumer key and secret must always be included when reconfiguring
		$config = array_merge($this->general_config, $config);
		parent::reconfigure($config);
	}

	/**
	 * Get the current config array from the class.
	 *
	 * @return Array
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function get_config() {
		return $this->general_config;
	}

	/**
	 * Do a GET request to the Twitter API.
	 *
	 * @param string $slug The slug to retrieve from Twitter's API
	 * @param string $parameters Optional parameters to set for the request
	 *
	 * @return Bool|Array A response array, or FALSE
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function get($slug, $parameters = array()) {
		$code = $this->request('GET', $this->url("{$this->general_config['api_version']}/$slug"), $parameters);
		return $this->response($code);
	}

	/**
	 * Do a POST request to the Twitter API.
	 *
	 * @param string $slug The slug to retrieve from Twitter's API
	 * @param string $parameters Optional parameters to set for the request
	 *
	 * @return Bool|Array A response array, or FALSE
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function post($slug, $parameters = array()) {
		$code = $this->request('POST', $this->url("{$this->general_config['api_version']}/$slug"), $parameters);
		return $this->response($code);
	}

	/**
	 * Owerwrite the parent's request function to clean up some parameters before sending.
	 *
	 * @param string $method the HTTP method being used. e.g. POST, GET, HEAD etc
	 * @param string $url the request URL without query string parameters
	 * @param array $params the request parameters as an array of key=value pairs. Default empty array
	 * @param string $useauth whether to use authentication when making the request. Default true
	 * @param string $multipart whether this request contains multipart data. Default false
	 * @param array $headers any custom headers to send with the request. Default empty array
	 *
	 * @return int the http response code for the request. 0 is returned if a connection could not be made
	 *tu
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function request($method, $url, $params=array(), $useauth=true, $multipart=false, $headers=array()) {
		// Check parameters and clean
		foreach($params AS $param => $val) {
			switch($param) {
				case 'user_id':
					if(!is_numeric($val)) {
						throw new \Exception("user_id must be numeric $val");
					}
					break;
				case 'count':
					// Don't allow count bigger than the max allowed by twitter'
					if(isset($this->max_counts[$url]) && $val > $this->max_counts) {
						$params[$param] = $this->max_counts[$url];
					}
					break;
				default:
					break;
			}
		}

		$this->log->addInfo("$method request to $url with params " . print_r($params, TRUE));
		return parent::request($method, $url, $params, $useauth, $multipart, $headers);
	}


	/**
	 * Get a request_token from Twitter.
	 *
	 * @param String $oauth_callback [Optional] The callback provided for Twitter's API.
	 * The user will be redirected there after authorizing your app oAn Twitter.
	 *
	 * @return Array|Bool a key/value array containing oauth_token and oauth_token_secret
	 * 						in case of success
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function get_request_token($oauth_callback = NULL) {
		$parameters = array();
		if (!empty($oauth_callback)) {
			$parameters['oauth_callback'] = $oauth_callback;
		}
		$code = $this->request('POST', $this->url("oauth/request_token", ''), $parameters);
		if(isset($this->response['code']) && $this->response['code'] == 200 && !empty($this->response['response'])) {
			$get_parameters = $this->response['response'];
			$token = array();
			parse_str($get_parameters, $token);
		}
		// Return the token if it was properly retrieved
		if( isset($token['oauth_token'], $token['oauth_token_secret']) ){
			return $token;
		} else {
			throw new \Exception("No request token found.");
		}
	}
	/**
	 * Get an access token for a logged in user.
	 *
	 * @param String|Bool $oauth_verifier
	 *
	 * @return Array|Bool key/value array containing the token in case of success
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function get_access_token($oauth_verifier = FALSE) {
		$parameters = array();
		if (!empty($oauth_verifier)) {
			$parameters['oauth_verifier'] = $oauth_verifier;
		}
		$code = $this->request('POST', $this->url("oauth/access_token", ''), $parameters);
		if(isset($this->response['code']) && $this->response['code'] == 200 && !empty($this->response['response'])) {
			$get_parameters = $this->response['response'];
			$token = array();
			parse_str($get_parameters, $token);
			// Reconfigure the tmhOAuth class with the new tokens
			$this->reconfigure(array('token' => $token['oauth_token'], 'secret' => $token['oauth_token_secret']));
			return $token;
		}
        throw new \Exception("No access token found.");
	}
	/**
	 * Get the authorize URL.
	 *
	 * @returns string
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function get_authorize_url($token, $sign_in_with_twitter = TRUE, $force_login = FALSE) {
		if (is_array($token)) {
			$token = $token['oauth_token'];
		}
		if ($force_login) {
			return "https://api.twitter.com/oauth/authenticate?oauth_token={$token}&force_login=true";
		} else if (empty($sign_in_with_twitter)) {
			return "https://api.twitter.com/oauth/authorize?oauth_token={$token}";
		} else {
			return "https://api.twitter.com/oauth/authenticate?oauth_token={$token}";
		}
	}

	/**
	 * Block a given user.
	 *
	 * @param Int $twitter_id
	 * @param Array $parameters [optional] Any extra parameters
	 *
	 * @return return
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function block($twitter_id, $parameters = array()) {
		if(!is_numeric($twitter_id)) {
			return FALSE;
		}
		$slug = 'blocks/create';
		$default_parameters = array(
			'user_id' => $twitter_id,
			'include_entities'	=> FALSE,
			'skip_status'	=> FALSE,
		);
		$parameters = array_merge($default_parameters, $parameters);
		return $this->post($slug, $parameters);
	}

	/**
	 * Unblock a given user.
	 *
	 * @param Int $twitter_id
	 * @param Array $parameters [optional] Any extra parameters
	 *
	 * @return return
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function unblock($twitter_id, $parameters = array()) {
		if(!is_numeric($twitter_id)) {
			return FALSE;
		}
		$slug = 'blocks/destroy';
		$default_parameters = array(
			'user_id' => $twitter_id,
			'include_entities'	=> FALSE,
			'skip_status'	=> FALSE,
		);
		$parameters = array_merge($default_parameters, $parameters);
		return $this->post($slug, $parameters);
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
     * Get all results from tweets for the search API returns for the given query.
     *
     * @param string $query
     * @param array $arguments
     */
	public function search(string $query, array $arguments = null)
	{
        $args = ['q' => $query];
        if (is_array($arguments)) {
            $args = array_merge($arguments, $args);
        }
        return new SearchIterator($this, 'search/tweets', $args);
    }

	/**
	 * Get all possible followers IDs for a given user.
	 * This function returns an iterator so must be used with a function that implements the Iterator
	 * interface
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
	 * Get all possible followers IDs for a given user
	 * This function returns an iterator so must be used with a function that implements the Iterator
	 * interface
	 *
	 * @param Array $arguments Arguments for the request ('user_id' or 'screen_name' at least, but also 'count' or others are possible)
	 *
	 * @return FollowerIterator
	 *
	 * @author Julio Foulquié <jfoulquie@gmail.com>
	 */
	public function get_friends($arguments) {
		$slug = 'friends/ids';
		return new FollowerIterator($this, $slug, $arguments);
	}

	public function get_user($arguments) {
		return $this->get('users/show', $arguments);
	}

	public function get_users($arguments) {
		return $this->get('users/lookup', $arguments);
	}

	/**
	 * Parse the response depending on the provided code
	 *
	 * @param Int $code Response code.
	 *
	 * @return Mixed
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
			case 403: // Forbidden:
				return $this->forbidden();
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
		$this->log->addDebug("Request succeeded!!");
		if(!isset($this->response['response']) OR !is_string($this->response['response'])) {
			$this->log->addError("There is no response! PANIC!!");
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
	private function forbidden() {
		$this->log->addError("Error {$this->response['code']}: {$this->response['response']}");
		return array(
			'code'		=> $this->response['code'],
			'errors'	=> $this->response['response'],
			'tts'		=> 0,
		);
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
		$this->log->addError("Error {$this->response['code']}: {$this->response['response']}");
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
		$this->log->addError("RATE LIMIT!");
		$this->log->addError("Error {$this->response['code']}: {$this->response['response']}");
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
		$this->log->addError("API ERROR!");
		$this->log->addError(print_r($this->response, TRUE));
		return array(
			'code'		=> $this->response['code'],
			'errors'	=> $this->response['response'],
			'tts'		=> 0,
		);
	}

    public function debug($string)
    {
        $this->log->addDebug($string);
    }

}
