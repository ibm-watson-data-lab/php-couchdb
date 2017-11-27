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
    public function update() : Document
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
            if ($response->getStatusCode() == 201 && $response_data = json_decode($response->getBody(), true)) {
                $newdoc = new Document($this->database, $doc);
                $newdoc->id = $response_data['id'];
                $newdoc->rev = $response_data['rev'];
                return $newdoc;
            }
        } catch (\GuzzleHTTP\Exception\ClientException $e) {
            // is it a conflict?  Or something else?
            if ($e->getResponse()->getStatusCode() == 409) {
                throw new Exception\DocumentConflictException('Conflict. Outdated or missing revision information');
            } else {
                throw new Exception\DatabseException('The update failed', 0, $e);
            }
        }
    }

    public function delete() : bool
    {
        $endpoint = "/" . $this->database->getName() . "/" . $this->id;
        $query = ["rev" => $this->rev];

        try {
            $response = $this->client->request('DELETE', $endpoint, ["query" => $query]);
            // if successful, cool
            return true;
        } catch (\GuzzleHTTP\Exception\ClientException $e) {
            // our reaction depends on the status code
            $status = $e->getResponse()->getStatusCode();

            if ($status == 404) {
                // a 404 error is fine, means the record is already gone
                return true;
            } elseif ($status == 409) {
                // conflict, we're deleting the wrong version of the doc
                throw new Exception\DocumentConflictException(
                    'Document conflict. Only the current document revision can be deleted'
                );
            } else {
                throw new Exception\DatabaseException('The record could not be deleted', 0, $e);
            }
        }
    }
}
