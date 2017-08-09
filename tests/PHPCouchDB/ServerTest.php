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
    public function testCreateWithURL() {
        $url = "http://localhost:5984";

        $server = new \PHPCouchDB\Server(["url" => $url]);

        $this->assertObjectHasAttribute('client', $server);
        $this->assertAttributeInstanceOf('\GuzzleHttp\ClientInterface', 'client', $server);
    }

    public function testCreateWithClient() {
        $url = "http://localhost:5984";
        $client = new \GuzzleHttp\Client(["base_uri" => $url]);

        $server = new \PHPCouchDB\Server(["client" => $client]);

        $this->assertObjectHasAttribute('client', $server);
        $this->assertAttributeInstanceOf('\GuzzleHttp\ClientInterface', 'client', $server);
    }

    public function testGetVersion() {

		$couchdb1 = '{"couchdb":"Welcome","uuid":"fce3d5aabfe189c988273c0ffa8d375b","version":"1.6.0","vendor":{"name":"Ubuntu","version":"15.10"}}';
		$response1 = new Response(200, [], $couchdb1);

		// Create a mock and queue two responses.
		$mock = new MockHandler([ $response1, $response1 ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
		$this->assertEquals("1.6.0", $server->getVersion());

    }

    public function testGetAllDbs() {
		$couchdb1 = '{"couchdb":"Welcome","uuid":"fce3d5aabfe189c988273c0ffa8d375b","version":"1.6.0","vendor":{"name":"Ubuntu","version":"15.10"}}';
		$db_response = new Response(200, [], $couchdb1);

        $dbs = ["test", "items"];
        $response1 = new Response(200, [], json_encode($dbs));

        $mock = new MockHandler([ $db_response, $response1 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
		$this->assertEquals($dbs, $server->getAllDbs());

    }

    public function testUseADbThatDoesExist() {
		$couchdb1 = '{"couchdb":"Welcome","uuid":"fce3d5aabfe189c988273c0ffa8d375b","version":"1.6.0","vendor":{"name":"Ubuntu","version":"15.10"}}';
		$db_response = new Response(200, [], $couchdb1);

        $egdb1 = '{"db_name":"egdb","update_seq":"0-g1AAAABXeJzLYWBgYMpgTmEQTM4vTc5ISXLIyU9OzMnILy7JAUklMiTV____PyuRAY-iPBYgydAApP5D1GYBAJmvHGw","sizes":{"file":8488,"external":0,"active":0},"purge_seq":0,"other":{"data_size":0},"doc_del_count":0,"doc_count":0,"disk_size":8488,"disk_format_version":6,"data_size":0,"compact_running":false,"instance_start_time":"0"}';
        $response1 = new Response(200, [], $egdb1);

        $mock = new MockHandler([ $db_response, $response1 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
		$this->assertInstanceOf("\PHPCouchDB\Database", $server->useDb(["name" => "egdb"]));
    }

    public function testUseADbWithCreateThatDoesExist() {
		$couchdb1 = '{"couchdb":"Welcome","uuid":"fce3d5aabfe189c988273c0ffa8d375b","version":"1.6.0","vendor":{"name":"Ubuntu","version":"15.10"}}';
		$db_response = new Response(200, [], $couchdb1);

        $egdb1 = '{"db_name":"egdb","update_seq":"0-g1AAAABXeJzLYWBgYMpgTmEQTM4vTc5ISXLIyU9OzMnILy7JAUklMiTV____PyuRAY-iPBYgydAApP5D1GYBAJmvHGw","sizes":{"file":8488,"external":0,"active":0},"purge_seq":0,"other":{"data_size":0},"doc_del_count":0,"doc_count":0,"disk_size":8488,"disk_format_version":6,"data_size":0,"compact_running":false,"instance_start_time":"0"}';
        $response1 = new Response(200, [], $egdb1);

        $mock = new MockHandler([ $db_response, $response1 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
		$this->assertInstanceOf("\PHPCouchDB\Database", $server->useDb(["name" => "egdb", "create_if_not_exists" => false]));
    }

    /**
     * @expectedException       \PHPCouchDB\Exception\ServerException
     */
    public function testUseADbThatDoesNotExist() {
		$couchdb1 = '{"couchdb":"Welcome","uuid":"fce3d5aabfe189c988273c0ffa8d375b","version":"1.6.0","vendor":{"name":"Ubuntu","version":"15.10"}}';
		$db_response = new Response(200, [], $couchdb1);

        $egdb1 = '{"error":"not_found","reason":"Database does not exist."}';
        $response1 = new Response(404, [], $egdb1);

        $mock = new MockHandler([ $db_response, $response1 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
		$server->useDb(["name" => "egdb"]);
    }

    public function testUseADbWithCreateThatDoesNotExist() {
		$couchdb1 = '{"couchdb":"Welcome","uuid":"fce3d5aabfe189c988273c0ffa8d375b","version":"1.6.0","vendor":{"name":"Ubuntu","version":"15.10"}}';
		$db_response = new Response(200, [], $couchdb1);

        $egdb1 = '{"error":"not_found","reason":"Database does not exist."}';
        $response1 = new Response(404, [], $egdb1);

        $egdb2 = '{"ok":true}';
        $response2 = new Response(201, [], $egdb2);

        $mock = new MockHandler([ $db_response, $response1, $response2 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
		$this->assertInstanceOf("\PHPCouchDB\Database", $server->useDb(["name" => "egdb", "create_if_not_exists" => true]));
    }

}
