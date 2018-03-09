<?php

require __DIR__ . "/../../vendor/autoload.php";

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class DocumentTest extends \PHPUnit\Framework\TestCase 
{
    public function setUp() {
        // offer a use_response for when selecting this database
        $egdb1 = '{"db_name":"egdb","update_seq":"0-g1AAAABXeJzLYWBgYMpgTmEQTM4vTc5ISXLIyU9OzMnILy7JAUklMiTV____PyuRAY-iPBYgydAApP5D1GYBAJmvHGw","sizes":{"file":8488,"external":0,"active":0},"purge_seq":0,"other":{"data_size":0},"doc_del_count":0,"doc_count":0,"disk_size":8488,"disk_format_version":6,"data_size":0,"compact_running":false,"instance_start_time":"0"}';
        $this->use_response = new Response(200, [], $egdb1);

        $create = '{"ok":true,"id":"abcde12345","rev":"1-928ec193918889e122e7ad45cfd88e47"}';
        $this->create_response = new Response(201, [], $create);
    }

    public function testUpdate() {
        $update = '{"ok":true,"id":"abcde12345","rev":"2-74a0465bd6e3ea40a1a3752b93916762"}';
        $update_response = new Response(201, [], $update);

		$mock = new MockHandler([ $this->use_response, $this->create_response, $update_response ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $doc = $database->create(["noise" => "howl", "id" => "abcde12345"]);

        $doc->noise = "purr";
        $newdoc = $doc->update();

        $this->assertInstanceOf('PHPCouchDB\Document', $newdoc);
        $this->assertObjectHasAttribute('id', $doc);
        $this->assertEquals("abcde12345", $doc->id);
        $this->assertEquals("purr", $doc->noise);
    }

    /**
     * @expectedException \PHPCouchDB\Exception\DocumentConflictException
     */
    public function testUpdateConflict() {
        $update = '{"error":"conflict","reason":"Document update conflict."}';;
        $update_response = new Response(409, [], $update);

		$mock = new MockHandler([ $this->use_response, $this->create_response, $update_response ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $doc = $database->create(["noise" => "howl", "id" => "abcde12345"]);

        $doc->noise = "purr";
        $newdoc = $doc->update();
    }

    public function testDelete() {
        $delete = '{"ok":true,"id":"abcde12345","rev":"2-74a0465bd6e3ea40a1a3752b93916762"}';
        $delete_response = new Response(200, [], $delete);

        $delete2 = '{"error":"not_found","reason":"deleted"}';
        $delete_response2 = new Response(404, [], $delete2);

		$mock = new MockHandler([ $this->use_response, $this->create_response, $delete_response, $delete_response2 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $doc = $database->create(["noise" => "howl", "id" => "abcde12345"]);

        $result = $doc->delete();
        $this->assertEquals(true, $result);

        // should be able to delete an already-deleted doc without errors

        $result = $doc->delete();
        $this->assertEquals(true, $result);
    }

    /**
     * @expectedException \PHPCouchDB\Exception\DocumentConflictException
     */
    public function testDeleteConflict() {
        $delete = '{"error":"conflict","reason":"Document update conflict."}';
        $delete_response = new Response(409, [], $delete);

		$mock = new MockHandler([ $this->use_response, $this->create_response, $delete_response ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $doc = $database->create(["noise" => "howl", "id" => "abcde12345"]);

        $result = $doc->delete();
    }
}
