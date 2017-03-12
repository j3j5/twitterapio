<?php

/**
 * This class allows to iterate through a set of Twitter followers.
 *
 * You can use it for endpoints which return collections that use 'cursoring'
 * for pagination (as opposed to timelines).
 * Some valid endpoints are:
 * -) /followers/ids
 * -) /friends/ids
 * -) /lists/memberships
 * -) /lists/members
 *
 * @package twitterapio
 * @author Julio Foulquié <jfoulquie@gmail.com>
 *
 * @see https://dev.twitter.com/overview/api/cursoring
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

		$response = $this->api->get($this->endpoint, $arguments);
        // Check rate limits.
        $response = $this->checkRateLimits($response, $arguments);

		if($this->response_array) {
			// Set previous cursor
			if (isset($response['previous_cursor'])) {
				$this->prev_cursor = $response['previous_cursor'];
			} else {
				$this->prev_cursor = 0;
			}

			// Set next cursor
			if (isset($response['next_cursor'])) {
				$this->next_cursor = $response['next_cursor'];
			} else {
				$this->next_cursor = 0;
			}

			if(isset($response['ids'])) {
				return $response['ids'];
			} else {
				return array();
			}
		} else {
			// Set previous cursor
			if (isset($response->previous_cursor)) {
				$this->prev_cursor = $response->previous_cursor;
			} else {
				$this->prev_cursor = 0;
			}

			// Set next cursor
			if (isset($response->next_cursor)) {
				$this->next_cursor = $response->next_cursor;
			} else {
				$this->next_cursor = 0;
			}

			// Return the result
			if(isset($response->ids)) {
				return $response->ids;
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
