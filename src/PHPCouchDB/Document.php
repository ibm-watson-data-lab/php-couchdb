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

    /**
     * Saves the current state of the document, returning a NEW object to
     * represent the new document revision.  If the doc is outdated, the update
     * fails.
     *
     * @return Document The updated doc
     * @throws \PHPCouchDB\Exception\DocumentConflictException if the update
     *  fails because we don't have the (correct) revision number
     * @throws \PHPCouchDB\Exception\DatabaseExecption if something else goes
     *  wrong, with the previous exception included
     */
    public function update()
    {
        $endpoint = "/" . $this->database->getName() . "/" . $this->id;

        // take a copy and drop out the internal values before sending
        $doc = get_object_vars($this);
        $doc['_rev'] = $doc['rev'];
        unset($doc['client']);
        unset($doc['id']);
        unset($doc['rev']);
        unset($doc['database']);

        try {
            $response = $this->client->request('PUT', $endpoint, ["json" => $doc]);
            // get a brand new version and return it as a brand new object
            $newrev = $this->database->getDocById($this->id);
            return $newrev;
        } catch (\GuzzleHTTP\Exception\ClientException $e) {
            // is it a conflict?  Or something else?
            if ($e->getResponse()->getStatusCode() == 409) {
                throw new Exception\DocumentConflictException('Conflict. Outdated or missing revision information');
            } else {
                throw new Exception\DatabseException('The update failed', 0, $e);
            }
        }
    }
}
