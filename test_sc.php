<?php
function outer() {
    $x = 10;
    $arr = [1,2,3];
    $fn = function() use ($arr) {
        echo "x inside closure: " . ($x ?? 'UNDEFINED') . "\n";
        echo "arr inside closure: " . json_encode($arr) . "\n";
    };
    $fn();
}
outer();
