<?php

/**
 * Functionality at the database level
 */

namespace PHPCouchDB;

/**
 * Working with a particular database is all done here.  Usually instantiated
 * by the Server object as it has the connection details we need to use.
 */


class Database
{
    protected $client;
    protected $db_name;


    /**
     * Constructor for the Database object - this is usually called by
     * Server::useDb rather than directly
     *
     * @param array $options This should contain "client" (implementing
     *  \GuzzleHTTP\ClientInterface) and "db_name" (a string)
     * @throws \PHPCouchDB\Exception\ServerException if we don't have the
     *  expected constructor arguments
     */

    public function __construct($options)
    {
        if (empty($options) || !is_array($options)) {
            throw new \PHPCouchDB\Exception\ServerException(
                '$options is a required parameter, array must contain both the client and a db_name'
            );
        }

        if (isset($options['client']) && $options['client'] instanceof \GuzzleHttp\ClientInterface) {
            $this->client = $options['client'];
        } else {
            throw new \PHPCouchDB\Exception\ServerException(
                "The options array must contain a 'client' element of type GuzzleHTTP\ClientInterface"
            );
        }

        if (isset($options['db_name']) && is_string($options['db_name'])) {
            $this->db_name = $options['db_name'];
        } else {
            throw new \PHPCouchDB\Exception\ServerException(
                "The options array must contain a 'db_name' key with a value of type string"
            );
        }
    }

    /**
     * Fetch all the documents from the database
     *
     * @param array $options  Any modifiers needed for the query  These include:
     *      - include_docs   Defaults to true
     * @return array The array contains `PHPCouchDB\Document` objects
     */
    public function getAllDocs($options = []) : array
    {
        $endpoint = "/" . $this->db_name . "/_all_docs";
        $query = ["include_docs" => "true"];
        $response = $this->client->request("GET", $endpoint, ["query" => $query]);
        if ($response->getStatusCode() == 200) {
            // try to decode JSON
            if ($json_data = json_decode($response->getBody(), true)) {
                // we have some data - extract the docs to return
                $docs = [];
                foreach ($json_data["rows"] as $document) {
                    $docs[] = new Document($document["doc"]);
                }
                return $docs;
            } else {
                throw new \PHPCouchDB\Exception\ServerException('JSON response not received or not understood');
            }
        }
    }
}
