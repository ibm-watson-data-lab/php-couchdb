<?php

/**
 * Server-related features
 */

namespace PHPCouchDB;

/**
 * Server class deals with operations on the server level, rather than specific
 * to a particular database
 */


class Server
{
    protected $client;

    public function __construct(\GuzzleHttp\ClientInterface $client)
    {
        if ($client) {
            $this->client = $client;
        } else {
            throw new Exception('$client is required');
        }
    }

    /**
     * Ask the CouchDB server what version it is running
     *
     * @return string Version, e.g. "2.0.1"
     */
    public function getVersion() : string
    {
        $response = $this->client->request("GET", "/");
        if ($response->getStatusCode() == 200) {
            // try to decode JSON
            if ($json_data = json_decode($response->getBody(), true)) {
                if ($json_data['version']) {
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
