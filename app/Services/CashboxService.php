<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CashboxService
{
    private $currencies;

    public function __construct(CurrencyService $currencies)
    {
        $this->currencies = $currencies;
    }

    /** Read the same persisted balances for the branch and administrator views. */
    public function balancesForUser(int $userId): array
    {
        return Wallet::query()->where('user_id', $userId)->get()
            ->mapWithKeys(function (Wallet $wallet) {
                $code = strtoupper((string) ($wallet->currency_code ?: 'USD'));
                return [$code => [
                    'currency' => $code,
                    'credit' => (float) $wallet->credit,
                    'debit' => (float) $wallet->debit,
                    'balance' => (float) $wallet->credit - (float) $wallet->debit,
                ]];
            })->all();
    }

    public function credit(
        int $userId,
        float $amount,
        string $currency,
        string $idempotencyKey,
        array $context = []
    ): WalletMovement {
        return $this->post($userId, $amount, $currency, 'credit', $idempotencyKey, $context);
    }

    public function debit(
        int $userId,
        float $amount,
        string $currency,
        string $idempotencyKey,
        array $context = []
    ): WalletMovement {
        return $this->post($userId, $amount, $currency, 'debit', $idempotencyKey, $context);
    }

    public function post(
        int $userId,
        float $amount,
        string $currency,
        string $direction,
        string $idempotencyKey,
        array $context = []
    ): WalletMovement {
        $currency = strtoupper($currency);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Cashbox amount must be greater than zero.');
        }
        if (!in_array($direction, ['credit', 'debit'], true)) {
            throw new InvalidArgumentException('Invalid cashbox movement direction.');
        }

        $existing = WalletMovement::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($userId, $amount, $currency, $direction, $idempotencyKey, $context) {
            // Serialize creation and posting for all cashboxes owned by the same
            // user. This also prevents two first-time SYP movements from creating
            // duplicate wallets concurrently.
            User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

            $existing = WalletMovement::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $wallet = Wallet::query()
                ->where('user_id', $userId)
                ->where('currency_code', $currency)
                ->lockForUpdate()
                ->first();
            if (!$wallet) {
                $wallet = Wallet::query()->create([
                    'user_id' => $userId,
                    'currency_code' => $currency,
                    'credit' => 0,
                    'debit' => 0,
                ]);
                $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            $rounded = $this->currencies->round($amount, $currency);
            $rate = isset($context['exchange_rate'])
                ? (float) $context['exchange_rate']
                : $this->currencies->rate($currency);
            if ($rate <= 0) {
                throw new InvalidArgumentException('Exchange rate must be greater than zero.');
            }

            $wallet->{$direction} = (float) $wallet->{$direction} + $rounded;
            $wallet->save();
            $balance = (float) $wallet->credit - (float) $wallet->debit;

            return WalletMovement::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $userId,
                'currency_code' => $currency,
                'direction' => $direction,
                'amount' => $rounded,
                'exchange_rate' => $rate,
                'base_amount' => $this->currencies->toUsdAtRate($rounded, $rate),
                'balance_after' => $balance,
                'payment_method' => $context['payment_method'] ?? null,
                'source_type' => $context['source_type'] ?? null,
                'source_id' => $context['source_id'] ?? null,
                'exchange_group' => $context['exchange_group'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'note' => $context['note'] ?? null,
            ]);
        }, 3);
    }

    public function exchange(int $userId, string $from, string $to, float $amount, float $rate, ?string $note = null): array
    {
        $from = strtoupper($from);
        $to = strtoupper($to);
        if ($from === $to || $amount <= 0 || $rate <= 0) {
            throw new InvalidArgumentException('Invalid currency exchange request.');
        }

        return DB::transaction(function () use ($userId, $from, $to, $amount, $rate, $note) {
            // Keep the same lock order used by post() to avoid a wallet/user
            // deadlock when a sale and an exchange arrive together.
            User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
            $source = Wallet::query()
                ->where('user_id', $userId)
                ->where('currency_code', $from)
                ->lockForUpdate()
                ->first();
            $available = $source ? (float) $source->credit - (float) $source->debit : 0;
            if ($available + 0.0001 < $amount) {
                throw new InvalidArgumentException('Insufficient cashbox balance.');
            }

            $group = (string) Str::uuid();
            $usd = $from === 'USD' ? $amount : $this->currencies->toUsdAtRate($amount, $rate);
            $received = $to === 'USD' ? $usd : $this->currencies->fromUsdAtRate($usd, $rate, $to);
            $context = ['exchange_rate' => $rate, 'exchange_group' => $group, 'payment_method' => 'exchange', 'note' => $note];

            $out = $this->debit($userId, $amount, $from, "exchange:{$group}:out", $context);
            $in = $this->credit($userId, $received, $to, "exchange:{$group}:in", $context);

            return [$out, $in];
        }, 3);
    }
}
