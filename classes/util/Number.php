<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes\Util;

class Number
{
    /**
     * @param  float|int  $value
     * @param  int  $style Define the type of the formatter.
     * @return string
     */
    public static function format($value, int $style = \NumberFormatter::DEFAULT_STYLE): string
    {
        return (new \NumberFormatter(app()->getLocale(), $style))
            ->format($value);
    }

    /**
     * @param  float|int  $value
     * @return string
     */
    public static function formatDecimal($value): string
    {
        return static::format($value, \NumberFormatter::DECIMAL);
    }
}
