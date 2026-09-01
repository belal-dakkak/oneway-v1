<?php

namespace App\Models;

use App\Mail\VerifyDeleteYourAccount;
use App\Mail\VerifyYourAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;


    const ROLE_ADMIN = 1;
    const ROLE_WAREHOUSE = 2;
    const ROLE_SHOP = 3;
    const ROLE_CLIENT = 4;
    const ROLE_MERCHANT = 5;
    const ROLE_SHIPPER = 6;

    const COUNTRY_LB = 1;
    const COUNTRY_UAE = 2;
    const COUNTRY_BOTH = 3;
    const COUNTRY_ALL = 3;
    const COUNTRY_SYRIA = 4;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'role_id',
        'country_id',
        'status',
        'post_code',
        'token',
        'code',
        'firebase_token',
        'mobile_verified_at',
        'email_verified_at',
        'reset_token',
        'reset_verified',
        'app_notification_status',
        'deleted',
        'enable_tax',
        'tax_ratio',
        'trn'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url', 'full_name', 'colors_count'
    ];

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(UserProduct::class, 'user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function wishList(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withPivot('product_id');
    }

    public function setPasswordAttribute($value)
    {
        if(Str::of($value)->startsWith('$'))
            $this->attributes['password'] = $value;
        else
            $this->attributes['password'] = Hash::make($value);
    }

    public function getFullNameAttribute()
    {
        return "$this->name ($this->phone)";
    }

    public function getColorsCountAttribute()
    {
        if (in_array($this->role_id, [self::ROLE_WAREHOUSE, self::ROLE_SHOP])){
            return $this->products()->sum('stock');
        }
        return 0;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function shippingDetails(): HasMany
    {
        return $this->hasMany(ShippingDetails::class);
    }

    public function generateActivationCode($mobile = null): User
    {
        $token = rand( 100000 , 999999 );
        $message = 'Your activation code is '.$token;
        $data = [
          'name' => $this->name,
          'message' => $message
        ];

        Mail::to([
            'email' => $this->email
        ])->send(new VerifyYourAccount($data));

        $this->code = $token;//$token;
        $this->email_verified_at = null;
        return $this;
    }

    public function activateUserAccount(): User
    {
        $this->update([
            'code' => null,
            'email_verified_at' => Carbon::now()
        ]);
        return $this;
    }

    public function generatePasswordToken()
    {
        $mobile = $this->phone_number;
        $token = rand( 100000 , 999999 );
        $message = __('api.your_reset_code_is').' '.$token;

//        $codeSentError = sendSmartSms($mobile, $message);

//        if ($codeSentError)
//            return $codeSentError;

        $this->reset_token = $token;
        $this->reset_verified = 'no';
        return $this;
    }

    public function checkPasswordCode($token): bool
    {
        return $this->reset_token == $token;
    }

    public function changePassword($password): bool
    {
        if($this->reset_verified == "yes") {
            $this->update([
                'password' => $password,
                'reset_token' => null,
                'reset_verified' => 'no'
            ]);
            return true;
        }
        return false;
    }

    public function toggleNotificationStatus(): User
    {
        $oldStatus = $this->app_notification_status;
        $newStatus = $oldStatus ==  "yes" ? "no" : "yes";
        $this->app_notification_status = $newStatus;
        return $this;
    }

    public function sendNotification($title,$body)
    {
        $userNotification = FcmNotification::query()->create([
            'title' =>$title,
            'message' => $body,
            'user_id' => $this->id
        ]);
        $this->userNotifications()->save($userNotification);
    }

    public function setFirebaseToken($token): User
    {
        $this->firebase_token = $token;
        return $this;
    }

    public function isActive(): bool
    {
        return (bool) $this->email_verified_at;
    }

    public function generateDeleteCode(): User
    {
        $token = rand( 100000 , 999999 );
        $message = 'Your otp code is '.$token;
        $data = [
          'name' => $this->name,
          'message' => $message
        ];

        Mail::to([
            'email' => $this->email
        ])->send(new VerifyDeleteYourAccount($data));

        $this->code = $token;//$token;
        return $this;
    }

    public function isAdmin()
    {
        return $this->role_id == User::ROLE_ADMIN;
    }

}
