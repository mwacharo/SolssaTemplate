<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CountryScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    

     public function apply(Builder $builder, Model $model)
    {
        // Only apply if user is authenticated and has a country_id
        if (Auth::check() && Auth::user()->country_id) {
            $builder->where($model->getTable() . '.country_id', Auth::user()->country_id);
        }
    }
}
