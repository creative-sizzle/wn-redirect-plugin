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

class AddIgnoreQueryParametersToRedirectsTable extends Migration
{
    public function up(): void
    {
        Schema::table('vdlp_redirect_redirects', static function (Blueprint $table) {
            $table->boolean('ignore_query_parameters')
                ->default(false)
                ->after('sort_order');
        });
    }

    public function down(): void
    {
        try {
            Schema::table('vdlp_redirect_redirects', static function (Blueprint $table) {
                $table->dropColumn('ignore_query_parameters');
            });
        } catch (Throwable $e) {
            resolve(LoggerInterface::class)->error(sprintf(
                'Vdlp.Redirect: Unable to drop column `%s` from table `%s`: %s',
                'ignore_query_parameters',
                'vdlp_redirect_redirects',
                $e->getMessage()
            ));
        }
    }
}
