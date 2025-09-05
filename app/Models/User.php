<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;

use Spatie\Activitylog\LogOptions;
use App\Models\ChannelCredential;
use App\Models\Scopes\CountryScope;
use Illuminate\Database\Eloquent\Relations\MorphMany;


class User extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;
    use LogsActivity;


    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;



    // protected static function booted()
    // {
    //     static::addGlobalScope(new CountryScope);
    // }




    protected $guard_name = 'sanctum'; // Important for Spatie permissions


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'client_name',
        'address',
        'city',
        'state',
        'token',
        'username',
        'phone_number',
        'alt_number',
        'country_code',
        'time_zone',
        'language',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'last_seen_at',
        'two_factor_enabled',
        'timezone',
        'country_id',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        // 'token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logOnly(['name', 'email', 'is_active']) // add the fields you care about
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "User was {$eventName}");
    }



    // channelCredentials

    //     public function channelCredentials(): MorphMany
    // {
    //     return $this->morphMany(ChannelCredential::class, 'credentialable');
    // }


    public function channelCredentials()
    {
        return $this->morphMany(ChannelCredential::class, 'credentialable');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
