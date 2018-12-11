<?php

namespace Chemem\Bingo\Functional\Http\Request;

class Request
{
    private $uri;

    private $method;

    private $headers;

    private $body;

    public function __construct(string $uri, string $method, array $headers, array $body)
    {
        $this->uri = $uri;
        $this->method = $method;
        $this->headers = $headers;
        $this->body = $body;
    }

    public static function request(string $uri, string $method, array $headers, array $body) : Request
    {
        return new self($uri, $method, $headers, $body);
    }

    public function get() : array
    {
        return array(
            'rqURI' => $this->uri,
            'rqMethod' => $this->method,
            'rqHeaders' => $this->headers,
            'rqBody' => $this->body
        );
    }
}