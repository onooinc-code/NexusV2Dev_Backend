<?php
$xml = simplexml_load_file('test-results.xml');
foreach($xml->xpath('//testcase') as $t) {
    $f = $t->failure ?? $t->error;
    if ($f && strpos((string)$f, 'received 405') !== false) {
        echo $t['class'] . '::' . $t['name'] . "\n";
    }
}
