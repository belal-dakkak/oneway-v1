<?php

namespace App\Services;

use App\Models\CountryCommerceSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class WebsiteInventoryService
{
    public function stockUserId(int $countryId): ?int
    {
        $userId = CountryCommerceSetting::forCountry($countryId)->website_stock_user_id;
        if (!$userId) {
            return null;
        }

        return User::query()
            ->whereKey($userId)
            ->where('country_id', $countryId)
            ->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])
            ->exists() ? (int) $userId : null;
    }

    public function requireStockUserId(int $countryId): int
    {
        $userId = $this->stockUserId($countryId);
        if (!$userId) {
            throw new InvalidArgumentException('Online ordering is temporarily unavailable because its fulfilment location is not configured.');
        }

        return $userId;
    }

    public function constrain(Builder $query, int $countryId, bool $availableOnly = true): Builder
    {
        $userId = $this->stockUserId($countryId);
        $query->where('country_id', $countryId);
        if (!$userId) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('user_id', $userId);
        if ($availableOnly) {
            $query->where('stock', '>', 0);
        }

        return $query;
    }
}
