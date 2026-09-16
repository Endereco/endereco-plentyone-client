<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * The tags the plugin attaches to orders.
 *
 * The value is the translation key, not the label: the label is what the
 * merchant reads, so it is translated per language.
 */
final class OrderTag
{
    public const ADDRESS_VALIDATION_PENDING = 'tag.addressValidationPending';

    public const ADDRESS_VALID = 'tag.addressValid';

    public const ADDRESS_CORRECTED = 'tag.addressCorrected';

    public const ADDRESS_ISSUE = 'tag.addressIssue';

    /** The circuit breaker gave up - the service could not be reached. */
    public const ADDRESS_VALIDATION_FAILED = 'tag.addressValidationFailed';
}
