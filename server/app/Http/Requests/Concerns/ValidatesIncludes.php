<?php

namespace App\Http\Requests\Concerns;

use Closure;

/**
 * Validates a comma-separated `?with=` query param against a whitelist of
 * relation names, so eager-loading stays scoped and no invalid relation
 * name slips through to the query builder. Shared across modules —
 * every resource with "include when requested" relationships needs this.
 */
trait ValidatesIncludes
{
    protected function includeRule(array $allowed): array
    {
        return ['sometimes', 'string', $this->csvInRule($allowed)];
    }

    protected function csvInRule(array $allowed): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($allowed): void {
            foreach (explode(',', (string) $value) as $part) {
                $part = trim($part);
                if ($part !== '' && ! in_array($part, $allowed, true)) {
                    $fail("The {$attribute} field contains an invalid value: {$part}.");
                }
            }
        };
    }
}
