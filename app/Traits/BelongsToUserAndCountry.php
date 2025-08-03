<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait BelongsToUserAndCountry
{
    public static function bootBelongsToUserAndCountry(): void
    {
        Log::info('BelongsToUserAndCountry trait booting...');

        static::creating(function ($model) {
            if (Auth::check()) {
                $user = Auth::user();
                Log::info('Creating CallCenterSetting for user', ['user_id' => $user->id, 'country_id' => $user->country_id]);

                if ($model->shouldSetCountryId()) {
                    $model->country_id = $user->country_id;
                    Log::info('country_id set on model', ['value' => $user->country_id]);
                }

                if ($model->shouldSetUserId()) {
                    $model->user_id = $user->id;
                    Log::info('user_id set on model', ['value' => $user->id]);
                }
            }
        });
    }

    protected function shouldSetCountryId(): bool
    {
        return in_array('country_id', $this->fillable) && empty($this->country_id);
    }

    protected function shouldSetUserId(): bool
    {
        return in_array('user_id', $this->fillable) && empty($this->user_id);
    }
}
