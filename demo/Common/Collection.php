<?php

use CPB\Utilities\Common\Collection;

require_once '../render.php';

echo Renderer::Init(Collection::class, [
    'From' => [
        [ 'one', 'two', 'three' ]
    ],
    [
        'From' => [ ['one', 'two', 'three'] ],
        'filter' => [ function($item){ return $item[0] === 't'; } ],
    ]
]);

var_dump(Collection::From(['one', 'two', 'three'])->where('x => x[0] === \'t\''));

?>