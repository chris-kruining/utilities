<?php

use CPB\Utilities\Code\Lambda;

require_once '../../vendor/autoload.php';

$lambda = Lambda::From('$var -> $kaas => $var . $kaas;')->Use([
    'kaas' => 'awesome',
]);

var_dump($lambda('lekkere ')); 