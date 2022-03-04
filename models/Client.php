<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Models;

use Winter\Storm\Database\Model;

final class Client extends Model
{
    public $table = 'creativesizzle_redirect_clients';

    public $belongsTo = [
        'redirect' => Redirect::class,
    ];

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'timestamp' => 'datetime',
    ];
}
