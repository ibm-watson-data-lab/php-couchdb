<?php

require __DIR__ . "/../../vendor/autoload.php";

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class ServerTest extends \PHPUnit\Framework\TestCase 
{
    public function setUp() {
        // create the first request to check we can connect, can be added to
        // the mocks for any test that wants it
        $couchdb1 = '{"couchdb":"Welcome","version":"2.0.0","vendor":{"name":"The Apache Software Foundation"}}';
		$this->db_response = new Response(200, [], $couchdb1);
    }

    public function testCreateWithClient() {
		$mock = new MockHandler([ ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);

        $this->assertObjectHasAttribute(\PHPCouchDB\Server::OPTION_CLIENT, $server);
        $this->assertAttributeInstanceOf('\GuzzleHttp\ClientInterface', \PHPCouchDB\Server::OPTION_CLIENT, $server);
    }

    public function testCreateWithUrl() {
		$mock = new MockHandler([ ]);

		$handler = HandlerStack::create($mock);

		// userland code starts
		$server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_URL => "http://localhost:5984"]);

        $this->assertObjectHasAttribute(\PHPCouchDB\Server::OPTION_CLIENT, $server);
        $this->assertAttributeInstanceOf('\GuzzleHttp\ClientInterface', \PHPCouchDB\Server::OPTION_CLIENT, $server);

        $config = $server->getClient()->getConfig();
        $this->assertArrayHasKey('User-Agent', $config['headers']);
        $this->assertStringStartsWith('PHPCouchDB', $config['headers']['User-Agent']);
    }

    public function testGetVersion() {
		$mock = new MockHandler([ $this->db_response ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
		$this->assertEquals("2.0.0", $server->getVersion());

    }

    public function testGetAllDbs() {
        $dbs = ["test", "items"];
        $response1 = new Response(200, [], json_encode($dbs));

        $mock = new MockHandler([ $response1 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
		$this->assertEquals($dbs, $server->getAllDbs());
    }

    public function testUseADbThatDoesExist() {
        $egdb1 = '{"db_name":"egdb","update_seq":"0-g1AAAABXeJzLYWBgYMpgTmEQTM4vTc5ISXLIyU9OzMnILy7JAUklMiTV____PyuRAY-iPBYgydAApP5D1GYBAJmvHGw","sizes":{"file":8488,"external":0,"active":0},"purge_seq":0,"other":{"data_size":0},"doc_del_count":0,"doc_count":0,"disk_size":8488,"disk_format_version":6,"data_size":0,"compact_running":false,"instance_start_time":"0"}';
        $response1 = new Response(200, [], $egdb1);

        $mock = new MockHandler([ $response1 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
		$this->assertInstanceOf("\PHPCouchDB\Database", $server->useDb([\PHPCouchDB\Server::OPTION_NAME => "egdb"]));
    }

    public function testUseADbWithCreateThatDoesExist() {
        $egdb1 = '{"db_name":"egdb","update_seq":"0-g1AAAABXeJzLYWBgYMpgTmEQTM4vTc5ISXLIyU9OzMnILy7JAUklMiTV____PyuRAY-iPBYgydAApP5D1GYBAJmvHGw","sizes":{"file":8488,"external":0,"active":0},"purge_seq":0,"other":{"data_size":0},"doc_del_count":0,"doc_count":0,"disk_size":8488,"disk_format_version":6,"data_size":0,"compact_running":false,"instance_start_time":"0"}';
        $response1 = new Response(200, [], $egdb1);

        $mock = new MockHandler([ $response1 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
		$this->assertInstanceOf("\PHPCouchDB\Database", $server->useDb(
		    [\PHPCouchDB\Server::OPTION_NAME => "egdb", \PHPCouchDB\Server::OPTION_CREATE_IF_NOT_EXISTS => false]
        ));
    }

    /**
     * @expectedException       \PHPCouchDB\Exception\ServerException
     */
    public function testUseADbThatDoesNotExist() {
        $egdb1 = '{"error":"not_found","reason":"Database does not exist."}';
        $response1 = new Response(404, [], $egdb1);

        $mock = new MockHandler([ $response1 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
		$server->useDb([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
    }

    public function testUseADbWithCreateThatDoesNotExist() {
        $egdb1 = '{"error":"not_found","reason":"Database does not exist."}';
        $response1 = new Response(404, [], $egdb1);

        $egdb2 = '{"ok":true}';
        $response2 = new Response(201, [], $egdb2);

        $mock = new MockHandler([ $response1, $response2 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
		$this->assertInstanceOf("\PHPCouchDB\Database", $server->useDb(
		    [\PHPCouchDB\Server::OPTION_NAME => "egdb", \PHPCouchDB\Server::OPTION_CREATE_IF_NOT_EXISTS => true]
        ));
    }

    public function testGetClient() {
		$mock = new MockHandler([ ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
		$this->assertInstanceOf("\GuzzleHttp\ClientInterface", $server->getClient());
    }
}
