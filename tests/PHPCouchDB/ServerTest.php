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
    public function testGetVersion() {

		$couchdb1 = '{"couchdb":"Welcome","uuid":"fce3d5aabfe189c988273c0ffa8d375b","version":"1.6.0","vendor":{"name":"Ubuntu","version":"15.10"}}';
		$response1 = new Response(200, [], $couchdb1);

		// Create a mock and queue two responses.
		$mock = new MockHandler([ $response1 ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);


		// userland code starts
		$server = new \PHPCouchDB\Server($client);
		$this->assertEquals("1.6.0", $server->getVersion());

    }
}
