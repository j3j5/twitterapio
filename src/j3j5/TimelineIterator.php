<?php

/**
 * This class allows to iterate through a Twitter Timeline from newest to oldest.
 *
 * You can use it following to get all tweets available (all the ones the API will return before
 * hitting the tweets limit) on a given timeline endpoint. Available endpoints are:
 * 	-) GET statuses/user_timeline
 * 	-) GET statuses/home_timeline
 *
 * @package twitterapio
 * @author Julio Foulquié <jfoulquie@gmail.com>
 */

namespace j3j5;

class TimelineIterator extends TwitterIterator
{

    /* Tweet ID from the oldest tweet retrieved */
    public $oldest_tweet_id;
    /* Tweet ID from the most recent tweet retrieved */
    public $latest_tweet_id;

    public function current()
    {
        $arguments = $this->arguments;
        if (isset($arguments['since_id']) && $arguments['since_id'] <= 0) {
            unset($arguments['since_id']);
        }
        if (isset($arguments['max_id']) && $arguments['max_id'] <= 0) {
            unset($arguments['max_id']);
        }
        $response = $this->api->get($this->endpoint, $arguments);

        // Check rate limits.
        $response = $this->checkRateLimits($response, $arguments);

        if ($this->response_array) {
            if (is_array($response) && !isset($response['errors']) && !isset($response['statuses'])) {
                $first_tweet = end($response);
                $latest_tweet = reset($response);
            } else {
                $this->since_id = $this->max_id = 0;
                $latest_tweet = $first_tweet = false;
            }
        } else {
            if (is_object($response) && !isset($response->statuses)) {
                $first_tweet = end($response);
                $latest_tweet = reset($response);
            } else {
                $this->since_id = $this->max_id = 0;
                $latest_tweet = $first_tweet = false;
            }
        }

        // Update since_id with the most recent tweet received
        if (is_array($latest_tweet) or is_object($latest_tweet)) {
            $this->since_id = $this->response_array ? $latest_tweet['id'] : $latest_tweet->id;
            if (empty($this->latest_tweet_id)) {
                $this->latest_tweet_id = $this->since_id;
            }
            /**
             * If this is not the first request, remove the latest tweet.
             * We must do this because 'max_id' parameter is inclusive, so the latest tweet is
             * the same as the first one was on the previous request.
             */
            if ($this->max_id > 0) {
                reset($response);
                unset($response[key($response)]);
            }
        }

        if (is_array($first_tweet) or is_object($first_tweet)) {
            $this->max_id = $this->response_array ? $first_tweet['id'] : $first_tweet->id;
            $this->oldest_tweet_id = $this->max_id;
        } else {
            $this->max_id = 0;
        }

        if ($this->since_id == $this->max_id) {
            $this->max_id = 0;
        }

        if (!empty($response)) {
            return $response;
        } else {
            return [];
        }
    }
}
