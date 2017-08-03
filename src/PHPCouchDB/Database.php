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
}
