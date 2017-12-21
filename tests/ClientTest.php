<?php

namespace Tagplus\Tests;

use Tagplus\Client;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use kamermans\OAuth2\Persistence\FileTokenPersistence;

class ClientTest extends TestCase 
{

    private $history = null;

    private function createMockClient(
        array $credentials = [],
        array $config = [],
        $tokenPersistence = null,
        $authClient = null
    ) {

        $client = new Client($credentials, $config, $tokenPersistence, $authClient);
        
        $mock = new Mock([new Response(299)]);
        $this->history = new History();

        $client->getEmitter()->attach($mock);
        $client->getEmitter()->attach($this->history);

        return $client;
    }

    public function testAuthorizationUrlWithMinimumParamns()
    {
        $this->assertEquals(
            'https://apidoc.tagplus.com.br/authorize?response_type=code&client_id=abc&scope=read:produtos write:clientes',
            Client::getAuthorizationUrl(
                // client_id
                'abc',
                // scope
                [
                    'read:produtos',
                    'write:clientes'
                ]
            )
        );
    }

    public function testAuthorizationCode()
    {

        // fixtures
        $_GET['code'] = '02cea611890942dca9a3bcc467f6e7c0';
        $accessTokenResponse = json_encode([
            'access_token' => 'eae31ca26ed14909a8db54eb77386908',
            'expires_in' => '0',
            'refresh_token' => '12297f2d52624584b38cc7b699c765ee',
            'type' => 'bearer',
        ]);

        $mock = new Mock([new Response(200, [], Stream::factory($accessTokenResponse))]);
        $client = new \GuzzleHttp\Client([
            'base_url' => 'https://api.iguana.q4dev.com.br' . '/oauth2/token',
            'verify' => false,
        ]);
        $client->getEmitter()->attach($mock);

        Client::getAccessToken(
            [
                'client_id' => 'a711acfeb005473e9a5a1755be79a244',
                'client_secret' => 'ed5c50930c48456b9e76551201f38d2d',
                'redirect_uri' => 'http://app.uau/ex.php',
                'scope' => 'write:clientes read:produtos'
            ],
            new FileTokenPersistence('tokens.txt'),
            $client
        );

        $this->assertFileExists('tokens.txt');
    }

    public function testClientWithoutAuthCauseError()
    {
        $this->setExpectedException('InvalidArgumentException');

        $api = $this->createMockClient();
    }

    public function testClientWithBothAuthCauseError()
    {
        $this->setExpectedException('InvalidArgumentException');
        
        $api =  $this->createMockClient([
            'apikey' => 'xxx',
            'client_id' => 'yyy',
            'client_secret' => 'zzz'
        ]);
    }

    public function testClientWithApikeySendHeader()
    {
        $api = $this->createMockClient([
            'apikey' => 'xyz'
        ]);

        $api->get('/clientes');

        $request = $this->history->getLastRequest();

        $this->assertEquals(
            'xyz',
            $request->getHeader('apikey')
        );

    }

    public function testClientWithOauth2HaveHeaderBearer()
    { 

        $tokenPersistence = new FileTokenPersistence('tokens.txt');

        $api = $this->createMockClient(
            [
                'client_id' => 'abc',
                'client_secret' => 'xyz'
            ],
            [],
            $tokenPersistence
        );

        $api->get('/produtos');

        $request = $this->history->getLastRequest();
        
        $this->assertTrue(
            $request->hasHeader('Authorization')
        );

        $this->assertStringStartsWith(
            'Bearer',
            $request->getHeader('Authorization')
        );
        
    }
}
