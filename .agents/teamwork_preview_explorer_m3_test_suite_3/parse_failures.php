<?php
$lines = file('test_failures.log');
$errors = [];
foreach ($lines as $i => $line) {
    if (strpos($line, 'FAILED') !== false && isset($lines[$i+2])) {
        $err = trim($lines[$i+2]);
        if (strpos($err, '>') === 0 && isset($lines[$i+3])) {
            $err = trim($lines[$i+3]);
        }
        $errors[] = $err;
    }
}
$counts = array_count_values($errors);
arsort($counts);
foreach (array_slice($counts, 0, 30) as $err => $count) {
    echo "$count: $err\n";
}
