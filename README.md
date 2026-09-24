# EnderecoPlentyOneClient

A PlentyONE plugin that automatically validates order delivery
addresses through the endereco API.

## Use Case

In the backend, an incorrect delivery address doesn't cause trouble
right away - it becomes costly only later: at label creation, on a
return, or when transferring the order into the ERP system. Unlike at
checkout, the customer isn't around anymore to correct the address
themselves.

## Solution

The plugin checks an order's delivery address internationally via the
endereco API - either automatically on arrival, or only when triggered
through a Flow - applies unambiguous corrections itself, and flags
everything else for manual follow-up, without ever touching the
customer's address in the CRM.

## Architecture (Short Version)

Any number of orders can arrive at once in the backend, for example
through an import, which rules out a synchronous check. The check runs
in two stages instead:

1. An order is enqueued for checking and held - the queue itself lives
   in its own database table.
2. A cronjob works through the queue in batches every five minutes and
   checks each order individually via the endereco API.

The result lands directly on the order: as tags, as order properties
(including a way back to the original address), and optionally as a
plain-text comment. See the [user guide](meta/documents/user_guide_de.md)
for details.

## Billing

Billing is per API call, not session-based. A session ID still
accompanies every call as a pure identifier on the order, but it is
not the basis for billing.
