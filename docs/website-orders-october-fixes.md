# Website orders, notifications and invoice data

## Deployment

- Deploy the PHP code, rebuilt frontend assets, and regenerated Ziggy routes together.
- Run `php artisan migrate --force`: the new nullable `website_orders.trn` stores the customer's TRN for new invoices. Existing rows retain their data and use the linked customer as a fallback.
- Refresh configuration and compiled views with `php artisan config:cache` and `php artisan view:clear`. Restart queue workers with `php artisan queue:restart`.
- Dashboard notifications are persisted independently of email. Keep the `notify` worker and broadcasting service running for email and live delivery; the authenticated dashboard also polls every 15 seconds.

## Stock and payment

Website checkout checks availability but no longer reserves or deducts inventory. Verified card captures do not deduct it either. The staff's manual sale remains responsible for stock movement. Cancellation releases only a recorded, unreleased legacy reservation; no bulk inventory reset is performed.

Card capture accounting and its idempotency keys are preserved. A manual sale is not automatically linked to a website payment by this change; staff must not collect a paid website order again.

The installed Laravel HTTP client does not implement `connectTimeout`. Both Tap charge creation and verification now use the supported `withOptions(['connect_timeout' => 5])`. Mocked gateway tests cover success, rejection, network failure and repeated callbacks. Live credentials and a real customer payment were not exercised.

## Legacy UAE debt review

Generate a **read-only** report outside the public directory:

```sh
php artisan clients:audit-uae-debts --output=storage/app/uae-debt-audit.json
```

Rows marked `proven_aed_label` require a pre-currency-migration USD account, only AED orders, reconciling balances and account ledger, matched historical payments, and one unambiguous stored exchange rate. Mixed currencies, missing ledger evidence, refunds, modern cashbox postings or conflicting values are left for manual review. `base_conversion_matches` is diagnostic and never authorizes conversion.

After reviewing that report, applying it is explicit:

```sh
php artisan clients:audit-uae-debts --apply=storage/app/uae-debt-audit.json
```

The command only processes eligible rows. It checks that the underlying records still match the report, merges into an existing AED account when present, retains payment IDs and amounts, and records a reconciliation marker. Reapplying the same report has no further effect. Orders, inventory and cashbox movements are not modified. No production debt balances were changed as part of development.

The local audit attempt could not connect to the configured MySQL database (`Access denied`). Run the read-only report on an environment with access before reviewing any real accounts.

## Direct receipt limitation

Tests inspect the real print-info endpoint for 1, 2 and 3 distinct products, including original quantities, amounts, model count, names and customer TRN. The bridge still posts JSON to `http://localhost:12354/api/orders`; no fabricated rows or layout fields were added.

The photographed gap between customer details and the item table has **not** been verified as fixed. Correct payloads cannot establish how the local renderer/printer positions content. Compare an actual receipt after deployment; if the gap remains, inspect the bridge's renderer or printer settings. The approved A4 and browser receipt dimensions were not changed.
