<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * The order property types the plugin owns.
 *
 * PlentyONE assigns each property type its own numeric id per system, so the
 * id is not portable - the technical key below is the stable handle instead,
 * and stays untranslated for that reason.
 */
final class OrderPropertyName
{
    /** One of the ProcessState values. */
    public const PROCESS_STATE = 'endereco_process_state';

    /** One of the CheckOutcome values. */
    public const CHECK_OUTCOME = 'endereco_check_outcome';

    /** Whether the returned correction was written to the order. */
    public const CORRECTION_APPLIED = 'endereco_correction_applied';

    /** How often the check was attempted; the circuit breaker reads this. */
    public const ATTEMPTS_COUNT = 'endereco_attempts_count';

    /**
     * The order status before the plugin put it on its start status. Empty
     * means the plugin never touched the status.
     */
    public const STATUS_BEFORE = 'endereco_status_before';

    /** Street - PlentyONE's address1. */
    public const ADDRESS1_ORIGINAL = 'endereco_address1_original';

    public const ADDRESS1_SUGGESTION = 'endereco_address1_suggestion';

    /** House number - PlentyONE's address2. */
    public const ADDRESS2_ORIGINAL = 'endereco_address2_original';

    public const ADDRESS2_SUGGESTION = 'endereco_address2_suggestion';

    /** Additional line - PlentyONE's address3. */
    public const ADDRESS3_ORIGINAL = 'endereco_address3_original';

    public const ADDRESS3_SUGGESTION = 'endereco_address3_suggestion';

    /** Free-form, undefined by PlentyONE - address4. */
    public const ADDRESS4_ORIGINAL = 'endereco_address4_original';

    public const ADDRESS4_SUGGESTION = 'endereco_address4_suggestion';

    public const POSTAL_CODE_ORIGINAL = 'endereco_postal_code_original';

    public const POSTAL_CODE_SUGGESTION = 'endereco_postal_code_suggestion';

    public const TOWN_ORIGINAL = 'endereco_town_original';

    public const TOWN_SUGGESTION = 'endereco_town_suggestion';

    public const SUBDIVISION_ORIGINAL = 'endereco_subdivision_original';

    public const SUBDIVISION_SUGGESTION = 'endereco_subdivision_suggestion';

    /**
     * Creation order, which is also the backend's display order on a
     * fresh install, where every type here is created in one pass by the
     * runOnBuild migration. A type that already exists is left alone,
     * so reordering this list has no effect on a system where some of
     * these types were already created under an older order.
     */
    public const ALL = [
        self::PROCESS_STATE,
        self::CHECK_OUTCOME,
        self::CORRECTION_APPLIED,
        self::ATTEMPTS_COUNT,
        self::STATUS_BEFORE,
        self::ADDRESS1_ORIGINAL,
        self::ADDRESS1_SUGGESTION,
        self::ADDRESS2_ORIGINAL,
        self::ADDRESS2_SUGGESTION,
        self::ADDRESS3_ORIGINAL,
        self::ADDRESS3_SUGGESTION,
        self::ADDRESS4_ORIGINAL,
        self::ADDRESS4_SUGGESTION,
        self::POSTAL_CODE_ORIGINAL,
        self::POSTAL_CODE_SUGGESTION,
        self::TOWN_ORIGINAL,
        self::TOWN_SUGGESTION,
        self::SUBDIVISION_ORIGINAL,
        self::SUBDIVISION_SUGGESTION,
    ];
}
