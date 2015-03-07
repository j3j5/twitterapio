<?php

/**
 * Documentation, License etc.
 *
 * @package twitterapio
 */

namespace j3j5;

class FollowerIterator extends TwitterIterator {

	private $cursor = "-1";
	private $prev_cursor = -1;
	private $next_cursor = 0;

	public function __construct($api, $endpoint, $arguments) {
		parent::__construct($api, $endpoint, $arguments);
	}

	public function rewind() {
		$this->cursor = $this->prev_cursor;
		$this->arguments['cursor'] = $this->cursor;
	}

	public function current() {
		$arguments = $this->arguments;

		$resp = $this->api->get($this->endpoint, $arguments);
		// Check for rate limits
		if(is_array($resp) && isset($resp['errors'], $resp['tts']) && $this->sleep_on_rate_limit) {
			if($resp['tts'] == 0) {
				TwitterApio::debug("An error occured: " . print_r($resp['errors'], TRUE));
				$this->next_cursor = $this->prev_cursor = 0;
				return array();
			} else {
				TwitterApio::debug("Sleeping for {$resp['tts']}s. ...");
				sleep($resp['tts'] + 1);
				// Retry
				$resp = $this->api->get($this->endpoint, $arguments);
			}
		}

		if($this->response_array) {
			// Set previous cursor
			if (isset($resp['previous_cursor'])) {
				$this->prev_cursor = $resp['previous_cursor'];
			} else {
				$this->prev_cursor = 0;
			}

			// Set next cursor
			if (isset($resp['next_cursor'])) {
				$this->next_cursor = $resp['next_cursor'];
			} else {
				$this->next_cursor = 0;
			}

			if(isset($resp['ids'])) {
				return $resp['ids'];
			} else {
				return array();
			}
		} else {
			// Set previous cursor
			if (isset($resp->previous_cursor)) {
				$this->prev_cursor = $resp->previous_cursor;
			} else {
				$this->prev_cursor = 0;
			}

			// Set next cursor
			if (isset($resp->next_cursor)) {
				$this->next_cursor = $resp->next_cursor;
			} else {
				$this->next_cursor = 0;
			}

			// Return the result
			if(isset($resp->ids)) {
				return $resp->ids;
			} else {
				return array();
			}
		}
	}

	public function key() {
		return $this->cursor;
	}

	public function next() {
		$this->cursor = $this->next_cursor;
		$this->arguments['cursor'] = $this->cursor;
	}

	public function valid() {
		return ($this->cursor != 0);
	}

}
