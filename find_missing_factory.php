<?php
$files = glob('app/Models/*.php');
foreach($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'extends Model') !== false && strpos($content, 'HasFactory') === false) {
        echo "Missing HasFactory: $file\n";
    }
}
