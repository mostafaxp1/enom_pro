<?php 

$limit = 200;
$length = 15;
$chars = 'abcdefghijklmnopqrstuvwxyz1234567890-';
$chars_array = str_split($chars, 1);
for ($i = 0; $i < $limit; $i++) {
    $word = "";
    while($length > strlen($word)) {
        $word .= $chars_array[mt_rand(0, (count($chars_array)-1) )];
        //Valid domains do not start with -
        $word = ltrim($word, '-');        
        //Make 1st char @ the end of alphabet
        $word = ltrim($word, 'abcdefghijklmnop1234567890');        
    }
    $word .= '.com';
    $prefix = 'auto-renew-';
    
    echo $prefix . $word.PHP_EOL;
}