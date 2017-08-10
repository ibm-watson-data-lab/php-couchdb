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
    protected $database;

    /**
     * Usually constructed by the Database object as it gets documents for us
     *
     * @param \PHPCouchDB\Database $database The database this document is in
     * @param array $data Representation of the document
     */

    public function __construct(Database $database, array $data)
    {
        $this->database = $database;
        $this->client  = $database->getClient();
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
     * Format object for var_dump(), removing large properties
     */
    public function __debugInfo()
    {
        // remove the $database property because the output is HUGE
        $result = get_object_vars($this);
        unset($result['client']);
        unset($result['database']);
        return $result;
    }
}
