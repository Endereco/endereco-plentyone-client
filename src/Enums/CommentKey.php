<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * Translation keys for the text OrderCommentWriter builds.
 */
final class CommentKey
{
    public const HEADER = 'comment.header';

    public const ORIGINAL_ADDRESS = 'comment.originalAddress';

    /** Followed by the formatted candidate, on the lines after it. */
    public const SUGGESTION = 'comment.suggestion';

    /** :number - one of several candidates, formatted on the lines after it. */
    public const SUGGESTION_NUMBERED = 'comment.suggestionNumbered';

    public const NO_SUGGESTION = 'comment.noSuggestion';

    public const CORRECT = 'comment.correct';

    public const MINOR_CORRECTION = 'comment.minorCorrection';

    public const GAVE_UP = 'comment.gaveUp';

    public const PACKSTATION = 'comment.packstation';

    public const POSTFILIALE = 'comment.postfiliale';

    public const BUILDING_NUMBER_MISSING = 'comment.buildingNumberMissing';

    public const BUILDING_NUMBER_NOT_FOUND = 'comment.buildingNumberNotFound';

    // --- address field labels, one address line each: "label: value" ------

    public const STREET = 'comment.fieldStreet';

    public const HOUSE_NUMBER = 'comment.fieldHouseNumber';

    public const ADDITIONAL_INFO = 'comment.fieldAdditionalInfo';

    public const POSTAL_CODE = 'comment.fieldPostalCode';

    public const TOWN = 'comment.fieldTown';

    public const SUBDIVISION = 'comment.fieldSubdivision';

    /** The value shown for a field the answer carried with no content. */
    public const EMPTY_VALUE = 'comment.emptyValue';
}
