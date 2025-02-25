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

class AddRedirectTimestampCrawlerIndexOnClientsTable extends Migration
{
    public function up(): void
    {
        Schema::table('vdlp_redirect_clients', static function (Blueprint $table) {
            $table->index(
                [
                    'redirect_id',
                    'timestamp',
                    'crawler',
                ],
                'redirect_timestamp_crawler'
            );
        });
    }

    public function down(): void
    {
        try {
            Schema::table('vdlp_redirect_clients', static function (Blueprint $table) {
                $table->dropIndex('redirect_timestamp_crawler');
            });
        } catch (Throwable $e) {
            resolve(LoggerInterface::class)->error(sprintf(
                'CreativeSizzle.Redirect: Unable to drop index `%s` from table `%s`: %s',
                'redirect_timestamp_crawler',
                'vdlp_redirect_clients',
                $e->getMessage()
            ));
        }
    }
}
