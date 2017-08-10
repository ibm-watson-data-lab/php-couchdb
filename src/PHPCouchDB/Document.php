<?php

/**
 * Objects to represent individual documents
 */

namespace PHPCouchDB;

/**
 * Documents are the "rows" in a document database.  The object has keys and
 * values to represent the keys and values of the document.  Operations on
 * an existing document, such as update and delete, are done by this object
 */


class Document
{
    protected $client;
    protected $db_name;

    /**
     * Usually constructed by the Database object as it gets documents for us
     *
     * @param \GuzzleHTTP\ClientInterface $client Our HTTP client
     * @param string $db_name The database this document is in
     * @param array $data Representation of the document
     */

    public function __construct(\GuzzleHttp\ClientInterface $client, string $db_name, array $data)
    {
        $this->client  = $client;
        $this->db_name = $db_name;
        // possibly overly simple!
        // Add all array elements as properties on the new object
        foreach ($data as $field => $value) {
            if ($field == "_id") {
                $this->id = $value;
            } elseif ($field == "_rev") {
                $this->rev = $value;
            } else {
                $this->{$field} = $value;
            }
        }
    }

    /**
     * Format object for var_dump() - removes the $client property
     */
    public function __debugInfo()
    {
        // remove the $client object because the output is HUGE
        $result = get_object_vars($this);
        unset($result['client']);
        return $result;
    }
}
