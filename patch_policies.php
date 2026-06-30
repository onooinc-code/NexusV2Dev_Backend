<?php

$dir = __DIR__ . '/app/Policies';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Cast $user->is_admin to (bool)
    $content = preg_replace('/return\s+\$user->is_admin;/', 'return (bool) $user->is_admin;', $content);
    
    // Cast $user->is_super_admin to (bool)
    $content = preg_replace('/return\s+\$user->is_super_admin(\s*\?\?\s*false)?;/', 'return (bool) ($user->is_super_admin ?? false);', $content);
    
    file_put_contents($file, $content);
}

echo "Policies patched successfully.\n";
