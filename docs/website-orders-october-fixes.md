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

## Legacy UAE customer account currency labels

The owner confirmed that existing UAE customer account amounts are already dirham amounts. Correct their USD labels to AED without multiplying, dividing, rebuilding balances from historical notes, or changing payments and exchange rates. This supersedes the earlier reconciliation workflow for this correction.

After deploying the code, preview the correction:

```sh
php artisan clients:correct-uae-currency-labels
```

Apply it on the server:

```sh
php artisan clients:correct-uae-currency-labels --apply
```

The transaction changes only `client_debits.currency_code` and the USD log labels belonging to those accounts. UAE scope comes from the creditor/shop's country, including zero and negative balances. For example, `32.65 USD` becomes `32.65 AED`. Existing amounts, rates, timestamps, payments, orders, refunds, inventory and cashbox entries remain unchanged. Repeating the command makes no further changes.

If a customer already has both USD and AED accounts with the same shop, the command reports the conflicting shop/customer pair and makes no changes; it does not silently merge accounts. No production correction was executed during development.

## Earlier diagnostic report

Generate a **read-only** report outside the public directory:

```sh
php artisan clients:audit-uae-debts --output=storage/app/uae-debt-audit.json
```

Rows marked `proven_aed_label` require a pre-currency-migration USD account, only AED orders, reconciling balances and account ledger, matched historical payments, and one unambiguous stored exchange rate. Mixed currencies, missing ledger evidence, refunds, modern cashbox postings or conflicting values are left for manual review. `base_conversion_matches` is diagnostic and never authorizes conversion.

The old audit's apply workflow is not the procedure for the owner's label-only correction. Use `clients:correct-uae-currency-labels --apply` above instead.

The local database connection was unavailable (`Access denied`); the correction was verified using isolated test data.

## Direct receipt limitation

Tests inspect the real print-info endpoint for 1, 2 and 3 distinct products, including original quantities, amounts, model count, names and customer TRN. The bridge still posts JSON to `http://localhost:12354/api/orders`; no fabricated rows or layout fields were added.

The photographed gap between customer details and the item table has **not** been verified as fixed. Correct payloads cannot establish how the local renderer/printer positions content. Compare an actual receipt after deployment; if the gap remains, inspect the bridge's renderer or printer settings. The approved A4 and browser receipt dimensions were not changed.
