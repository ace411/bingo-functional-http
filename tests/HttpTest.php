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

    public function testRequestMethodOutputsResultWithEncapsulatedMethod()
    {
        $this->forAll(
            Generator\suchThat(self::validateUrl, Generator\map(self::createUrl, Generator\string())),
            Generator\associative(array(
                'GET' => Generator\constant(Http\getRequest),
                'POST' => Generator\constant(Http\postRequest),
                'HEAD' => Generator\constant(Http\headRequest),
                '_POST' => Generator\constant(Http\postRequestWithBody)
            ))
        )
            ->then(function (string $uri, array $actions) {
                $pluck = A\partial(A\pluck, $actions);
                $get = $pluck('GET')($uri);
                $post = $pluck('POST')($uri);
                $head = $pluck('HEAD')($uri);
                $_post = $pluck('_POST')($uri, 'application/json', array('name' => 'loki'));

                $this->assertInstanceOf(Http\Request\Request::class, $get);
                $this->assertInstanceOf(Http\Request\Request::class, $post);
                $this->assertInstanceOf(Http\Request\Request::class, $head);
                $this->assertInstanceOf(Http\Request\Request::class, $_post);
                $this->assertEquals('GET', $get->rqMethod);
                $this->assertEquals('POST', $post->rqMethod);
                $this->assertEquals('HEAD', $head->rqMethod);
                $this->assertEquals('POST', $_post->rqMethod);
            });
    }
}