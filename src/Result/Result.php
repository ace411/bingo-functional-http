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

    public function __construct(Either\Either $data)
    {
        $this->data = $data;
    }

    public static function result(array $data) : Result
    {
        $either = Either\Either::right($data);
        
        return new self($either);
    }

    public function resolve() : object
    {
        $result = M\bind(function (array $data) : Either\Either {
            $pluck = A\partial(A\pluck, $data);
            $arrJson = A\partialRight('json_decode', true);
            $combine = A\dropLeft(explode(' ', $pluck('headers')[0]), 1);
            $codeReason = array((int) A\head($combine), A\concat(' ', ...A\tail($combine)));
            
            return count($pluck('headers')) < 2 ? 
                Either\Either::left(Error::error(A\last($codeReason))) : 
                Either\Either::right(Response::response(...A\extend(
                    $codeReason, 
                    array($pluck('headers')), 
                    array($pluck('result'))
                )));
        }, $this->get());

        return $result;
    }

    public function get() : object
    {
        return $this->data;
    }
}