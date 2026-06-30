<?php

$files = glob('tests/Feature/HedraSoul/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = str_replace('/api/hedrasoul', '/api/v1/hedrasoul', $content);
    file_put_contents($file, $content);
    echo "Updated $file\n";
}
