<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    // @use HasFactory<UserFactory>
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STAFF = 'staff';

    public const ROLE_DRIVER = 'driver';

    public const ROLE_CUSTOMER = 'customer';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'mobile',
        'role',
        'house_no',
        'street',
        'subdivision',
        'barangay',
        'city',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
        ];
    }

    // internal accounts are created by admin and keyed off the email domain
    public static function resolveRole(string $email): string
    {
        $domain = Str::after(strtolower($email), '@');

        return match ($domain) {
            'admin.com' => self::ROLE_ADMIN,
            'staff.com' => self::ROLE_STAFF,
            'delivery.com' => self::ROLE_DRIVER,
            default => self::ROLE_CUSTOMER,
        };
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->house_no,
            $this->street,
            $this->subdivision,
            $this->city,
        ])->filter()->implode(', ');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isDriver(): bool
    {
        return $this->role === self::ROLE_DRIVER;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'driver_id');
    }

    public function paymentsRecorded(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    public function reminder(): HasOne
    {
        return $this->hasOne(Reminder::class);
    }

    public function containerTransactions(): HasMany
    {
        return $this->hasMany(ContainerTransaction::class);
    }
}
