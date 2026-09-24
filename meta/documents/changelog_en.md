# EnderecoPlentyOneClient

## Version 1.0.0 - 2026-09-24

### Added

* Initial release.
* International address validation for order addresses.
* Automatic write-back of safe, unambiguous corrections to street, town,
  postal code, additional address line, and state.
* Automatic order status change after the check, with a configurable
  target status.
* Order tags that show the check's outcome directly in the order overview.
* An order comment with the check's result in plain text, optionally
  disabled.
* The full check result in the order properties, also usable to revert a
  correction.
* An order can be enqueued for checking automatically on arrival.
* Alternatively through a Flow building block that lets you decide
  yourself when a check runs, e.g. only after payment is received, or
  to deliberately re-check an order that was already checked before.

### TODO

* Deploy the plugin set twice after installation - only the second
  deployment makes PlentyONE create the plugin's table and order
  properties.
* Enter your API key under the "endereco interface" configuration tab
  and save it.
* Check that the plugin set is linked to a client - without that link,
  none of the plugin's code runs.
