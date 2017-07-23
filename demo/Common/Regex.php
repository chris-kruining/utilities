<?php

use CPB\Utilities\Common\Regex;

require_once '../render.php';

echo Renderer::Init(Regex::class, [
    'Match' => ['/quick|lazy/', 'the quick brown fox jumped over the lazy dog'],
    'Encapsulate' => ['encapsulate.all.the.words.with.a.tick', '`'],
    'AfterLastOccurrence' => ['select.all.that.is.after.the.last.dot', '.'],
]);

?>

