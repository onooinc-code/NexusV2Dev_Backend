<?php
$log = file_get_contents('C:\Users\hedra\.gemini\antigravity\brain\8f2dcd06-516f-49e3-8bda-7688d98315e0\.system_generated\tasks\task-9.log');
$lines = explode("\n", $log);

$errors = [];
$currentError = null;
foreach ($lines as $line) {
    if (preg_match('/^>   (Failed asserting .*|SQLSTATE.*|Call to undefined method .*|Class ".*" not found|Target class .* does not exist|Return value must be of type .*|Argument #\d+ .*|Mockery\\Exception.*)/', $line, $m)) {
        $err = trim($m[1]);
        $errors[$err] = ($errors[$err] ?? 0) + 1;
    } elseif (preg_match('/^\s+(SQLSTATE.*|Call to undefined method .*|Class ".*" not found|Target class .* does not exist|App\\\\.*::.*: Return value must be of type .*|App\\\\Jobs\\\\.*::__construct\(\): Argument #\d+ .*|Mockery\\Exception.*)/', $line, $m)) {
        $err = trim($m[1]);
        $errors[$err] = ($errors[$err] ?? 0) + 1;
    }
}

arsort($errors);
foreach ($errors as $msg => $count) {
    echo "[$count] $msg\n";
}
