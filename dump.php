<?php
$xml = simplexml_load_file('test-results.xml');
$f = $xml->xpath('//testcase/failure | //testcase/error')[0];
var_dump((string)$f, (string)$f['type']);
