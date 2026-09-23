<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * Translation keys for the text OrderCommentWriter builds.
 */
final class CommentKey
{
    public const HEADER = 'comment.header';

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
}
