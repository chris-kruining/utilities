<?php

use CPB\Utilities\Common\Collection;
use CPB\Utilities\Contracts\Queryable;

require_once '../render.php';

echo Renderer::Init(Collection::class, [
    'from' => [
        [ 'one', 'two', 'three' ]
    ],
    [
        'from' => [ ['one', 'two', 'three'] ],
        'filter' => [ '$i => $i === \'t\'' ],
    ]
]);

$first = Collection::From([
    ['ID' => 3, 'Value' => 'baas', 'Second' => 1],
    ['ID' => 1, 'Value' => 'laars', 'Second' => 0],
]);

$second = Collection::From([
    ['ID' => 2, 'Value' => 'is'],
    ['ID' => 1, 'Value' => 'kaas'],
    ['ID' => 3, 'Value' => 'awesome'],
]);

var_dump($first->join($second, 'Second', 'ID', Queryable::JOIN_INNER));
var_dump($first->join($second, 'Second', 'ID', Queryable::JOIN_OUTER)->offset(1)->limit(2));
var_dump($first->join($second, 'Second', 'ID', Queryable::JOIN_LEFT));
var_dump($first->join($second, 'Second', 'ID', Queryable::JOIN_RIGHT));
var_dump($first->union($second));

?>