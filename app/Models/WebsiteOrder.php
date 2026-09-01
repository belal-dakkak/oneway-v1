<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jenssegers\Date\Date;

class WebsiteOrder extends Model
{
    use HasFactory;

    const STATUS_UNPAID = 0;
    const STATUS_PENDING = 1;
    const STATUS_ONGOING = 2;
    const STATUS_DELIVERED = 3;
    const STATUS_FAILED = 4;

    protected $fillable = [
        'barcode', 'buyer_id', 'first_name', 'last_name', 'email', 'phone', 
        'address', 'city', 'building_name', 'flat_number',
        'total_price_before_discount', 'discount', 'total_price', 
        'shipping_fee', 'cod_fee', 'status', 'payment_type', 'curr_type', 'invoice', 
        'notes', 'country_id', 'paid_price', 'remain_price', 'curr_rate'
    ];

    protected $appends = ['date', 'payment_label', 'status_label', 'is_paid'];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WebsiteOrderItem::class);
    }

    public function getDateAttribute()
    {
        Date::setLocale('ar');
        return Date::parse($this->created_at)->timezone(\App\Support\Country::timezone($this->country_id))->format('d-m-Y h:i a');
    }

    public function getPaymentLabelAttribute()
    {
        if ($this->payment_type === 'cod') {
            return 'الدفع عند الاستلام';
        } elseif ($this->payment_type === 'card' || $this->payment_type === 'pay_by_card') {
            return 'دفع إلكتروني';
        }
        return $this->payment_type ?: 'غير محدد';
    }

    /**
     * Returns true if the order was paid by card (electronic payment),
     * false if it is a COD order that has not been manually marked as paid.
     */
    public function getIsPaidAttribute(): bool
    {
        if ($this->payment_type === 'card' || $this->payment_type === 'pay_by_card') {
            return $this->status !== self::STATUS_UNPAID && $this->status !== self::STATUS_FAILED;
        }
        // COD orders are considered paid when remain_price reaches 0
        return $this->remain_price <= 0 && $this->total_price > 0;
    }

    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case self::STATUS_UNPAID:
                return 'بانتظار الدفع';
            case self::STATUS_PENDING:
                return 'قيد الانتظار';
            case self::STATUS_ONGOING:
                return 'جاري التوصيل';
            case self::STATUS_DELIVERED:
                return 'تم التوصيل';
            case self::STATUS_FAILED:
                return 'فشل الدفع';
            default:
                return 'قيد الانتظار';
        }
    }

    /**
     * Dispatch order notifications and emails to admin and client.
     */
    public function dispatchNotifications()
    {
        // Get all users with admin dashboard access (Admin, Warehouse, Shop)
        $adminUsers = User::query()
            ->whereIn('role_id', [User::ROLE_ADMIN, User::ROLE_WAREHOUSE, User::ROLE_SHOP])
            ->where(function ($query) {
                $query->where('role_id', User::ROLE_ADMIN)
                    ->orWhere('country_id', $this->country_id);
            })
            ->get();

        $buyer = $this->buyer;
        $note = __('New Order') . " #{$this->barcode}";

        // Log for debugging
        \Illuminate\Support\Facades\Log::info('Dispatching notifications for website order #' . $this->barcode, [
            'admin_users_count' => $adminUsers->count(),
            'admin_users' => $adminUsers->pluck('id', 'email'),
            'order_id' => $this->id,
        ]);

        // Send notification to all admin users
        foreach ($adminUsers as $adminUser) {
            \Illuminate\Support\Facades\Log::info('Dispatching notification to admin', ['user_id' => $adminUser->id, 'email' => $adminUser->email]);
            \App\Jobs\NotificationOrderJob::dispatch($adminUser, $buyer, $this, $note)->onQueue('notify');

            // Send email to admin/shop users
            try {
                \Illuminate\Support\Facades\Mail::to($adminUser->email)->send(new \App\Mail\NewOrderAdminEmail($this));
                \Illuminate\Support\Facades\Log::info('Admin email sent successfully', ['email' => $adminUser->email]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send admin email', [
                    'email' => $adminUser->email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Send confirmation email to client
        try {
            \Illuminate\Support\Facades\Mail::to($this->email)->send(new \App\Mail\OrderConfirmationEmail($this));
            \Illuminate\Support\Facades\Log::info('Client confirmation email sent successfully', ['email' => $this->email]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send client confirmation email', [
                'email' => $this->email,
                'error' => $e->getMessage()
            ]);
        }
    }
}
