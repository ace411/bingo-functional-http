<?php

/**
 * 
 * @see http://hackage.haskell.org/package/HTTP-4000.3.12/docs/Network-HTTP.html
 *
 * Http primary functions
 * 
 * @author Lochemem Bruno Michael
 * @license Apache-2.0 
 */

namespace Chemem\Bingo\Functional\Http;

use \Composer\CaBundle\CaBundle;
use \Chemem\Bingo\Functional\Functors\Maybe;
use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Http\Result\Result;
use \Chemem\Bingo\Functional\Functors\Monads\IO;
use \Chemem\Bingo\Functional\Functors\Monads as M;
use \Chemem\Bingo\Functional\PatternMatching as PM;
use \Chemem\Bingo\Functional\Functors\Either;

const show = 'Chemem\\Bingo\\Functional\\Http\\show';
// show :: Http a -> String
function show(object $http) : string
{
    return json_encode($http);
}

const getHeaders = 'Chemem\\Bingo\\Functional\\Http\\getHeaders';
//getHeaders :: Http a -> [Header]
function getHeaders(object $http) : array
{
    return PM\patternMatch(array(
        Response\Response::class => function () use ($http) {
            return $http->rspHeaders;
        },
        Request\Request::class => function () use ($http) {
            return $http->rqHeaders;
        },
        '_' => function () : array {
            return array();
        }
    ), $http);
}

const noMsg = 'Chemem\\Bingo\\Functional\\Http\\noMsg';
//noMsg :: Error
function noMsg() : Error\Error
{
    return (new Error);
}

const strMsg = 'Chemem\\Bingo\\Functional\\Http\\strMsg';
//strMsg :: String -> Error
function strMsg(string $msg) : Error\Error
{
    $error = new Error;
    $error->connErr = $msg;
    return $error;
}

function _setReqOpt(Request\Request $http, string $opt, array $data) : Request\Request
{
    $http->$opt = A\extend((is_array($http->$opt) ? $http->$opt : array()), $data);
    return $http;
}

const setHeaders = 'Chemem\\Bingo\\Functional\\Http\\setHeaders';
//setHeaders :: Request a -> [Header] -> Request a
function setHeaders(Request\Request $http, array $headers) : Request\Request
{
    return _setReqOpt($http, 'rqHeaders', $headers);
}

const setRequestBody = 'Chemem\\Bingo\\Functional\\Http\\setRequestBody';
//setRequestBody :: Request a -> [body] -> Request
function setRequestBody(Request\Request $http, array $body) : Request\Request
{
    return _setReqOpt($http, 'rqBody', $body);
}

const http = 'Chemem\\Bingo\\Functional\\Http\\http';
//http :: Request ty -> IO (Result (Response ty))
function http(Request\Request $request) : IO
{
    $http = A\compose('stream_context_create', function ($context) use ($request) : IO {
        return M\bind(A\compose(Result::of, IO\IO), IO\IO(function () use ($request, $context) {
            $data = @file_get_contents($request->rqUri, false, $context);
            $error = new Error\Error;
            $error->connErr = 'Invalid Request';
            return Either\Either::right(
                isset($http_response_header) ?
                    _bindResponse(...A\extend(
                        _extrCodeReason($http_response_header), 
                        array($http_response_header), 
                        array($data)
                    )) :
                    $error
            );
        })); 
    });

    return $http(array(
        'http' => array(
            'method' => $request->rqMethod,
            'header' => $request->rqHeaders,
            'content' => json_encode($request->rqBody)
        ),
        'ssl' => A\extend(DEFAULT_SSL_OPTS, array(
            'cafile' => CaBundle::getBundledCaBundlePath()
        ))
    ));
}

//bindOpt :: Object -> [opts] -> [data] -> Object
function _bindOpt(object $http, array $data) : object 
{
    $pluck = A\partial(A\pluck, $data);
    return PM\patternMatch(array(
        Response\Response::class => function () use ($http, $pluck) : Response\Response {
            $http->rspCode = $pluck(0);
            $http->rspReason = $pluck(1);
            $http->rspHeaders = $pluck(2);
            $http->rspBody = $pluck(3);
            return $http;
        },
        '_' => function () use ($http, $pluck) : Request\Request {
            $http->rqUri = $pluck(0);
            $http->rqMethod = $pluck(1);
            $http->rqHeaders = $pluck(2);
            $http->rqBody = $pluck(3);
            return $http;
        }
    ), $http);
}

