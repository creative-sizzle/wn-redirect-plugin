<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Classes\Exceptions;

use CreativeSizzle\Redirect\Classes\RedirectRule;
use RuntimeException;

final class NoMatchForRule extends RuntimeException
{
    public static function withRedirectRule(
        RedirectRule $redirectRule,
        string $requestPath,
        ?string $scheme = null
    ): self {
        return new self(sprintf(
            'No match found for rule %d (request path: %s, schema: %s).',
            $redirectRule->getId(),
            $requestPath,
            $scheme ?: '(no scheme)'
        ));
    }
}
