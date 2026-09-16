<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Exceptions;

/**
 * The address check could not be carried out: no network, a timeout, an
 * HTTP code other than 200, a body that is not JSON, a JSON-RPC error, a
 * wrong/missing API key, or an answer whose status codes match no known case
 * (see EnderecoResultMapper).
 *
 * An order whose check fails this way keeps everything it has and only gets
 * its attempt counter raised, for the next cron run to try again.
 */
final class AddressCheckFailed extends \Exception
{
}
