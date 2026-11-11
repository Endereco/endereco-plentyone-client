# Release Notes for EnderecoPlentyOneClient

## v1.0.1 (2026-11-11)

### Changed

- The tags "Address validation pending" and "Address validation failed"
  are now called "Pending address validation" and "Failed address
  validation". The order overview truncates long tag labels, and both old
  names became "Address valid..." - with the distinguishing word first,
  they stay easy to tell apart.
- The plugin declares PHP 8.0 as its minimum version and is therefore
  marked as "PHP 8 compatibility checked by the developer".

### Fixed

- Removed the deprecated `curl_close()` call (deprecated as of PHP 8.5,
  without effect since PHP 8.0). Behavior is unchanged.
- The plugin now reports its actual version to the Endereco service
  instead of 0.1.0.

### TODO

- Deploy the plugin set after the update.
- The plugin creates the new tags itself the first time it needs them.
  It no longer removes the tags with the old names: orders that still
  carry "Address validation pending" at the time of the update keep that
  tag even after the check - remove the old tag from them by hand.
- Once no order carries the old tags anymore, you can delete them.

## v1.0.0 (2026-09-24)

### Added

- Initial release.
- International address validation for order addresses.
- Automatic write-back of safe, unambiguous corrections to street, town,
  postal code, additional address line, and state.
- Automatic order status change after the check, with a configurable
  target status.
- Order tags that show the check's outcome directly in the order overview.
- An order comment with the check's result in plain text, optionally
  disabled.
- The full check result in the order properties, also usable to revert a
  correction.
- An order can be enqueued for checking automatically on arrival.
- Alternatively through a Flow building block that lets you decide
  yourself when a check runs, e.g. only after payment is received, or
  to deliberately re-check an order that was already checked before.

### TODO

- Deploy the plugin set twice after installation - only the second
  deployment makes PlentyONE create the plugin's table and order
  properties.
- Enter your API key under the "endereco interface" configuration tab
  and save it.
- Check that the plugin set is linked to a client - without that link,
  none of the plugin's code runs.
