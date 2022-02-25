<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Models;

use Backend\Models\ExportModel;

final class RedirectExport extends ExportModel
{
    public $table = 'vdlp_redirect_redirects';

    public function exportData($columns, $sessionKey = null)
    {
        return static::make()
            ->get()
            ->toArray();
    }
}
