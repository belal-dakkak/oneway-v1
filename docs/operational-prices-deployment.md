# Operational product prices

Product prices entered in the admin are stored in `operational_prices` in the
operator's currency. The legacy product columns remain USD base values with
four decimal places for compatibility.

## Plesk deployment

In Plesk's **Artisan Command** field, enter each command without the word
`artisan`, in this order:

1. `down --retry=60`
2. `migrate --force`
3. `catalog:backfill-operational-prices --chunk=200`
4. `optimize:clear`
5. `up`

The backfill freezes the currently displayed UAE value to two decimal places.
It cannot reconstruct an older value that was already changed by repeated
currency conversion. Open each known affected product once after deployment,
enter its correct AED price, and save it. Future exchange rate changes will not
change that saved AED price.

If the migration has not run, product create and update operations stop with a
clear error before changing the product, its colors, or its USD base values.

