<?php

/** @noinspection PhpUnused */
/** @noinspection AutoloadingIssuesInspection */

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Updates;

use Psr\Log\LoggerInterface;
use Schema;
use Throwable;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

class AddDescriptionToRedirectsTable extends Migration
{
    public function up(): void
    {
        Schema::table('vdlp_redirect_redirects', static function (Blueprint $table) {
            $table->string('description')
                ->nullable()
                ->after('system');
        });
    }

    public function down(): void
    {
        try {
            Schema::table('vdlp_redirect_redirects', static function (Blueprint $table) {
                $table->dropColumn('description');
            });
        } catch (Throwable $e) {
            resolve(LoggerInterface::class)->error(sprintf(
                'CreativeSizzle.Redirect: Unable to drop index `%s` from table `%s`: %s',
                'description',
                'vdlp_redirect_redirects',
                $e->getMessage()
            ));
        }
    }
}
