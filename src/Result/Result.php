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

    public function __construct(Either\Either $data)
    {
        $this->data = $data;
    }

    public static function of(Either\Either $data) : Result
    {        
        return new self(self::resolve($data));
    }

    private static function resolve(Either\Either $object) : Either\Either
    {
        return M\bind(function ($res) : Either\Either {
            return $res instanceof Error ? Either\Either::left($res) : Either\Either::right($res);
        }, $object);
    }

    public function bind(callable $function) : Result
    {
        return $function($this->get());
    }

    public function map(callable $function) : Result
    {
        return new self($this->get()->map($function));
    }

    public function ap(Result $result) : Result
    {
        return $this->map(function ($function) use ($result) {
            return $result->map($function);
        });
    }

    public function get()
    {
        return $this->data instanceof Either\Left ? $this->data->getLeft() : $this->data->getRight();
    }
}