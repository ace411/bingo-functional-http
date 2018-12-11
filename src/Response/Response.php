<?php

namespace Chemem\Bingo\Functional\Http\Response;

class Response
{
    private $code;

    private $reason;

    private $headers;

    private $body;

    public function __construct(int $code, string $reason, array $headers, string $body)
    {
        $this->code = $code;
        $this->reason = $reason;
        $this->headers = $headers;
        $this->body = $body;
    }

    public static function response(int $code, string $reason, array $headers, string $body) : Response
    {
        return new self($code, $reason, $headers, $body);
    }

    public function get() : array
    {
        return array(
            'rspCode' => $this->code,
            'rspReason' => $this->reason,
            'rspHeaders' => $this->headers,
            'rspBody' => $this->body
        );
    }
}