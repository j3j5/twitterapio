<?php

/**
    TwitterApio - A wrapper to make it easier to use Twitter's API with tmhOAuth library.
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

class TwitterApio extends tmhOAuth
{
    protected $api_settings;
    protected $general_config;

    /**
     * Max value for count parameters on different endpoints.
     */
    protected $max_counts = [
        'statuses/user_timeline'    => 200,
        'followers/ids'             => 5000,
        'search/tweets'             => 100,
        'users/lookup'              => 100,
    ];

    /**
     * Create an instance of the TwitterApio class.
     *
     * @param array $settings The settings to start the OAuth library (consumers and tokens)
     * @param array $config The application settings to start the OAuth library
     *
     * @return void
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function __construct(array $settings = [], array $config = [])
    {
        include dirname(__DIR__) . '/config/config.php';
        $this->general_config = array_merge($general_config, $twitter_settings);    // Original twitter settings from config file
        $this->general_config = array_merge($this->general_config, $settings);      // Supplied Oauth settings
        $this->general_config = array_merge($this->general_config, $config);        // Supplied app settings.
        parent::__construct(array_merge($twitter_settings, $settings));
    }

    /**
     * Set new config values for the OAuth class like different tokens.
     *
     * @param array $config An array containing the values that should be overwritten.
     *
     * @return void
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function reconfigure($config = [])
    {
        // The consumer key and secret must always be included when reconfiguring
        $config = array_merge($this->general_config, $config);
        parent::reconfigure($config);
    }

    /**
     * Get the current config array from the class.
     *
     * @return array
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function getConfig() : array
    {
        return $this->general_config;
    }

    /**
     * Do a GET request to the Twitter API.
     *
     * @param string $slug The slug to retrieve from Twitter's API
     * @param array $parameters Optional parameters to set for the request
     *
     * @return bool|array A response array, or false
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function get(string $slug, array $parameters = [])
    {
        $code = $this->request('GET', $this->url("{$this->general_config['api_version']}/$slug"), $parameters);
        return $this->response($code);
    }

    /**
     * Do a POST request to the Twitter API.
     *
     * @param string $slug The slug to retrieve from Twitter's API
     * @param array $parameters Optional parameters to set for the request
     *
     * @return bool|array A response array, or false
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function post(string $slug, array $parameters = [])
    {
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
    public function request($method, $url, $params = [], $useauth = true, $multipart = false, $headers = [])
    {
        // Check parameters and clean
        foreach ($params as $param => $val) {
            switch ($param) {
                case 'user_id':
                    if (!is_numeric($val)) {
                        throw new \Exception("user_id must be numeric $val");
                    }
                    break;
                case 'count':
                    // Don't allow count bigger than the max allowed by twitter'
                    if (isset($this->max_counts[$url]) && $val > $this->max_counts) {
                        $params[$param] = $this->max_counts[$url];
                    }
                    break;
                default:
                    break;
            }
        }

        return parent::request($method, $url, $params, $useauth, $multipart, $headers);
    }

    /**
     * Get a request_token from Twitter.
     *
     * @param string $oauth_callback [Optional] The callback provided for Twitter's API.
     * The user will be redirected there after authorizing your app on Twitter.
     *
     * @return array|bool a key/value array containing oauth_token and oauth_token_secret
     * 						in case of success
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function getRequestToken(string $oauth_callback = null)
    {
        $parameters = [];
        if (!empty($oauth_callback)) {
            $parameters['oauth_callback'] = $oauth_callback;
        }
        $code = $this->request('POST', $this->url('oauth/request_token', ''), $parameters);
        if (isset($this->response['code']) && $this->response['code'] == 200 && !empty($this->response['response'])) {
            $get_parameters = $this->response['response'];
            $token = [];
            parse_str($get_parameters, $token);
        }
        // Return the token if it was properly retrieved
        if (isset($token['oauth_token'], $token['oauth_token_secret'])) {
            return $token;
        } else {
            throw new \Exception('No request token found.');
        }
    }
    /**
     * Get an access token for a logged in user.
     *
     * @param string $oauth_verifier
     *
     * @return array|bool key/value array containing the token in case of success
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function getAccessToken(string $oauth_verifier = null)
    {
        $parameters = [];
        if (!empty($oauth_verifier)) {
            $parameters['oauth_verifier'] = $oauth_verifier;
        }
        $code = $this->request('POST', $this->url('oauth/access_token', ''), $parameters);
        if (isset($this->response['code']) && $this->response['code'] == 200 && !empty($this->response['response'])) {
            $get_parameters = $this->response['response'];
            $token = [];
            parse_str($get_parameters, $token);
            // Reconfigure the tmhOAuth class with the new tokens
            $this->reconfigure([
                'token' => $token['oauth_token'],
                'secret' => $token['oauth_token_secret']]
            );
            return $token;
        }
        throw new \Exception('No access token found.');
    }
    /**
     * Get the authorize URL.
     *
     * @return string
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function getAuthorizeUrl($token, bool $sign_in_with_twitter = true, bool $force_login = false)
    {
        if (is_array($token)) {
            $token = $token['oauth_token'];
        }
        if ($force_login) {
            return "https://api.twitter.com/oauth/authenticate?oauth_token={$token}&force_login=true";
        } elseif (empty($sign_in_with_twitter)) {
            return "https://api.twitter.com/oauth/authorize?oauth_token={$token}";
        } else {
            return "https://api.twitter.com/oauth/authenticate?oauth_token={$token}";
        }
    }

    /**
     * Block a given user.
     *
     * @param int|string $twitter_id
     * @param array $parameters [optional] Any extra parameters
     *
     * @return array|bool
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function block($twitter_id, array $parameters = [])
    {
        if (!is_numeric($twitter_id)) {
            return false;
        }
        $slug = 'blocks/create';
        $default_parameters = [
            'user_id' => $twitter_id,
            'include_entities'    => false,
            'skip_status'    => false,
        ];
        $parameters = array_merge($default_parameters, $parameters);
        return $this->post($slug, $parameters);
    }

    /**
     * Unblock a given user.
     *
     * @param int|string $twitter_id
     * @param array $parameters [optional] Any extra parameters
     *
     * @return array|bool
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function unblock($twitter_id, array $parameters = [])
    {
        if (!is_numeric($twitter_id)) {
            return false;
        }
        $slug = 'blocks/destroy';
        $default_parameters = [
            'user_id' => $twitter_id,
            'include_entities'    => false,
            'skip_status'    => false,
        ];
        $parameters = array_merge($default_parameters, $parameters);
        return $this->post($slug, $parameters);
    }

    /**
     * Get all possible tweets from a timeline endpoint.
     * This function returns an iterator so must be used with a function that implements the Iterator
     * interface
     *
     * @param string $slug The timeline endpoint to be used on the iterator
     * @param array $arguments [Optional] Extra argument for the request
     *
     * @return TimelineIterator
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function getTimeline(string $slug, array $arguments = [])
    {
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
     * @param array $arguments Arguments for the request ('user_id' or 'screen_name' at least, but also 'count' or others are possible)
     *
     * @return FollowerIterator
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function getFollowers(array $arguments)
    {
        $slug = 'followers/ids';
        return new FollowerIterator($this, $slug, $arguments);
    }

    /**
     * Get all possible followers IDs for a given user
     * This function returns an iterator so must be used with a function that implements the Iterator
     * interface
     *
     * @param array $arguments Arguments for the request ('user_id' or 'screen_name' at least, but also 'count' or others are possible)
     *
     * @return FollowerIterator
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    public function getFriends(array $arguments)
    {
        $slug = 'friends/ids';
        return new FollowerIterator($this, $slug, $arguments);
    }

    /**
     * Get info about a given user.
     * Search can be performed based on ID, screen name...
     *
     * @param array $arguments
     *
     * @return array
     */
    public function getUser(string $query, array $extra_args = null)
    {
        if (is_numeric($query)) {
            $response = $this->getUserByID($query, $extra_args);
            if (!isset($response['errors'])) {
                return $response;
            }
        }
        return $this->getUserByUsername($query, $extra_args);
    }

    protected function getUserByID(string $twitter_id, array $extra_args = null)
    {
        $arguments = ['user_id' => $twitter_id];
        if (!is_null($extra_args)) {
            $arguments = array_merge($extra_args, $arguments);
        }
        return $this->get('users/show', $arguments);
    }

    protected function getUserByUsername(string $username, array $extra_args = null)
    {
        $arguments = ['screen_name' => $username];
        if (!is_null($extra_args)) {
            $arguments = array_merge($extra_args, $arguments);
        }
        return $this->get('users/show', $arguments);
    }

    /**
     * Get info about different users.
     *
     * @param array $arguments
     *
     * @return array
     */
    public function getUsers(array $arguments)
    {
        return $this->get('users/lookup', $arguments);
    }

    public function geoSearch(array $arguments)
    {
        return $this->get('geo/search', $arguments);
    }

    public function uploadMedia(array $params) {
        $oldHost = $this->general_config['host'];
        parent::reconfigure(['host' => 'upload.twitter.com']);

        $response = $this->post('media/upload', $params);

        // Go back to normal
        parent::reconfigure(['host' => $oldHost]);

        return $response;
    }

    public function addMetadata(array $params)
    {
        $oldHost = $this->general_config['host'];
        parent::reconfigure(['host' => 'upload.twitter.com']);

        $response = $this->post('/media/metadata/create', $params);

        // Go back to normal
        parent::reconfigure(['host' => $oldHost]);

        return $response;
    }

    /**
     * Parse the response depending on the provided code
     *
     * @param int $code Response code.
     *
     * @return mixed
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    private function response(int $code)
    {
        switch ($code) {
            // Successful
            case 200:
            case 304:
                return $this->success();
            // Non existent user
            case 403: // Forbidden:
                return $this->forbidden();
            case 404: // Removed
                return $this->requestDoesNotExist();
            // Rate limit
            case 429:
                return $this->rateLimit();
            // OAuth Credentials are NOT VALID or others
            case 400:
            default:
                return $this->generalError();
        }
    }

    /**
     * Process a successful response and return the parsed output
     *
     * @return object|array|bool Return the decoded output depending on the values of the config object,
     *                           false on error.
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    private function success()
    {
        if (!isset($this->response['response']) or !is_string($this->response['response'])) {
            throw new \Exception('There is no response! PANIC!!');
        }
        $json_output = $this->general_config['json_decode'] == 'array' ? true : false;

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
    private function forbidden() : array
    {
        return [
            'code'      => $this->response['code'],
            'errors'    => $this->response['response'],
            'tts'       => 0,
        ];
    }

    /**
     * Return the error response when the requested values (or the endpoint) do not exist.
     * ex. a requested user does not exist or has been suspended, a tweet has been deleted...
     *
     * @return array
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    private function requestDoesNotExist() : array
    {
        return [
            'code'      => $this->response['code'],
            'errors'    => $this->response['response'],
            'tts'       => 0,
        ];
    }

    /**
     * Return the error response when a rate limit has been reached on a given endpoint.
     *
     * @return array Includes the 'tts' in case this is run from a CLI and you want to
     *               sleep the process till the rate limit is gone.
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    private function rateLimit() : array
    {
        return [
            'code'      => $this->response['code'],
            'errors'    => $this->response['response'],
            'tts'       => isset($this->response['headers']['x-rate-limit-reset']) ? // Time To Sleep
                            ($this->response['headers']['x-rate-limit-reset'] - time()) :
                            false,
        ];
    }

    /**
     * Return the error response for general errors from the API.
     *
     * @return array
     *
     * @author Julio Foulquié <jfoulquie@gmail.com>
     */
    private function generalError() : array
    {
        return [
            'code'      => $this->response['code'],
            'errors'    => $this->response['response'],
            'tts'       => 0,
        ];
    }
}
