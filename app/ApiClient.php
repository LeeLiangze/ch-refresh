<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\RequestOptions;

class ApiClient
{
    private $apiKey;
    private $url;
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->apiKey = 'rA0q67u2scvuV2DyTLQ';
//        $this->url = env('POSAPI_URL', '');
        $this->url = 'https://incallsystemshelpdesk.freshservice.com/api/v2/';
    }

    public function get($endpoint, $options = [])
    {
        $res = $this->rawRequest('GET', $endpoint, $options);
        if ($res === 404){
            return $res;
        } else {
            return json_decode($res->getBody()->getContents(), true);
        }
    }

    public function post($endpoint, $options = [])
    {
        return json_decode(
            $this->rawRequest('POST', $endpoint, $options)
                ->getBody()
                ->getContents(),
            true
        );
    }

    public function put($endpoint, $options = [])
    {
        return json_decode(
            $this->rawRequest('PUT', $endpoint, $options)
                ->getBody()
                ->getContents()
        );
    }

    public function delete(string $endpoint, array $options = [])
    {
        return json_decode(
            $this->rawRequest('DELETE', $endpoint, $options)
                ->getBody()
                ->getContents()
        );
    }

    public function rawRequest(string $method, $uri, array $options = [])
    {
        try {
            if ( substr($uri,0,4)=='http' ) {
                $url = $uri;
            }
            else {
                $url = $this->url . $uri;
                $options['headers']['Content-Type'] = 'application/json';
                $options['auth'] = array($this->apiKey, 'X');
            }
            $res = $this->client->request($method, $url, $options);
            return $res;
        } catch (BadResponseException $e) {
            return $e->getCode();
        }
    }
}
