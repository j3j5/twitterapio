<?php

/**
 * This class allows to iterate through a Twitter Timeline from newest to oldest.
 *
 * You can use it following to get all tweets available (all the ones the API will return before
 * hitting the tweets limit) on a given timeline endpoint. Available endpoints are:
 * 	-) GET statuses/user_timeline
 * 	-) GET statuses/home_timeline
 * 	-) GET search/tweets
 *
 * @package twitterapio
 * @author Julio Foulquié <jfoulquie@gmail.com>
 */

namespace j3j5;

class TimelineIterator extends TwitterIterator {

	/* Tweet ID from the oldest tweet retrieved */
	public $oldest_tweet_id;
	/* Tweet ID from the most recent tweet retrieved */
	public $latest_tweet_id;

	private $since_id = -1;
	private $max_id = -1;

	public function __construct($api, $endpoint, $arguments) {
		parent::__construct($api, $endpoint, $arguments);
	}

	public function rewind() {
		$this->arguments['since_id'] = $this->since_id;
		unset($this->arguments['max_id']);
	}

	public function current() {
		$arguments = $this->arguments;
		if(isset($arguments['since_id']) && $arguments['since_id'] <= 0) {
			unset($arguments['since_id']);
		}
		if(isset($arguments['max_id']) && $arguments['max_id'] <= 0) {
			unset($arguments['max_id']);
		}
		$resp = $this->api->get($this->endpoint, $arguments);
		// Check for rate limits
		if(isset($resp['errors'], $resp['tts']) && $this->sleep_on_rate_limit) {
			echo "sleeping for {$resp['tts']}...".PHP_EOL;
			sleep($resp['tts'] + 1);
			// Retry
			$resp = $this->api->get($this->endpoint, $arguments);
		}

		if(is_array($resp) && !isset($resp['errors'])) {
			$first_tweet = end($resp);
			$latest_tweet = reset($resp);
		} else {
			$this->since_id = $this->max_id = 0;
		}

		// Update since_id with the most recent tweet received
		if(is_array($latest_tweet) OR is_object($latest_tweet)) {
			$this->since_id = $this->response_array ? $latest_tweet['id'] : $latest_tweet->id;
			if(empty($this->latest_tweet_id)) {
				$this->latest_tweet_id = $this->since_id;
			}
			/* If this is not the first request, remove the latest tweet.
			 * We must do this because 'max_id' parameter is inclusive, so the latest tweet is
			 * the same as the first one was on the previous request. */
			if($this->max_id > 0) {
				reset($resp);
				unset($resp[key($resp)]);
			}
		}

		if (is_array($first_tweet) OR is_object($first_tweet)) {
			$this->max_id = $this->response_array ? $first_tweet['id'] : $first_tweet->id;
			$this->oldest_tweet_id = $this->max_id;
		} else {
			$this->max_id = 0;
		}


		if($this->since_id == $this->max_id) {
			$this->max_id = 0;
		}

		if(!empty($resp)) {
			return $resp;
		} else {
			return array();
		}
	}

	public function key() {
		return $this->max_id;
	}

	public function next() {
		unset($this->arguments['since_id']);
		$this->arguments['max_id'] = $this->max_id;
	}

	public function valid() {
		return ($this->max_id !== 0);
	}
}
