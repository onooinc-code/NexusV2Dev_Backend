<?php
$xml = simplexml_load_file('test-results.xml');
$errors = [];
foreach($xml->xpath('//testcase') as $testcase) {
    $failure = $testcase->failure ?? $testcase->error ?? null;
    if ($failure) {
        $msg = (string)$failure['message'];
        $lines = explode("\n", $msg);
        $first_line = trim($lines[0]);
        if(!isset($errors[$first_line])) {
            $errors[$first_line] = 0;
        }
        $errors[$first_line]++;
    }
}
arsort($errors);
foreach($errors as $msg => $count) {
    echo $count . " failures of: " . $msg . "\n";
}
