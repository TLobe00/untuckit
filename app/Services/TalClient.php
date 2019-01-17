<?php


namespace App\Services;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class TalClient {
    /**
     * @var mixed
     */
    private $baseUrl;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var
     */
    private $baseApiURI;

    /**
     * TalClient constructor.
     */
    public function __construct() {
        $this->baseUrl = env('TAL_CLIENT_URL');
        $this->baseApiURI = env('TAL_CLIENT_API_URI');
        $this->client = new Client();
    }

    /**
     * Fetch a single customer order
     * @param $orderId
     * @param string $accessToken
     * @return array
     */
    public function getOrder($orderId, $accessToken = '') {
        $uri = $this->baseApiURI . "/customerOrders";
        $url = $this->baseUrl . $uri;

        return $this->makeRequest('get', $url, [
            'customerOrderNo' => $orderId
        ], [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json'
        ]);
    }

    /**
     * Fetch an access token. The TTL for these guys is 12 hours. May want to consider caching
     * @return array
     */
    public function getAccessToken() {
        $clientId = env('TAL_CLIENT_ID');
        $clientSecret = env('TAL_CLIENT_SECRET');
        $url = $this->baseUrl . "/oauth2/token";

        return $this->makeRequest('post', $url, [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'grant_type'    => 'client_credentials',
            'scope'         => 'read write',
        ]);
    }

    /**
     * Make GET/POST HTTP requests
     * @param $method
     * @param $url
     * @param array $params     This can be GET or POST params/fields
     * @param array $headers    Any custom headers you need to pass in
     * @return mixed
     */
    private function makeRequest($method, $url, $params = [], $headers = []) {
        $options = null;
        $method = strtoupper($method);
        # Crude response wrapper for now. Can make this a class later
        $response = ['success' => false, 'errors' => null, 'data' => null];

        # Set params get/post
        if ($params) {
            $options = [];

            # Assuming either GET or POST for now
            if ('GET' == $method) {
                $options['query'] = $params;
            } else {
                $options['form_params'] = $params;
            }
        }

        # Set headers
        if($headers) {
            if(is_null($options)) {
                $options = [];
            }

            foreach($headers as $k => $v) {
                $options['headers'][$k] = $v;
            }
        }

        try {
            $res = $this->client->request(strtoupper($method), $url, $options);
            $res = json_decode($res->getBody(), true);
            $response['data'] = $res;
            $response['success'] = true;
        } catch (BadResponseException $e) {
            $res = $e->getResponse();
            $res = $res->getBody()->getContents();

            $response['data'] = json_decode($res, true);
        } catch (\Exception $e) {
            $response['errors'] = $e->getMessage();
        }

        return $response;
    }
}
