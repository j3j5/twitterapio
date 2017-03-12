<?php

/**
 * Implement the Iterator interface to be able to go through a Twitter timeline using PHP's native functions
 *
 * @package twitterapio
 */

namespace j3j5;

/**
 * Iterator class to quickly iterate through API results.
 *
 * @see https://dev.twitter.com/rest/public/timelines
 */
abstract class TwitterIterator implements \Iterator
{
	protected $api;
	protected $endpoint;
	protected $arguments;

	protected $sleep_on_rate_limit;
	protected $response_array;

    protected $since_id = -1;
    protected $max_id = -1;

	public function __construct($api, $endpoint, $arguments) {
		$this->api = $api;
		$this->endpoint = $endpoint;
		$this->arguments = $arguments;

		$general_config = $this->api->get_config();
		$this->sleep_on_rate_limit = $general_config['sleep_on_rate_limit'];
		// Overwrite the setting if running on webserver
		if(PHP_SAPI != 'cli') {
			$this->sleep_on_rate_limit = FALSE;
		}
		$this->response_array = isset($general_config['json_decode']) && $general_config['json_decode'] == 'array' ? TRUE : FALSE ;
	}

	/**
     * Check whether the response from Twitter's API is rate limiting the calls or not.
     *
     * @param array $response Twitter's respose
     *
     * @
     */
	public function checkRateLimits(array $response, array $original_arguments)
	{
        // Check for rate limits
        if(is_array($response) && isset($response['errors'], $response['tts']) ) {
            if($this->sleep_on_rate_limit) {
                if($response['tts'] == 0) {
                    $this->api->debug("An error occured: " . print_r($response['errors'], TRUE));
                    $this->max_id = $this->since_id = 0;
                    return array();
                } else {
                    $this->api->debug("Sleeping for {$response['tts']}s. ...");
                    sleep($response['tts'] + 1);
                    // Retry
                    return $this->api->get($this->endpoint, $original_arguments);
                }
            } else {
                $this->max_id = $this->since_id = 0;
                return array();
            }
        }
        return $response;
    }

    /**
     * Return the key of the current element.
     */
	public function key() {
		return $this->max_id;
	}

	/**
     * Move forward to next element.
     */
	public function next() {
		unset($this->arguments['since_id']);
		$this->arguments['max_id'] = $this->max_id;
	}

	/**
     * Checks if current position is valid.
     */
	public function valid() {
		return ($this->max_id !== 0);
	}

	/**
     * Return the current element.
     */
	abstract public function current();

}
