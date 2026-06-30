<?php
$xml = simplexml_load_file('test-results.xml');
if(!$xml) { echo 'Not ready'; exit(1); }
$errors = [];
$tests = [];
foreach($xml->xpath('//testcase') as $testcase) {
    $failure = $testcase->failure ?? $testcase->error ?? null;
    if ($failure) {
        $msg = (string)$failure;
        $lines = explode("\n", $msg);
        $first_line = trim($lines[0]);
        if(!isset($errors[$first_line])) {
            $errors[$first_line] = [];
        }
        $errors[$first_line][] = (string)$testcase['class'] . '::' . (string)$testcase['name'];
    }
}
arsort($errors);
foreach($errors as $msg => $failed_tests) {
    echo count($failed_tests) . ": " . $msg . "\n";
    echo "  Example: " . $failed_tests[0] . "\n";
}
