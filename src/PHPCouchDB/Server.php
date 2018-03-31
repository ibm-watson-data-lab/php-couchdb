<?php

/**
 * Server-related features
 */

namespace PHPCouchDB;

const VERSION = "0.1.2";

/**
 * Server class deals with operations on the server level, rather than specific
 * to a particular database
 */


class Server
{
    protected $client;

    const OPTION_CLIENT = 'client';
    const OPTION_URL = 'url';
    const OPTION_NAME = 'name';
    const OPTION_CREATE_IF_NOT_EXISTS = 'create_if_not_exists';

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

        if (isset($options[self::OPTION_CLIENT])
            && $options[self::OPTION_CLIENT] instanceof \GuzzleHttp\ClientInterface) {
            $client = $options[self::OPTION_CLIENT];
        } elseif (isset($options[self::OPTION_URL])) {
            // set a descriptive user agent
            $user_agent = \GuzzleHttp\default_user_agent();
            $client = new \GuzzleHttp\Client(["base_uri" => $options[self::OPTION_URL],
            "headers" => ["User-Agent" => "PHPCouchDB/" . VERSION . " " . $user_agent]]);
        } else {
            throw new Exception\ServerException(
                'Failed to parse $options, array should contain either a url or a client'
            );
        }

        $this->client = $client;
    }

    /**
     * Ask the CouchDB server what version it is running
     *
     * @return string Version, e.g. "2.0.1"
     * @throws \PHPCouchDB\Exception\ServerException if there's a problem with
     * connecting to the server or parsing arguments
     */
    public function getVersion() : string
    {
        try {
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
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new Exception\ServerException(
                "Could not connect to database.  Error: " . $e->getMessage(),
                0,
                $e
            );
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
        if (!isset($options[self::OPTION_NAME])) {
            throw new Exception\ServerException(
                '"name" is a required $options parameter'
            );
        } else {
            $db_name = $options[self::OPTION_NAME];
        }


        if (isset($options[self::OPTION_CREATE_IF_NOT_EXISTS])) {
            $create_if_not_exists = $options[self::OPTION_CREATE_IF_NOT_EXISTS];
        } else {
            // default value
            $create_if_not_exists = false;
        }

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
     * Create a database user and return the revision of the user record (for later updating)
     *
     * @author David Baltusavich <david@synatree.com>
     * @param $username the new username
     * @param $password the password to setup for the user
     * @param optional $roles if you want to specify the roles on the server for the user. defaults to []
     * @return string revision string
     * @throws \PHPCouchDB\Exception\ServerException if there's a problem
     */
    public function createUser($username, $password, $roles = [])
    {
        $doc = [
            'name' => $username,
            'password' => $password,
            'roles' => $roles,
            'type' => 'user',
        ];
        try {
            $response = $this->client->request("PUT", "/_users/org.couchdb.user:" . $username, ['json' => $doc]);
            if ($response->getStatusCode() == 201 && $response_data = json_decode($response->getBody(), true))
            {
                return $response_data['rev'];
            }
        } 
        catch (\GuzzleHttp\Exception\ClientException $e) {
            return false;
        }
	    throw new Exception\ServerException(
            'Problem creating user'
        );

    }

    /**
     * Update a database user and return the revision of the user record (for further updating)
     *
     * @author David Baltusavich <david@synatree.com>
     * @param $username the new username
     * @param $password the new password
     * @param optional $rev the revision of the current record (saves a query if you can specify this)
     * @param optional $roles if you want to specify the roles on the server for the user. defaults to []
     * @return string revision string
     * @throws \PHPCouchDB\Exception\ServerException if there's a problem
     */
    public function updateUser($username, $password, $rev = false, $roles = [])
    {
        $doc = [
            'password' => $password,
            'type' => 'user',
            'name' => $username,
            'roles' => $roles,
        ];
        if(!$rev)
        {
            try
            {
            	$response = $this->client->request("GET", "/_users/org.couchdb.user:" . $username);
            	if ($response->getStatusCode() == 200 && $response_data = json_decode($response->getBody(), true))
           	    {
                    $rev = $response_data['_rev'];
                }
                else
                {
                    if($response->getStatusCode() == 404)
                    {
                        throw new Exception\ServerException("Your connection doesn't have privileges to confirm the user, or the user does not exist");
                    }
                    throw new Exception\ServerException("Something went wrong: " . $response->getStatusCode());
                }
            } 
            catch (\GuzzleHttp\Exception\ClientException $e){
		        throw new Exception\ServerException(
                    'Could not retrieve the username provided'
                );
            }
        }
        try
        {
            $response = $this->client->request("PUT", "/_users/org.couchdb.user:" . $username, [
                'json' => $doc,
                'headers' => [
                    'If-Match' => $rev
                ],
            ]);
            if ($response->getStatusCode() == 201 && $response_data = json_decode($response->getBody(), true))
            {
            	return $response_data['rev'];
            }
            else
            {
                if($response->getStatusCode() == 409)
                {
                    throw new Exception\ServerException("Your connection doesn't have privileges to update the user password");
                }
                throw new Exception\ServerException("Something went wrong: " . $response->getStatusCode());
            }
        } 
        catch (\GuzzleHttp\Exception\ClientException $e)
        {
            throw new Exception\ServerException(
                    "Failing Updating User: " . $e->getMessage() . "REV: $rev"
                );
        }
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
