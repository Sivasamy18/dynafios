<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HandlesDatabaseIndexing
{
    public function checkIndexExists(string $tableName, $index): bool
    {
        $pdo = DB::connection()->getPdo();
        $statement = $pdo->prepare("SHOW INDEX FROM $tableName WHERE Key_name = ?");
        $statement->execute([$index]);
        $results = $statement->fetchAll();
        return count($results) > 0;
    }
}