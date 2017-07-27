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
		$mock = new MockHandler([ $response1 ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
		$this->assertEquals("1.6.0", $server->getVersion());

    }

    public function testGetAllDbs() {
        $dbs = ["test", "items"];
        $response1 = new Response(200, [], json_encode($dbs));

        $mock = new MockHandler([ $response1 ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
		$this->assertEquals($dbs, $server->getAllDbs());

    }
}
