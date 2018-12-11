<?php

namespace Chemem\Bingo\Functional\Http\Tests;

use \Eris\Generator;
use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Http;

class HttpTest extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    const createUrl = 'Chemem\\Bingo\\Functional\\Http\\Tests\\HttpTest::createUrl';

    public static function createUrl(string $domain) : string
    {
        return A\concat('', 'http://', $domain, '.io');
    }

    const validateUrl = 'Chemem\\Bingo\\Functional\\Http\\Tests\\HttpTest::validateUrl'; 

    public static function validateUrl(string $uri)
    {
        return filter_var($uri, FILTER_VALIDATE_URL) !== false;
    }

    public function testShowConvertsHttpObjectToString()
    {
        $this->forAll(
            Generator\suchThat(self::validateUrl, Generator\map(self::createUrl, Generator\string()))
        )
            ->then(function (string $uri) {
                $httpObj = Http\getRequest($uri);
                $str = Http\show($httpObj);

                $this->assertInternalType('string', $str);
            });
    }

    public function testGetHeadersOutputsAllHeadersAsArray()
    {
        $this->forAll(
            Generator\suchThat(self::validateUrl, Generator\map(self::createUrl, Generator\string()))
        )
            ->then(function (string $uri) {
                $headers = Http\getHeaders(Http\getRequest($uri));

                $this->assertInternalType('array', $headers);
                $this->assertEquals(array(), $headers);
            });
    }

    public function testSetHeadersOutputsRequestInstance()
    {
        $this->forAll(
            Generator\suchThat(self::validateUrl, Generator\map(self::createUrl, Generator\string())),
            Generator\associative([
                'X-Auth' => Generator\map(A\partial('hash', 'sha256'), Generator\string()),
                'X-Origin' => Generator\names()
            ])
        )
            ->then(function (string $uri, array $headers) {
                $final = A\map(A\partial('implode', ': '), A\toPairs($headers));
                $request = Http\getRequest($uri);
                $new = Http\setHeaders($request, $final);
                
                $this->assertInstanceOf(Http\Request\Request::class, $new);
                $this->assertInternalType('array', Http\getHeaders($new));
                $this->assertEquals($final, Http\getHeaders($new));
            });
    }

    public function testGetRequestOutputsRequestObjectWithGetMethod()
    {
        $this->forAll(
            Generator\suchThat(self::validateUrl, Generator\map(self::createUrl, Generator\string()))
        )
            ->then(function (string $uri) {
                $req = Http\getRequest($uri);

                $this->assertInstanceOf(Http\Request\Request::class, $req);
                $this->assertEquals('GET', A\pluck($req->get(), 'rqMethod'));
            });
    }
}