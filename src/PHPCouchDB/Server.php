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
     * @throws \PHPCouchDB\Exception\ServerException if there's a problem
     *  with parsing arguments or connecting to the database
     */
    public function __construct(array $options)
    {
        if (empty($options) || !is_array($options)) {
            throw new Exception\ServerException(
                '$options is a required parameter, array should contain either a url or a client'
            );
        }

        if (isset($options['client']) && $options['client'] instanceof \GuzzleHttp\ClientInterface) {
            $client = $options['client'];
        } elseif (isset($options['url'])) {
            $client = new \GuzzleHttp\Client(["base_uri" => $options['url']]);
        } else {
            throw new Exception\ServerException(
                'Failed to parse $options, array should contain either a url or a client'
            );
        }

        // try to connect as well
        try {
            $client->get('/');
            $this->client = $client;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new Exception\ServerException(
                "Could not connect to database.  Error: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Ask the CouchDB server what version it is running
     *
     * @return string Version, e.g. "2.0.1"
     * @throws \PHPCouchDB\Exception\ServerException if there's a problem with parsing arguments or creating the client
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
                throw new Exception\ServerException('JSON response not received or not understood');
            }
        }
    }

    /**
     * Get a list of databases
     *
     * @return array The database names
     * @throws \PHPCouchDB\Exception\ServerException if there's a problem with parsing arguments or creating the client
     */
    public function getAllDbs() : array
    {
        $response = $this->client->request("GET", "/_all_dbs");
        if ($response->getStatusCode() == 200) {
            // try to decode JSON
            if ($json_data = json_decode($response->getBody(), true)) {
                return $json_data;
            } else {
                throw new Exception\ServerException('JSON response not received or not understood');
            }
        }
    }

    /**
     * Create and return a Database object to work with
     *
     * @param $options Supply the "name" (required) and an optional boolean
     *  "create_if_not_exists" value (default is false)
     * @return \CouchDB\Database represents the named database
     * @throws \PHPCouchDB\Exception\ServerException if there's a problem
     *  with parsing arguments or creating the database object (e.g. database
     *  doesn't exist and shouldn't be created)
     */
    public function useDb($options) : Database
    {
        // check the $options array is sane
        if (!isset($options['name'])) {
            throw new Exception\ServerException(
                '"name" is a required $options parameter'
            );
        } else {
            $db_name = $options['name'];
        }

        $create_if_not_exists = isset($options['create_if_not_exists']) ? $options['create_if_not_exists'] : false;

        // does this database exist?
        $exists = false;
        try {
            $response = $this->client->request("GET", "/" . $db_name);
            if ($response->getStatusCode() == 200) {
                $exists = true;
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // it doesn't exist, should we create it?
            if ($create_if_not_exists) {
                $create_response = $this->client->request("PUT", "/" . $db_name);
                if ($create_response->getStatusCode() == 201) {
                    $exists = true;
                }
            }
        }

        if ($exists) {
            return new Database($this->client, $db_name);
        }

        throw new Exception\ServerException(
            'Database doesn\'t exist, include "create_if_not_exists" parameter to create it'
        );
    }

    /**
     * If you need to make a request that isn't supported by this library,
     * use this method to get the client to use.  Aimed at more advanced
     * users/requirements
     */
    public function getClient() : \GuzzleHttp\ClientInterface
    {
        return $this->client;
    }
}
