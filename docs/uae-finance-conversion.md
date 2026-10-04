# UAE transactions in AED

## Scope and deployment order

The approved rate for historical USD amounts is **3.675 AED/USD**. This replaces the earlier label-only and remaining-balance-only procedures. The owner confirmed the remaining-balance conversion has **not** been applied. Do not run either previous apply command as part of this deployment.

New UAE sales, checkout, customer settlements, merchant accounts/payments and expenses use AED. Product cost, wholesale/retail base prices, system order item base prices, and `base_amount` remain USD internally. Input/output conversion happens once. Country follows the transaction/account owner, not the viewer's language. Existing AED transaction amounts are retained. Shared product base prices and other countries are not rewritten.

The currency metadata migration must precede use of the new PHP code. Publish built assets with that code. Keep the site in maintenance until the historical report is reviewed and successfully applied: legacy USD accounts must not receive new settlements during conversion.

## Report generation (no financial writes)

Keep the original `client_debits.sql` export outside `public`, for example `storage/app/client_debits.sql`. This is a query export, **not a restorable table dump**. The command parses supported INSERT data; it never executes the SQL file. The original account IDs, owners, currency and balance prove which currently AED-labelled accounts still contain USD numbers.

```sh
php artisan migrate --force
php artisan finance:convert-uae-to-aed --source=storage/app/client_debits.sql --output=storage/app/uae-finance-preview.json
```

The private JSON report records field-by-field previous/proposed values, evidence, exact unrounded conversion and rounding differences where applicable, source snapshot, conflicts and a signature. The server's `APP_KEY` signs it. Do not edit the report or change `APP_KEY` between preview and application. Existing reports are never overwritten; choose a new output filename for another preview. A report under `public` is rejected.

### PHP CLI memory on Plesk

The snapshot reader fetches 250 records at a time, fingerprints every original field incrementally, and retains only conversion fields for large order/item tables. Inventory rows are fingerprinted without retaining a second inventory copy. It also releases snapshots before revalidation and report writing. This avoids loading whole PDO result sets and constructing a full snapshot JSON string. Account/wallet rows that may be merged are retained in full for the audit. Memory still depends on the number of financial rows and proposed changes; it is not a constant-memory converter.

After deploying the memory fix, regenerate any previous preview because the fingerprint format changed. For larger databases, a bounded CLI-only memory limit can be used from SSH in the project directory:

```sh
/opt/plesk/php/8.0/bin/php -d memory_limit=512M artisan finance:convert-uae-to-aed --source=storage/app/client_debits.sql --output=storage/app/uae-finance-preview-v2.json
```

This does not change the website's PHP memory limit or apply the conversion. If the command fails during preview, no financial updates have been executed. A regression test with more than 128 MiB of stored order metadata passes under a 128 MiB PHP limit and verifies that changes to discarded metadata still invalidate the report.

Without SSH, upload the updated `app/Console/Commands/ConvertUaeFinance.php` and run the following in Plesk's Artisan command interface:

```sh
php artisan finance:convert-uae-to-aed --memory=512 --source=storage/app/client_debits.sql --output=storage/app/uae-finance-preview-v2.json
```

`--memory` accepts an integer in MiB from 128 through 2048. It raises the current process's limit before loading the snapshot and prints the effective limit. It never lowers an existing higher/unlimited limit and changes no PHP configuration file. It does not imply `--apply`. If PHP disallows `ini_set`, the command stops with a hosting-administrator message before reading financial data. The same option can be used for a later reviewed `--apply`, with the maintenance requirement still enforced. Actual memory availability remains subject to hosting limits.

Review all conflicts before application. Examples:

- Missing/changed source account, mismatched customer/shop, prior balance-only correction.
- Unsupported currency, mixed ledger/payment rates, or amounts already converted independently.
- Cashbox credit/debit totals not supported by recorded movements (including an undocumented opening balance).
- Missing financial sources, source amounts/owners disagreeing with cash movements, cross-country merchant accounts or transfers.

These cases block the **whole** batch. Do not fix them by editing the signed JSON, assuming an exchange rate or deleting evidence. Investigate the database records and obtain their source evidence, then produce another report.

Conflict entries include a restricted `context` with the current amounts/rates, linked account/order data and owner IDs/countries. Free-text notes, names and contact details are excluded from this diagnostic context. Cashbox conflicts include recorded credit/debit sums, movement count and unmatched differences. For an owner with unreconciled cashbox history, the preview omits all wallet reset/merge/movement proposals for that owner; an empty movement history never proposes resetting a historical balance to zero. The command prints grouped conflict counts, with all IDs/details available in the private JSON. An exit code of 1 together with a saved report and a conflict summary means review is required, not that financial changes were applied.

MySQL returns DECIMAL metadata as strings. The legacy USD evidence check compares zero numerically, so `base_amount = "0.0000"` with `exchange_rate = "1.000000"` is treated like SQLite's numeric zero/one. Previous builds incorrectly used PHP `empty()` on the decimal zero string and reported false mixed-currency conflicts. Nonzero mismatched base amounts and non-USD rates still block conversion. Regenerate previews after deploying this fix; do not edit old reports to remove conflicts. Changed-source-account conflicts also include restricted account log/payment history to investigate later activity without multiplying new AED payments as if they were old USD.

