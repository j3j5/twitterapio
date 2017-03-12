<?php

/**
 * This class allows to iterate through a Twitter Timeline from newest to oldest.
 *
 * You can use it following to get all tweets available (all the ones the API will return before
 * hitting the tweets limit) on a given timeline endpoint. Available as are:
 * 	-) GET statuses/user_timeline
 * 	-) GET statuses/home_timeline
 *
 * @package twitterapio
 * @author Julio Foulquié <jfoulquie@gmail.com>
 */

namespace j3j5;

class SearchIterator extends TwitterIterator
{
    /* Tweet ID from the oldest tweet retrieved */
    public $oldest_tweet_id;
    /* Tweet ID from the most recent tweet retrieved */
    public $latest_tweet_id;

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
        $response = $this->api->get($this->endpoint, $arguments);
        // Check rate limits.
        $response = $this->checkRateLimits($response, $arguments);

        if($this->response_array) {
            if(isset($response['statuses']) && $response['search_metadata']) {
                // Search results come this way
                if (isset($response['search_metadata']['next_results'])) {
                    // The URL comes with a '?' that must be removed
                    parse_str(str_replace('?', '', $response['search_metadata']['next_results']), $next_results);
                    if (isset($next_results['max_id'])) {
                        $this->max_id = $next_results['max_id'];
                    }
                } else {
                    $this->max_id = 0;
                }

                if(isset($response['search_metadata']['max_id_str'])) {
                    $this->since_id = $response['search_metadata']['max_id_str'];
                }

                $response = $response['statuses'];
            } else {
                $this->since_id = $this->max_id = 0;
                $latest_tweet = $first_tweet = FALSE;
            }
        }

        if(!empty($response)) {
            return $response;
        } else {
            return array();
        }
    }
}
