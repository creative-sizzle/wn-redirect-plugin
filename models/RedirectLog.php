<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Models;

use Winter\Storm\Database\Model;

final class RedirectLog extends Model
{
    public $table = 'creativesizzle_redirect_redirect_logs';

    public $belongsTo = [
        'redirect' => Redirect::class,
    ];

    protected $guarded = [];
}
