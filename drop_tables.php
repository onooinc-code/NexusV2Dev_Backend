<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

Schema::disableForeignKeyConstraints();

$tables = DB::select('SHOW TABLES LIKE "peopleconnect_%"');
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    Schema::dropIfExists($tableName);
    echo "Dropped \$tableName\n";
}

Schema::enableForeignKeyConstraints();
