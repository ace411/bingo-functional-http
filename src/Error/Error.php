<?php

namespace Chemem\Bingo\Functional\Http\Error;

class Error
{
    private $connErr;

    public function __construct(string $connErr)
    {
        $this->connErr = $connErr;
    }

    public static function error(string $connErr) : Error
    {
        return new static($connErr);
    }

    public function get() : string
    {
        return $this->connErr;
    }
}