<?php

require_once __DIR__ . '/../../src/Common/Regex.php';

?>

<h1>Demo for Regex utility</h1>

<p>
    
    <?php
    
    $subject = 'the quick brown fox jumped over the lazy dog';
    $pattern = '/quick|lazy/';
    
    echo 'Subject :: ' . $subject . '<br />';
    echo 'Pattern :: ' . $pattern . '<br />';
    echo 'Result  :: ' . \CPB\Utilities\Common\Regex::Match($pattern, $subject). '<br />';
    
    ?>
</p>

