<?php
$files = glob('app/Models/*.php');
foreach($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'extends Model') !== false && strpos($content, 'HasFactory') === false) {
        $replacement = "{\n    use \Illuminate\Database\Eloquent\Factories\HasFactory;\n";
        $content = preg_replace('/\{\s*/', $replacement, $content, 1);
        file_put_contents($file, $content);
        echo "Added HasFactory to: $file\n";
    }
}
