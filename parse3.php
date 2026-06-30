<?php
$xml = simplexml_load_file('test-results.xml');
$errors = [];
foreach($xml->xpath('//testcase') as $testcase) {
    $failure = $testcase->failure ?? $testcase->error ?? null;
    if ($failure) {
        $msg = (string)$failure;
        $lines = explode("\n", $msg);
        $exception_line = null;
        foreach($lines as $line) {
            if (strpos($line, 'Exception:') !== false || strpos($line, 'Error:') !== false || preg_match('/^[a-zA-Z0-9_\\\\]+:/', $line)) {
                $exception_line = trim($line);
                break;
            }
        }
        if (!$exception_line) {
            $exception_line = trim($lines[1] ?? $lines[0]);
        }
        if(!isset($errors[$exception_line])) {
            $errors[$exception_line] = 0;
        }
        $errors[$exception_line]++;
    }
}
arsort($errors);
foreach($errors as $msg => $count) {
    echo $count . " failures of: " . substr($msg, 0, 100) . "...\n";
}
