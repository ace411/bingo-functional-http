<?php

/**
 * 
 * @see http://hackage.haskell.org/package/HTTP-4000.3.12/docs/Network-HTTP.html
 * 
 */

namespace Chemem\Bingo\Functional\Http;

use \Composer\CaBundle\CaBundle;
use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use \Chemem\Bingo\Functional\Functors\Monads as M;
use \Chemem\Bingo\Functional\PatternMatching as PM;
use \Chemem\Bingo\Functional\Http\Result\Result;

const show = 'Chemem\\Bingo\\Functional\\Http\\show';
// show :: Http a -> String
function show(object $http) : string
{
    return json_encode($http->get());
}

const getHeaders = 'Chemem\\Bingo\\Functional\\Http\\getHeaders';
//getHeaders :: Http a -> [Header]
function getHeaders(object $http) : array
{
    $let = PM\letIn(array('uri', 'method', 'headers'), $http->get());

    return $let(array('headers'), A\identity);
}

const setHeaders = 'Chemem\\Bingo\\Functional\\Http\\setHeaders';
//setHeaders :: Request a -> [Header] -> Request a
function setHeaders(Request\Request $http, array $headers) : Request\Request
{
    $let = PM\letIn(RESPONSE_ITEMS, $http->get());

    return $let(RESPONSE_ITEMS, function ($uri, $method, $oldHeaders, $body) use ($headers) {
        return Request\Request::request($uri, $method, A\extend($oldHeaders, $headers), $body);
    });
}

const http = 'Chemem\\Bingo\\Functional\\Http\\http';
//http :: Request ty -> IO (Result (Response ty))
function http(Request\Request $request) : IO
{
    $let = PM\letIn(RESPONSE_ITEMS, $request->get());

    return $let(RESPONSE_ITEMS, function ($uri, $method, $headers, $body) {
        $context = stream_context_create(array(
            'http' => array(
                'method' => $method,
                'header' => $headers,
                'content' => json_encode($body)
            ),
            'ssl' => A\extend(DEFAULT_SSL_OPTS, array(
                'cafile' => CaBundle::getBundledCaBundlePath()
            ))
        ));

        return M\bind(
            function (array $http) {
                return IO\IO(Result::result($http));
            },
            IO\IO(function () use ($uri, $context) {
                $data = @file_get_contents($uri, false, $context);
                return array(
                    'headers' => isset($http_response_header) ? $http_response_header : array('HTTP/1.0 404 Not Found'),
                    'result' => isset($data) ? $data : json_encode(array())
                );
            })
        );
    });
}

//_baseReq :: String -> a -> Request
function _baseReq(string $uri, ...$opts) : Request\Request
{
    if (!filter_var($uri, FILTER_VALIDATE_URL)) {
        throw new \Exception('Invalid URL');
    }
    return Request\Request::request($uri, ...$opts);
}

//getRequest :: String -> Request
function getRequest(string $uri) : Request\Request
{
    return _baseReq($uri, 'GET', array(), array());
}

//postRequest :: String -> Request
function postRequest(string $uri) : Request\Request
{
    return _baseReq($uri, 'POST', array(), array());
}

//headRequest :: String -> Request
function headRequest(string $uri) : Request\Request
{
    return _baseReq($uri, 'HEAD', array(), array());
}

//postRequestWithBody :: String -> String -> [body] -> Request
function postRequestWithBody(string $uri, string $contentType, array $body) : Request\Request
{
    return _baseReq($uri, 'POST', array(A\concat(' ', 'Content-Type:', $contentType)), $body);
}

//_deconstructResponse :: Result (Response ty) -> String -> IO  
function _deconstructResponse(Result $result, string $opt) : IO
{
    $let = PM\letIn(array('code', 'reason', 'headers', 'body'), $result->resolve()->getRight()->get());

    return $let(array($opt), A\compose(A\identity, IO\IO));
}

const getResponseBody = 'Chemem\\Bingo\\Functional\\Http\\getResponseBody';
//getResponseBody :: Result (Response ty) -> IO ty
function getResponseBody(Result $result) : IO
{
    return _deconstructResponse($result, 'body');
}

const getResponseCode = 'Chemem\\Bingo\\Functional\\Http\\getResponseCode';
//getResponseCode :: Result (Response ty) -> IO ResponseCode
function getResponseCode(Result $result) : IO
{
    return _deconstructResponse($result, 'code');
}