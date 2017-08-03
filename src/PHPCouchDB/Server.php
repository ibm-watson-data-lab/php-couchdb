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
        if (empty($options) || !is_array($options)) {
            throw new \PHPCouchDB\Exception\ServerException(
                '$options is a required parameter, array should contain either a url or a client'
            );
        }

        if (isset($options['client']) && $options['client'] instanceof \GuzzleHttp\ClientInterface) {
            $this->client = $options['client'];
        } elseif (isset($options['url'])) {
            try {
                $this->client = new \GuzzleHttp\Client(["base_uri" => $options['url']]);
            } catch (Exception $e) {
                throw new \PHPCouchDB\Exception\ServerException(
                    "Could not connect with URL.  Error: " . $e->getMessage()
                );
            }
        } else {
            throw new \PHPCouchDB\Exception\ServerException(
                'Failed to parse $options, array should contain either a url or a client'
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
                throw new \PHPCouchDB\Exception\ServerException('JSON response not received or not understood');
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
                throw new \PHPCouchDB\Exception\ServerException('JSON response not received or not understood');
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
    public function useDb($options) : \PHPCouchDB\Database
    {
        // check the $options array is sane
        if (!isset($options['name'])) {
            throw new \PHPCouchDB\Exception\ServerException(
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
            return new \PHPCouchDB\Database(["client" => $this->client, "db_name" => $db_name]);
        }

        throw new \PHPCouchDB\Exception\ServerException(
            'Database doesn\'t exist, include "create_if_not_exists" parameter to create it'
        );
    }
}
