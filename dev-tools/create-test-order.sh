#!/usr/bin/env bash
#
# Logs in and creates a test sales order via the PlentyONE REST API.
#
# Usage: dev-tools/create-test-order.sh [path/to/payload.json]
#
# Known, harmless noise: the response includes an ERROR/CRITICAL log entry
# from Plenty\Modules\Order\Stock\Services\OrderStockService about a failed
# stock reservation ("stock config for variation ... not ..."). The order is
# still created correctly and the plugin's own listeners fire regardless -
# this is a warehouse/stock-service configuration matter unrelated to
# address validation, not worth chasing for a test fixture.

source "$(dirname "$0")/.env"

PAYLOAD_FILE="${1:-$(dirname "$0")/fixtures/sales-order.json}"

TOKEN=$(curl -s -X POST "$PLENTY_BASE_URL/rest/login" \
    -H "Content-Type: application/json" \
    -d "{\"username\":\"$PLENTY_USERNAME\",\"password\":\"$PLENTY_PASSWORD\"}" \
    | grep -oP '"access_token":"\K[^"]+')

curl -i -X POST "$PLENTY_BASE_URL/rest/orders" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d @"$PAYLOAD_FILE"
