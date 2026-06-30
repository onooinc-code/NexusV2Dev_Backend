<?php
$xml = simplexml_load_file(__DIR__ . '/../../junit.xml');
$errs = [];
$failedTests = [];

foreach ($xml->xpath('//testcase[failure|error]') as $t) {
    $class = (string) $t['class'];
    $name = (string) $t['name'];
    $node = $t->failure ? $t->failure : $t->error;
    $lines = explode("\n", trim((string)$node));
    
    // Line 0 is the test name, line 1 is the exception
    $msg = count($lines) > 1 ? trim($lines[1]) : trim($lines[0]);
    
    // Normalize messages
    if (strpos($msg, 'SQLSTATE') !== false) {
        $msg = preg_replace('/\(Connection:.*?\)/', '', $msg); // remove connection details
        $msg = preg_replace('/values \(.*?\)/', 'values (...)', $msg);
    }
    
    $errs[$msg] = ($errs[$msg] ?? 0) + 1;
    $failedTests[$msg][] = "$class::$name";
}

arsort($errs);
foreach ($errs as $msg => $count) {
    echo "========================================================\n";
    echo "$count instances of:\n$msg\n";
    foreach (array_slice($failedTests[$msg], 0, 3) as $ft) {
        echo "  - $ft\n";
    }
    if ($count > 3) echo "  - ...\n";
}
