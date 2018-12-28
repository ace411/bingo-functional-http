<?php

namespace Chemem\Bingo\Functional\Http\Tests;

use \Eris\Generator;
use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Http;
use \Chemem\Bingo\Functional\Functors\Monads as M;
use \Chemem\Bingo\Functional\Functors\Either;
use \Chemem\Bingo\Functional\Functors\Monads\IO;

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
                'DELETE' => Generator\constant(Http\deleteRequest),
                'PUT' => Generator\constant(Http\putRequest),
                'HEAD' => Generator\constant(Http\headRequest),
                '_POST' => Generator\constant(Http\postRequestWithBody)
            ))
        )
            ->then(function (string $uri, array $actions) {
                $pluck = A\partial(A\pluck, $actions);
                $get = $pluck('GET')($uri);
                $post = $pluck('POST')($uri);
                $put = $pluck('PUT')($uri);
                $delete = $pluck('DELETE')($uri);
                $head = $pluck('HEAD')($uri);
                $_post = $pluck('_POST')($uri, 'application/json', array('name' => 'loki'));

                $this->assertInstanceOf(Http\Request\Request::class, $get);
                $this->assertInstanceOf(Http\Request\Request::class, $post);
                $this->assertInstanceOf(Http\Request\Request::class, $delete);
                $this->assertInstanceOf(Http\Request\Request::class, $put);
                $this->assertInstanceOf(Http\Request\Request::class, $head);
                $this->assertInstanceOf(Http\Request\Request::class, $_post);
                $this->assertEquals('GET', $get->rqMethod);
                $this->assertEquals('PUT', $put->rqMethod);
                $this->assertEquals('DELETE', $delete->rqMethod);
                $this->assertEquals('POST', $post->rqMethod);
                $this->assertEquals('HEAD', $head->rqMethod);
                $this->assertEquals('POST', $_post->rqMethod);
            });
    }

    public function testSetRequestBodyChangesRequestBody()
    {
        $this->forAll(
            Generator\suchThat(self::validateUrl, Generator\map(self::createUrl, Generator\string())),
            Generator\associative(array(
                'user' => Generator\names(),
                'password' => Generator\map(A\partial('hash', 'sha256'), Generator\string())
            ))
        )
            ->then(function (string $uri, array $body) {
                $request = Http\setRequestBody(Http\postRequest($uri), $body);

                $this->assertInstanceOf(Http\Request\Request::class, $request);
                $this->assertInternalType('array', $request->rqBody);
            });
    }

    public function testHttpFetchesDataOverHttpAndStoresItInResultObject()
    {
        $this
            ->limitTo(2)
            ->forAll(
                Generator\elements(
                    'https://http2.pro/api/v1',
                    'https://api.publicapis.org/random'
                )
            )
            ->then(function (string $uri) {
                $http = Http\http(Http\getRequest($uri));

                $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $http);
                $this->assertInstanceOf(Http\Result\Result::class, $http->exec());
                $this->assertInstanceOf(Either\Either::class, $http->exec()->get());
            });
    }

    public function testGetResponseCodeOutputsResponseCode()
    {
        $this
            ->limitTo(2)
            ->forAll(
                Generator\elements(
                    'https://http2.pro/api/v1',
                    'https://api.publicapis.org/random'
                )
            )
            ->then(function (string $uri) {
                $http = A\compose(Http\getRequest, Http\http, function ($response) {
                    return M\bind(Http\getResponseCode, $response);
                });

                $this->assertInternalType('integer', $http($uri)->exec());
                $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $http($uri));
            });
    }

    public function testGetResponseBodyOutputsResponseBody()
    {
        $this
            ->limitTo(2)
            ->forAll(
                Generator\elements(
                    'https://http2.pro/api/v1',
                    'https://api.publicapis.org/random'
                )
            )
            ->then(function (string $uri) {
                $http = A\compose(Http\getRequest, Http\http, function ($response) {
                    return M\bind(Http\getResponseBody, $response);
                });

                $this->assertInternalType('string', $http($uri)->exec());
                $this->assertInstanceOf(\Chemem\Bingo\Functional\Functors\Monads\IO::class, $http($uri));
            });
    }

    public function testCatchIOSafelyParsesIOException()
    {
        $this->forAll(Generator\constant('Some Exception'))
            ->then(function (string $msg) {
                $func = IO\IO(function () use ($msg) : callable {
                    return function () use ($msg) {
                        throw new \Exception($msg);
                    };
                });

                $catch = Http\catchIO($func);

                $this->assertInstanceOf(IO::class, $catch);
                $this->assertInternalType('string', $catch->exec());
                $this->assertEquals($msg, $catch->exec());
            });
    }
}