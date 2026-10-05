# Website orders, notifications and invoice data

## Deployment

- Deploy the PHP code, rebuilt frontend assets, and regenerated Ziggy routes together.
- Run `php artisan migrate --force`: the new nullable `website_orders.trn` stores the customer's TRN for new invoices. Existing rows retain their data and use the linked customer as a fallback.
- Refresh configuration and compiled views with `php artisan config:cache` and `php artisan view:clear`. Restart queue workers with `php artisan queue:restart`.
- Dashboard notifications are persisted independently of email. Keep the `notify` worker and broadcasting service running for email and live delivery; the authenticated dashboard also polls every 15 seconds.

## Website order badge

`website_order_count` counts website orders with `STATUS_PENDING` in the viewer's selected country, matching the order list's country scope. It includes historical orders without notifications and ignores search/date filters. Opening the list or reading an individual notification does not lower this count. Unpaid, ongoing, delivered and failed orders are excluded; returning an order to pending includes it again.

Saving a status from the list or detail screen requests a fresh notification summary immediately. A status save during an outstanding poll queues another request and suppresses the stale response. Other open sessions update through the existing 15-second polling and visibility refresh. A zero count hides the badge. Notification IDs and unread counts remain separate for ringtone deduplication and individual read state; opening the list no longer marks other employees' notifications as read.

Deploy the rebuilt frontend assets together with the PHP changes. This change needs no migration or route regeneration.

## Stock and payment

Website checkout checks availability but no longer reserves or deducts inventory. Verified card captures do not deduct it either. The staff's manual sale remains responsible for stock movement. Cancellation releases only a recorded, unreleased legacy reservation; no bulk inventory reset is performed.

Card capture accounting and its idempotency keys are preserved. A manual sale is not automatically linked to a website payment by this change; staff must not collect a paid website order again.

The installed Laravel HTTP client does not implement `connectTimeout`. Both Tap charge creation and verification now use the supported `withOptions(['connect_timeout' => 5])`. Mocked gateway tests cover success, rejection, network failure and repeated callbacks. Live credentials and a real customer payment were not exercised.

## UAE currency conversion (superseding procedure)

The latest approved scope converts all proven UAE USD transaction amounts and history to AED at **3.675**, while retaining internal USD base prices. It replaces the earlier label-only and remaining-balance-only instructions. Do not run `clients:correct-uae-currency-labels --apply` or `clients:convert-uae-debt-balances --apply` for this deployment.

Follow [the UAE finance conversion guide](uae-finance-conversion.md) for the required migration, signed private report, conflict review, backup, paused writers, atomic application and verification. The current command is `finance:convert-uae-to-aed`; its default mode only saves a preview. Application uses `--apply=<report>` and requires maintenance mode. Live database conversion has not been performed from this workspace.

## Direct receipt compact-layout contract

Tests inspect the real print-info endpoint for 1, 2 and 3 distinct products, including original quantities, amounts, model count, names and customer TRN. The payload sent to `http://localhost:12354/api/orders` now includes the exact `products_count` and a `compact_layout` boolean. It is `true` for one or two real product rows and `false` for zero or at least three rows. No fabricated rows are added.

The local renderer must place the item table immediately after the customer and seller details when `compact_layout` is `true`. It must not reserve a fixed minimum height for the product area in either mode. Older renderer versions can ignore the new field, but the photographed gap will remain until the local program implements this contract. The approved A4 and browser receipt dimensions are unchanged.
