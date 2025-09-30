<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DeleteNullSemesterIdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $database = DB::getDatabaseName();

        $tables = DB::select("
            SELECT table_name 
            FROM information_schema.columns 
            WHERE column_name = 'semester_id' 
                AND table_schema = ?
        ", [$database]);

        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;

            // Safeguard: check if column really exists
            if (Schema::hasColumn($tableName, 'semester_id')) {
                $deleted = DB::table($tableName)
                    ->whereNull('semester_id')
                    ->delete();

                $this->command->info("Deleted {$deleted} rows from {$tableName}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
