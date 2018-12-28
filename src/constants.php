<?php

/**
 * 
 * Package constants
 * 
 * @author Lochemem Bruno Michael
 * @license Apache-2.0 
 */

namespace Chemem\Bingo\Functional\Http;

const DEFAULT_SSL_OPTS = array(
    'ciphers' => 'HIGH:!SSLv2:!SSLv3',
    'verify_peer' => true,
    'disable_compression' => true
);

const RESPONSE_ITEMS = array('uri', 'method', 'headers', 'body');