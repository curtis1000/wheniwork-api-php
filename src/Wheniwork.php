<?php

use GuzzleHttp\Client;

/**
 * Client library for the When I Work scheduling and attendance platform.
 *
 * Uses Guzzle to request JSON responses from the When I Work API.
 * Version 2 is updated to use Guzzle, and designed to be a drop-in replacement for v0.1.
 *
 * Contributors:
 * Daniel Olfelt <daniel@thisclicks.com>
 * Curtis Branum <curtis.branum@wheniwork.com>
 *
 * @author Daniel Olfelt <daniel@thisclicks.com>
 * @author Curtis Branum <curtis.branum@wheniwork.com>
 * @version 2.0
 */
class Wheniwork
{
    /**
     * Library Version
     */
    const VERSION = '2.0';

    /**
     * HTTP Methods
     */
    const METHOD_GET    = 'get';
    const METHOD_POST   = 'post';
    const METHOD_PUT    = 'put';
    const METHOD_PATCH  = 'patch';
    const METHOD_DELETE = 'delete';

    const DEFAULT_TIMEOUT = 10;

    private $api_token;
    private $response;
    private $api_endpoint = 'https://api.wheniwork.com/2';
    private $api_headers  = [];
    private $verify_ssl   = false;
    private $options      = [];

    /**
     * Create a new instance
     *
     * @param string $api_token The user WhenIWork API token
     * @param array $options Allows you to set the `headers` and the `endpoint`
     */
    function __construct($api_token = null, $options = [])
    {
        $this->api_token = $api_token;

        if (!empty($options['endpoint'])) {
            $this->setEndpoint($options['endpoint']);
        }
        if (!empty($options['headers'])) {
            $this->setHeaders($options['headers'], true);
        }
        $this->timeout = !empty($options['timeout']) ? $options['timeout']: self::DEFAULT_TIMEOUT;
    }

    /**
     * Set the user token for all requests
     *
     * @param string $api_token The user WhenIWork API token
     * @return Wheniwork
     */
    public function setToken($api_token)
    {
        $this->api_token = $api_token;

        return $this;
    }

    /**
     * Get the user token to save for future requests
     *
     * @return string The user WhenIWork API token
     */
    public function getToken()
    {
        return $this->api_token;
    }

    /**
     * Set the endpoint for all requests
     *
     * @param string $endpoint The WhenIWork API endpoint to use
     * @return Wheniwork
     */
    public function setEndpoint($endpoint)
    {
        $this->api_endpoint = $endpoint;

        return $this;
    }

    /**
     * Get the endpoint to use for future requests
     *
     * @return string The WhenIWork API endpoint
     */
    public function getEndpoint()
    {
        return $this->api_endpoint;
    }

    /**
     * Set the headers for all requests
     *
     * @param array $headers Global headers for all future requests
     * @param bool $reset
     * @return $this
     */
    public function setHeaders(array $headers, $reset = false)
    {
        if ($reset === true) {
            $this->api_headers = $headers;
        } else {
            $this->api_headers += $headers;
        }

        return $this;
    }

    /**
     * Get the host to use for future requests
     *
     * @return array Global headers array
     */
    public function getHeaders()
    {
        return $this->api_headers;
    }

    /**
     * Get an object or list.
     *
     * @param  string $method The API method to call, e.g. '/users/'
     * @param  array $params An array of arguments to pass to the method.
     * @param  array $headers Array of custom headers to be passed
     * @return array           Object of json decoded API response.
     */
    public function get($method, $params = [], $headers = [])
    {
        return $this->makeRequest($method, self::METHOD_GET, $params, $headers);
    }

    /**
     * Post to an endpoint.
     *
     * @param  string $method The API method to call, e.g. '/shifts/publish/'
     * @param  array $params An array of data used to create the object.
     * @param  array $headers Array of custom headers to be passed
     * @return array           Object of json decoded API response.
     */
    public function post($method, $params = [], $headers = [])
    {
        return $this->makeRequest($method, self::METHOD_POST, $params, $headers);
    }

    /**
     * Create an object. Helper method for post.
     *
     * @param  string $method The API method to call, e.g. '/users/'
     * @param  array $params An array of data used to create the object.
     * @param  array $headers Array of custom headers to be passed
     * @return array           Object of json decoded API response.
     */
    public function create($method, $params = [], $headers = [])
    {
        return $this->post($method, $params, $headers);
    }

    /**
     * Update an object. Must include the ID.
     *
     * @param  string $method The API method to call, e.g. '/users/1'
     * @param  array $params An array of data to update the object. Only changed fields needed.
     * @param  array $headers Array of custom headers to be passed
     * @return array           Object of json decoded API response.
     */
    public function update($method, $params = [], $headers = [])
    {
        return $this->makeRequest($method, self::METHOD_PUT, $params, $headers);
    }

    /**
     * Delete an object. Must include the ID.
     *
     * @param  string $method The API method to call, e.g. '/users/1'
     * @param  array $params An array of arguments to pass to the method.
     * @param  array $headers Array of custom headers to be passed
     * @return array           Object of json decoded API response.
     */
    public function delete($method, $params = [], $headers = [])
    {
        return $this->makeRequest($method, self::METHOD_DELETE, $params, $headers);
    }


    /**
     * Performs the underlying HTTP request. Exciting stuff happening here. Not really.
     *
     * @param  string $method The API method to be called
     * @param  string $request The type of request
     * @param  array $params Assoc array of parameters to be passed
     * @param  array $headers Assoc array of custom headers to be passed
     * @return array           Assoc array of decoded result
     */
    private function makeRequest($method, $request, $params = [], $headers = [])
    {
        $url = $this->getEndpoint() . '/' . $method;

        if ($params && ($request == self::METHOD_GET || $request == self::METHOD_DELETE)) {
            $url .= '?' . http_build_query($params);
        }

        $config = [
            'base_uri' => $this->api_endpoint,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'WhenIWork-PHP/' . static::VERSION,
            ],
            'timeout'  => $this->timeout,
        ];

        if ($this->api_token) {
            $config['headers']['W-Token'] = $this->api_token;
        }
        $config['headers'] += $this->getHeaders();
        $config['headers'] += $headers;

        $client = new Client($config);

        $body = null;
        if (in_array($request, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_PATCH]) &&
            !empty($params)) {
            $body = [
                'json' => $params,
            ];
        }

        $this->response = $client->{$request}($url, $body);
        return json_decode((string) $this->response->getBody());
    }

    /**
     * Get the Guzzle Response object
     * @return ?GuzzleHttp\Psr7\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Login helper using developer key and credentials to get back a login response
     *
     * @param  $key      Developer API key
     * @param  $email    Email of the user logging in
     * @param  $password Password of the user
     * @return
     */
    public static function login($key, $email, $password)
    {
        $params = [
            "username" => $email,
            "password" => $password,
        ];

        $headers = [
            'W-Key' => $key
        ];

        $login = new static();
        $response = $login->makeRequest("login", self::METHOD_POST, $params, $headers);

        return $response;
    }
}
