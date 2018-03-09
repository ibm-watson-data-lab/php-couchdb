<?php

require __DIR__ . "/../../vendor/autoload.php";

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class DatabaseTest extends \PHPUnit\Framework\TestCase 
{
    public function setUp() {
        // offer a use_response for when selecting this database
        $egdb1 = '{"db_name":"egdb","update_seq":"0-g1AAAABXeJzLYWBgYMpgTmEQTM4vTc5ISXLIyU9OzMnILy7JAUklMiTV____PyuRAY-iPBYgydAApP5D1GYBAJmvHGw","sizes":{"file":8488,"external":0,"active":0},"purge_seq":0,"other":{"data_size":0},"doc_del_count":0,"doc_count":0,"disk_size":8488,"disk_format_version":6,"data_size":0,"compact_running":false,"instance_start_time":"0"}';
        $this->use_response = new Response(200, [], $egdb1);
    }

    public function testGetAllDocs() {
        $docs = '{"total_rows":2,"offset":0,"rows":[
{"id":"95613816b3a7490727388ebb470001a6","key":"95613816b3a7490727388ebb470001a6","value":{"rev":"1-71e39cb1ac06a5974a16c72b26969009"},"doc":{"_id":"95613816b3a7490727388ebb470001a6","_rev":"1-71e39cb1ac06a5974a16c72b26969009","sound":"squeak"}},
{"id":"95613816b3a7490727388ebb4700165a","key":"95613816b3a7490727388ebb4700165a","value":{"rev":"1-1ed93c4b346f531c5e7d4d69b755ee71"},"doc":{"_id":"95613816b3a7490727388ebb4700165a","_rev":"1-1ed93c4b346f531c5e7d4d69b755ee71","noise":"pop"}}
]}';
        $docs_response = new Response(200, [], $docs);

		$mock = new MockHandler([ $this->use_response, $docs_response ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $docs = $database->getAllDocs();

        $this->assertInternalType('array', $docs);
        $this->assertInstanceOf('\PHPCouchDB\Document', $docs[0]);
    }

    public function testGetAllDocsWithNoDocs() {
        $docs = '{"total_rows":0,"offset":0,"rows":[

]}';
        $docs_response = new Response(200, [], $docs);

		$mock = new MockHandler([ $this->use_response, $docs_response ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $docs = $database->getAllDocs();

        $this->assertInternalType('array', $docs);
        $this->assertEmpty($docs);
    }

    public function testCreateWithID() {
        $create = '{"ok":true,"id":"abcde12345","rev":"1-928ec193918889e122e7ad45cfd88e47"}';
        $create_response = new Response(201, [], $create);

		$mock = new MockHandler([ $this->use_response, $create_response ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $doc = $database->create(["noise" => "howl", "id" => "abcde12345"]);

        $this->assertInstanceOf('PHPCouchDB\Document', $doc);
        $this->assertObjectHasAttribute('id', $doc);
        $this->assertEquals("abcde12345", $doc->id);
    }

    public function testCreateWithoutID() {
        $create = '{"ok":true,"id":"95613816b3a7490727388ebb47002c0f","rev":"1-928ec193918889e122e7ad45cfd88e47"}';
        $create_response = new Response(201, [], $create);

		$mock = new MockHandler([ $this->use_response, $create_response ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $doc = $database->create(["noise" => "howl"]);

        $this->assertInstanceOf('PHPCouchDB\Document', $doc);
        $this->assertObjectHasAttribute('id', $doc);
    }

    public function testGetDocById() {
        // create the doc with the id (which also triggers a fetch), then fetch it
        $create = '{"ok":true,"id":"95613816b3a7490727388ebb47002c0f","rev":"1-928ec193918889e122e7ad45cfd88e47"}';
        $create_response = new Response(201, [], $create);
        $fetch = '{"_id":"95613816b3a7490727388ebb47002c0f","_rev":"1-928ec193918889e122e7ad45cfd88e47","noise":"howl"}';
        $fetch_response = new Response(200, [], $fetch);

		$mock = new MockHandler([ $this->use_response, $create_response, $fetch_response, $fetch_response ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $doc = $database->create(["noise" => "crackle"]);

        $fetched_doc = $database->getDocById($doc->id);

        $this->assertInstanceOf('PHPCouchDB\Document', $fetched_doc);
        $this->assertObjectHasAttribute('id', $fetched_doc);
    }

    public function testGetName() {
		$mock = new MockHandler([ $this->use_response ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);

        $this->assertInternalType('string', $database->getName());
    }

    public function testGetClient() {
		$mock = new MockHandler([ $this->use_response ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);

        $this->assertInstanceOf('\GuzzleHttp\ClientInterface', $database->getClient());
    }

    public function testAllDocsWithoutIncludeDocs() {
        $docs = '{"total_rows":88378,"offset":0,"rows":[
{"id":"27881d866ac53784daebdd4fd3036986","key":"27881d866ac53784daebdd4fd3036986","value":{"rev":"1-d3d95288556bb4875daa17ab81b21813"}},
{"id":"27881d866ac53784daebdd4fd3037731","key":"27881d866ac53784daebdd4fd3037731","value":{"rev":"1-4ccc2e75f0328ac53b852684f303906f"}}
]}';
        $docs_response = new Response(200, [], $docs);

		$mock = new MockHandler([ $this->use_response, $docs_response ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $docs = $database->getAllDocs([\PHPCouchDB\Database::OPTION_INCLUDE_DOCS => false]);

        $this->assertInternalType('array', $docs);
        $this->assertInternalType('array', $docs[0]);
        $this->assertArrayHasKey('id', $docs[0]);
        $this->assertArrayHasKey('rev', $docs[0]);
    }

    public function testView() {
        $view = '{"rows":[
{"key":"2012","value":34028},
{"key":"2013","value":33023},
{"key":"2014","value":21324}
]}';
        $view_response = new Response(200, [], $view);

        $mock = new MockHandler([ $this->use_response, $view_response ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $docs = $database->getView([
            \PHPCouchDB\Database::OPTION_DDOC => "myview",
            \PHPCouchDB\Database::OPTION_VIEW => "year",
            "group" => true
        ]);

        $this->assertInternalType('array', $docs);
        $this->assertEquals(3, count($docs));
        $this->assertInternalType('array', $docs[0]);
    }

    public function testViewWithIncludeDocs() {
        $view = '{"total_rows":88375,"offset":0,"rows":[
{"id":"27881d866ac53784daebdd4fd3036986","key":"2012","value":1,"doc":{"_id":"27881d866ac53784daebdd4fd3036986","_rev":"1-d3d95288556bb4875daa17ab81b21813","Retailer country":"Italy","Order method type":"Sales visit","Retailer type":"Warehouse Store","Product line":"Camping Equipment","Product type":"Lanterns","Product":"EverGlow Single","Year":"2012","Quarter":"Q1 2012","Revenue":"15130.95","Quantity":"447","Gross margin":"0.46706056"}},
{"id":"27881d866ac53784daebdd4fd3037731","key":"2012","value":1,"doc":{"_id":"27881d866ac53784daebdd4fd3037731","_rev":"1-4ccc2e75f0328ac53b852684f303906f","Retailer country":"Italy","Order method type":"Sales visit","Retailer type":"Warehouse Store","Product line":"Personal Accessories","Product type":"Knives","Product":"Single Edge","Year":"2012","Quarter":"Q1 2012","Revenue":"37411.15","Quantity":"3115","Gross margin":"0.28726062"}},
{"id":"27881d866ac53784daebdd4fd30378ed","key":"2012","value":1,"doc":{"_id":"27881d866ac53784daebdd4fd30378ed","_rev":"1-d10f91c4f214cc96bd7bf5d38943693f","Retailer country":"Italy","Order method type":"Sales visit","Retailer type":"Warehouse Store","Product line":"Personal Accessories","Product type":"Knives","Product":"Double Edge","Year":"2012","Quarter":"Q1 2012","Revenue":"9151.38","Quantity":"567","Gross margin":"0.29182156"}}
]}';
        $view_response = new Response(200, [], $view);

        $mock = new MockHandler([ $this->use_response, $view_response ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

		// userland code starts
        $server = new \PHPCouchDB\Server([\PHPCouchDB\Server::OPTION_CLIENT => $client]);
        $database = $server->useDB([\PHPCouchDB\Server::OPTION_NAME => "egdb"]);
        $docs = $database->getView([
            \PHPCouchDB\Database::OPTION_DDOC => "myview",
            \PHPCouchDB\Database::OPTION_VIEW => "year",
            "reduce" => false,
            "limit" => 3,
            \PHPCouchDB\Database::OPTION_INCLUDE_DOCS => true
        ]);

        $this->assertInternalType('array', $docs);
        $this->assertEquals(3, count($docs));
        $this->assertInstanceOf('PHPCouchDB\Document', $docs[0]);
        $this->assertObjectHasAttribute('Product type', $docs[0]);
    }
}
