<?php

namespace App\Models\Concerns;

use App\Models\Residence;
use App\Models\Scopes\ResidenceScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToResidence
{
    /**
     * Boot the trait to apply the residence global scope.
     */
    public static function bootBelongsToResidence(): void
    {
        static::addGlobalScope(new ResidenceScope);
    }

    /**
     * Get the residence this model belongs to.
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }
}
