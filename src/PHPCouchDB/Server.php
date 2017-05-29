<?php

namespace PHPCouchDB;

class Server {
    protected $client;

    public function __construct(\GuzzleHttp\ClientInterface $client) {
        if($client) {
            $this->client = $client;
        } else {
            throw new Exception('$client is required');
        }
    }

    public function getVersion() {
        $response = $this->client->request("GET", "/");
        if($response->getStatusCode() == 200) {
            // try to decode JSON
            if($json_data = json_decode($response->getBody(), true)) {
                if($json_data['version']) {
                    return $json_data['version'];
                } else {
                    return "unknown";
                }
            } else {
                throw new Exception('JSON response not received or not understood');
            }
        }
    }
}
