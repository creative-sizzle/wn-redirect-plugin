<?php

namespace CreativeSizzle\Redirect\Updates;

use Illuminate\Support\Facades\DB;
use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

class RenameTables extends Migration
{
    public const TABLES = [
        'categories',
        'clients',
        'redirect_logs',
        'redirects',
    ];

    public function up()
    {
        foreach (self::TABLES as $tableName) {
            $from = 'vdlp_redirect_' . $tableName;
            $to = 'creativesizzle_redirect_' . $tableName;

            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }

            $this->updateIndexNames($from, $to, $to);
        }

        DB::table('system_settings')
            ->where('item', 'vdlp_redirect_settings')
            ->update([
                'item' => 'creativesizzle_redirect_settings',
            ]);
    }

    public function down()
    {
        foreach (self::TABLES as $tableName) {
            $from = 'creativesizzle_redirect_' . $tableName;
            $to = 'vdlp_redirect_' . $tableName;

            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }

            $this->updateIndexNames($from, $to, $from);
        }

        DB::table('system_settings')
            ->where('item', 'creativesizzle_redirect_settings')
            ->update([
                'item' => 'vdlp_redirect_settings',
            ]);
    }

    protected function updateIndexNames(string $from, string $to, string $tableName): void
    {
        $manager = Schema::getConnection()->getDoctrineSchemaManager();

        $table = $manager->listTableDetails($tableName);

        foreach ($table->getIndexes() as $index) {
            if ($index->isPrimary()) {
                continue;
            }

            $old = $index->getName();
            $new = str_replace($from, $to, $old);

            $table->renameIndex($old, $new);
        }
    }
}
