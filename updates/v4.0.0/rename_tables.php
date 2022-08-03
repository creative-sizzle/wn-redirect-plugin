<?php

namespace CreativeSizzle\Redirect\Updates;

use Backend\Models\User;
use Backend\Models\UserRole;
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
            $from = 'vdlp_redirect_'.$tableName;
            $to = 'creativesizzle_redirect_'.$tableName;

            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }

            $this->updateIndexNames($from, $to, $to);
        }

        // Migrate settings
        DB::table('system_settings')
            ->where('item', 'vdlp_redirect_settings')
            ->update([
                'item' => 'creativesizzle_redirect_settings',
            ]);

        // Migrate system request logs columns
        if (Schema::hasColumn('system_request_logs', 'vdlp_redirect_redirect_id')) {
            Schema::table('system_request_logs', function ($table) {
                $table->renameColumn('vdlp_redirect_redirect_id', 'creativesizzle_redirect_redirect_id');
                $table->renameIndex('vdlp_redirect_request_log', 'creativesizzle_redirect_request_log');
            });
        }

        // Migrate backend user permissions
        $this->migrateUserPermissions();

        // Migrate backend user role permissions
        $this->migrateRolePermissions();
    }

    public function down()
    {
        foreach (self::TABLES as $tableName) {
            $from = 'creativesizzle_redirect_'.$tableName;
            $to = 'vdlp_redirect_'.$tableName;

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

        // Migrate system request logs columns
        if (Schema::hasColumn('system_request_logs', 'creativesizzle_redirect_redirect_id')) {
            Schema::table('system_request_logs', function ($table) {
                $table->renameColumn('creativesizzle_redirect_redirect_id', 'vdlp_redirect_redirect_id');
                $table->renameIndex('creativesizzle_redirect_request_log', 'vdlp_redirect_request_log');
            });
        }
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

    private function migrateUserPermissions()
    {
        $users = User::query()
            ->where('permissions', '<>', '')
            ->get();

        foreach ($users as $user) {
            $permissions = $user->permissions ?: [];
            if (! isset($permissions['vdlp.redirect.access_redirects'])) {
                continue;
            }

            $user->setPermissionsAttribute(json_encode($permissions + [
                'creativesizzle.redirect.access_redirects' => $permissions['vdlp.redirect.access_redirects'],
            ]));

            $user->save();
        }
    }

    private function migrateRolePermissions()
    {
        $roles = UserRole::query()
            ->where('permissions', '<>', '')
            ->get();

        foreach ($roles as $role) {
            $permissions = $role->permissions ?: [];
            if (! isset($permissions['vdlp.redirect.access_redirects'])) {
                continue;
            }

            $role->setPermissionsAttribute(json_encode($permissions + [
                'creativesizzle.redirect.access_redirects' => $permissions['vdlp.redirect.access_redirects'],
            ]));

            $role->save();
        }
    }
}
