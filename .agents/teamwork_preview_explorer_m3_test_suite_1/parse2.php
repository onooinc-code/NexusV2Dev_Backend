<?php
$log = file_get_contents('C:\Users\hedra\.gemini\antigravity\brain\8f2dcd06-516f-49e3-8bda-7688d98315e0\.system_generated\tasks\task-9.log');
$lines = explode("\n", $log);

$errors = [];
foreach ($lines as $line) {
    if (preg_match('/^\s*(?:>   |\s+)(Failed asserting .*|SQLSTATE.*|Call to undefined method .*|Class ".*" not found|Target class .* does not exist|.*Return value must be of type .*|App\\\\Jobs\\\\.*::__construct\(\): Argument #\d+ .*|Mockery\\Exception.*)/', $line, $m)) {
        $err = trim($m[1]);
        // Normalize SQL queries
        $err = preg_replace('/\(Connection:.*$/', '', $err);
        // Normalize failed assertions
        if (str_starts_with($err, 'Failed asserting that')) {
            $err = preg_replace('/Failed asserting that \d+ is identical to \d+\./', 'Failed asserting that HTTP status matches', $err);
        }
        $errors[$err] = ($errors[$err] ?? 0) + 1;
    }
}

arsort($errors);
foreach ($errors as $msg => $count) {
    echo "[$count] $msg\n";
}
