<?php

namespace App\Models\Scopes;

use App\Support\ResidenceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ResidenceScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $residenceId = ResidenceContext::getId();

        if ($residenceId !== null) {
            $builder->where($model->qualifyColumn('residence_id'), $residenceId);
        }
    }
}
