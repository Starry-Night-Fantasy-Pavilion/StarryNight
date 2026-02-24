<?php

namespace BookSourceManager\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HttpService {
    private Client $client;
    
    public function __construct() {
        $this->client = new Client([
            'timeout' => 10,
            'verify' => false, // In a production environment, this should be set to true.
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);
    }
    
    /**
     * Send a GET request.
     * @param string $url
     * @param array $headers
     * @return string
     * @throws GuzzleException
     */
    public function get(string $url, array $headers = []): string {
        $response = $this->client->get($url, ['headers' => $headers]);
        return $response->getBody()->getContents();
    }
    
    /**
     * Send a POST request.
     * @param string $url
     * @param array $headers
     * @param array $formParams
     * @return string
     * @throws GuzzleException
     */
    public function post(string $url, array $headers = [], array $formParams = []): string {
        $response = $this->client->post($url, [
            'headers' => $headers,
            'form_params' => $formParams
        ]);
        return $response->getBody()->getContents();
    }
}
