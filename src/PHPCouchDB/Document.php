<?php

/**
 * Objects to represent individual documents
 */

namespace PHPCouchDB;

/**
 * Documents are the "rows" in a document database.  The object has keys and
 * values to represent the keys and values of the document.
 */


class Document
{
    /**
     * Usually constructed by the Database object as it gets documents for us
     *
     * @param array $data Representation of the document
     */
    public function __construct($data)
    {
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
}