## Later AED refunds against a label-corrected USD account

A changed source balance can be reconciled only when the complete account history proves this specific case: positive legacy order logs sum exactly to the original USD snapshot; subsequent AED refund logs explain the entire difference to the current balance; and there are no account payments or previous balance-only conversion. Each refund must match the customer, account, original order, actual refund amount, AED currency and 3.675 rate/base amount. Duplicate refund sources, missing links and other changes still block application.

The proposal converts the snapshot and legacy logs once, preserves the actual later AED refunds, and records its calculation in the signed report's `reconciliation` field. For example, an original USD balance of 97.96 followed by two verified AED refunds of 90 produces `97.96 × 3.675 = 360.00`, then `360.00 − 180.00 = 180.00 AED`. The current mixed figure of -82.04 must not itself be multiplied. No refund, payment or cash movement is created by this reconciliation. Production links must pass the checks; the example alone is not proof of the live records.

The owner confirmed that UAE/Lebanon merchant transactions and Lebanese closures into the admin wallet are genuine cross-country activity. Do not change user countries to clear those conflicts. The current conversion still blocks these cases until their UAE portion and settlement currency are established from source records. A mixed admin wallet cannot be converted using only its owner's current country. Historical wallet balances without matching movements also remain unresolved; an empty ledger does not establish a zero balance.

After uploading the updated `app/Services/UaeFinanceConversion.php`, use the following in Plesk's Artisan command box to generate a new preview (no financial writes):

```text
finance:convert-uae-to-aed --memory=512 --source=storage/app/client_debits.sql --output=storage/app/uae-finance-preview-v6.json
```

This preview verifies the refund links on the server. It is not approval to apply the conversion; the remaining cross-country, wallet and identity conflicts still require reconciliation.

## Applying the reviewed report

Take a complete database backup and verify its restore procedure before conversion. Stop scheduled financial imports and pause/drain queue workers so no background payment or inventory job can write. `queue:restart` alone is not a pause: Supervisor can restart workers. Let in-flight HTTP requests finish. The command requires Laravel maintenance mode for application; this is a whole-application pause, not a per-country middleware switch.

```sh
php artisan down
# Pause workers, scheduler and other writers using the deployment's process manager.
# Generate the final preview again while writes are paused if the earlier data changed.
php artisan finance:convert-uae-to-aed --source=storage/app/client_debits.sql --output=storage/app/uae-finance-final.json
# Review this final report and keep a copy with the database backup.
php artisan finance:convert-uae-to-aed --apply=storage/app/uae-finance-final.json
```

Application rechecks the signature, conflicts, every proposed change and the full source-data fingerprint under row locks. Any change since preview aborts; generate/review another report. All updates, account merges, movement relinking and journal rows share one database transaction. No sale/payment/stock posting code is invoked. No new cash movement, payment or stock deduction is created.

`finance_conversion_batches` stores the report; `finance_conversion_changes` retains each original and replacement value, including full deleted source rows when accounts merge. A unique batch key and unique record/field journal keys prevent duplicate conversion. Reapplying the same report returns `already_applied`. Preserve both audit tables. This is a one-time migration, not a recurring currency exchange job. It does not infer future undocumented USD data as AED.

Matched USD/AED customer and merchant accounts merge by identical creditor/debtor. Wallets merge only within the same UAE owner. Child references move to the retained account, and historical movement IDs, source IDs, idempotency keys and transfer groups remain intact. Cashbox totals and chronological running balances are rebuilt from recorded converted movements. Local AED expense amounts stay unchanged; their old USD cashbox postings are reconciled against the original expense.

Invoice total/net/VAT/paid/remaining amounts and transaction-currency item/refund fields are converted. Original sold quantity, stored base sale price and inventory remain intact. Actual gateway charge IDs, original gateway currency/amount/rate remain evidence of the charge actually made; these external settlement facts are not rewritten as if the bank charged AED.

## Verification and resume

Compare representative quick/wholesale/website invoices, partial payments/refunds, customer/merchant statements and wallet balances with the report and backup. Check that non-UAE records and product base prices are unchanged and linked payments/cash movements agree. Review audit rounding differences. Test a new UAE sale, refund, expense, merchant payment and sales closure in a staging copy first.

```sh
php artisan config:cache
php artisan view:clear
php artisan queue:restart
# Resume the paused workers, scheduler and other writers only after verification.
php artisan up
```

Build assets with `npm run production` during packaging, then deploy `public/js`, associated compiled assets and `public/mix-manifest.json` together. No route regeneration is needed for this currency change. Do not use a migration rollback to undo a committed financial conversion: journal data is intentionally retained. Recovery requires the reviewed database backup and matching application version, with all writers still stopped.

## Verification limits

Automated tests use isolated SQLite and include two concurrent PHP processes. They cover repeated application, stale/tampered reports, rollback after a mid-batch failure, existing AED accounts/wallets, USD history, negative balances, original sale quantities after refund, shared base prices, external charge evidence and new AED expense/merchant cash postings. These do not establish that production MySQL data is conflict-free. A successful reviewed production report, verified backup and post-application comparisons are still required. No production conversion has been executed from this workspace.
