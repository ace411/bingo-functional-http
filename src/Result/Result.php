<?php

namespace Chemem\Bingo\Functional\Http\Result;

use \Chemem\Bingo\Functional\Algorithms as A;
use \Chemem\Bingo\Functional\Functors\Either;
use \Chemem\Bingo\Functional\Functors\Monads as M;
use \Chemem\Bingo\Functional\PatternMatching as PM;
use \Chemem\Bingo\Functional\Http\Response\Response;
use \Chemem\Bingo\Functional\Http\Error\Error;

class Result
{
    private $data;

    const of = 'Chemem\\Bingo\\Functional\\Http\\Result\\Result::of';

    public function __construct($value)
    {
        $this->data = $value instanceof Error ? Either\Either::left($value) : Either\Either::right($value);
    }

    public static function of($value) : Result
    {
        return new static($value);        
    }

    public function bind(callable $function) : Result
    {
        return $function($this->get());
    }

    public function map(callable $function) : Result
    {
        return $this->bind(function ($data) use ($function) {
            return new static($function($data));
        });
    }

    public function ap(Result $result) : Result
    {
        return $this->map(function ($function) use ($result) {
            return $result->map($function);
        });
    }

    public function get() : Either\Either
    {
        return $this->data;
    }
}