function _bindResponse(...$data) : Response\Response
{
    return _bindOpt((new Response\Response), $data);
}

function _bindRequest(...$data) : Request\Request
{
    return _bindOpt((new Request\Request), $data);
}

//_extrCodeReason :: [Header] -> [code, reason]
function _extrCodeReason(array $headers) : array
{
    $extr = A\compose(A\head, A\partial('explode', ' '), function (array $data) : array {
        $sec = A\dropLeft($data, 1);
        return array(A\head($sec), implode(' ', A\tail($sec)));
    });
    return $extr($headers);
}

//_baseReq :: String -> a -> Request
function _baseReq(string $uri, ...$opts) : Request\Request
{
    if (!filter_var($uri, FILTER_VALIDATE_URL)) {
        throw new \Exception('Invalid URL');
    }

    return _bindRequest(...A\extend(array($uri), $opts));
}

const getRequest = 'Chemem\\Bingo\\Functional\\Http\\getRequest';
//getRequest :: String -> Request
function getRequest(string $uri) : Request\Request
{
    return _baseReq($uri, 'GET', array(), array());
}

const postRequest = 'Chemem\\Bingo\\Functional\\Http\\postRequest';
//postRequest :: String -> Request
function postRequest(string $uri) : Request\Request
{
    return _baseReq($uri, 'POST', array(), array());
}

const headRequest = 'Chemem\\Bingo\\Functional\\Http\\headRequest';
//headRequest :: String -> Request
function headRequest(string $uri) : Request\Request
{
    return _baseReq($uri, 'HEAD', array(), array());
}

const postRequestWithBody = 'Chemem\\Bingo\\Functional\\Http\\postRequestWithBody';
//postRequestWithBody :: String -> String -> [body] -> Request
function postRequestWithBody(string $uri, string $contentType, array $body) : Request\Request
{
    return _baseReq($uri, 'POST', $body, array(A\concat(' ', 'Content-Type:', $contentType)));
}

//_deconstructResponse :: Result (Response ty) -> String -> IO  
function _deconstructResponse(Result $result, string $opt) : IO
{
    $http = $result->get();
    $res = A\compose(
        A\partial(PM\patternMatch, array(
            '"body"' => function () use ($http) : string {
                return PM\patternMatch(array(
                    Response\Response::class => function () use ($http) : string {
                        return $http->rspBody;
                    },
                    '_' => function () : string {
                        return json_encode(array());
                    }
                ), $http); 
            },
            '"code"' => function () use ($http) : int {
                return $http->rspCode;
            },
            '_' => function () : array {
                return array();
            }
        )),
        IO\IO
    );
    return $res($opt);
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

//catchIO :: IO a -> (IOException -> IO a) -> IO a
function catchIO(IO $result) : IO
{
    return M\bind(function (callable $action) {
        return IO\IO(A\toException($action));
    }, $result);
}

const bindE = 'Chemem\\Bingo\\Functional\\Http\\bindE';
//bindE :: Result a -> (a -> Result b) -> Result b
function bindE(Result $result, callable $function) : Result
{
    return $result->bind($function);
}

const fmapE = 'Chemem\\Bingo\\Functional\\Http\\fmapE';
//fmapE :: (a -> Result b) -> IO (Result a) -> IO (Result b)
function fmapE(callable $function, IO $result) : IO
{
    return M\bind(function (Result $result) use ($function) {
        return IO\IO($result->bind($function));
    }, $result);
}

function _bindResult($data) : Result
{
    return Result::of(Either\Either::right($data));
}

const failWith = 'Chemem\\Bingo\\Functional\\Http\\failWith';
//failWith :: Error -> Result a
function failWith(Error\Error $error) : Result
{
    return _bindResult($error->connErr);
}

const failParse = 'Chemem\\Bingo\\Functional\\Http\\failParse';
//failParse :: String -> Result a
function failParse(string $msg) : Result
{
    return _bindResult($msg);
}
