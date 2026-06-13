<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToUserAndCountry;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\CountryScope;


class FulfillmentHub extends Model
{
    use HasFactory;
    use BelongsToUserAndCountry;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'last_call_agent_id',
        'country_id',
    ];


    protected static function booted(): void
    {
        static::addGlobalScope(new CountryScope);
    }
    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // /**
    //  * Agents working in this hub
    //  */
    // public function agents()
    // {
    //     return $this->belongsToMany(User::class, 'fulfillment_hub_user');
    // }

    // /**
    //  * Vendors assigned to this hub
    //  */
    // public function vendors()
    // {
    //     return $this->hasMany(Vendor::class, 'fulfillment_hub_id');
    // }


    /**
     * Agents working in this hub
     */
    public function agents()
    {
        // ← correct pivot table + foreign key
        return $this->belongsToMany(User::class, 'fulfillment_hub_agent', 'fulfillment_hub_id', 'agent_id')
            ->withTimestamps();
    }

    /**
     * Vendors assigned to this hub
     */
    public function vendors()
    {
        // ← belongsToMany, not hasMany
        return $this->belongsToMany(User::class, 'fulfillment_hub_vendor', 'fulfillment_hub_id', 'vendor_id')
            ->withTimestamps();
    }

    /**
     * Last agent used in assignment (for optional fallback / tracking)
     */
    public function lastCallAgent()
    {
        return $this->belongsTo(User::class, 'last_call_agent_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get available agents
     */
    public function availableAgents()
    {
        return $this->agents();
        // ->where('present', true);
        // ->where('country_id', $this->country_id) --- IGNORE ---
    }

    /**
     * Check if hub has agents
     */
    public function hasAgents(): bool
    {
        return $this->availableAgents()->exists();
    }


    // belongs to a country 
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
