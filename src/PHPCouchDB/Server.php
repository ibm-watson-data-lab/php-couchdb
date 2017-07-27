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

    /**
     * Create the object to represent the server
     *
     * See also: \PHPCouchDB\Server:createFromURL()
     *
     * @param array $options Supply either a string "url" parameter OR a
     *  \GuzzleHttp\ClientInterface "client" parameter if more configuration
     *  is required
     * @throws \PHPCouchDB\Exception\ServerException if there's a problem with parsing arguments or creating the client
     */
    public function __construct(array $options)
    {
        if(empty($options) || !is_array($options)) {
            throw new \PHPCouchDB\Exception\ServerException('$options is a required parameter, array should contain either a url or a client');
        }

        if(isset($options['client']) && $options['client'] instanceof \GuzzleHttp\ClientInterface) {
            $this->client = $options['client'];
        } elseif(isset($options['url'])) {
            try {
                $this->client = new \GuzzleHttp\Client(["base_uri" => $options['url']]);
            } catch(Exception $e) {
                throw new \PHPCouchDB\Exception\ServerException("Could not connect with URL.  Error: " . $e->getMessage());
            }
        } else {
            throw new \PHPCouchDB\Exception\ServerException('Failed to parse $options, array should contain either a url or a client');
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
