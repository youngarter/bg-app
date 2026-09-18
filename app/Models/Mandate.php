<?php

namespace App\Models;

use App\Enums\ExerciseState;
use App\Enums\MandateState;
use App\Models\Concerns\BelongsToResidence;
use Database\Factories\MandateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mandate extends Model
{
    /** @use HasFactory<MandateFactory> */
    use BelongsToResidence, HasFactory;

    protected $fillable = [
        'residence_id',
        'syndic_company_id',
        'started_on',
        'ended_on',
        'state',
        'pv_ag_reference',
        'pv_ag_document_path',
        'honoraires',
        'terminated_motif',
        'terminated_document_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
            'state' => MandateState::class,
            'honoraires' => 'integer',
        ];
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function syndicCompany(): BelongsTo
    {
        return $this->belongsTo(SyndicCompany::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function isActive(): bool
    {
        return $this->state === MandateState::Active;
    }

    public function isDraft(): bool
    {
        return $this->state === MandateState::Draft;
    }

    public function isTerminated(): bool
    {
        return $this->state === MandateState::Terminated;
    }

    public function isExpired(): bool
    {
        return $this->state === MandateState::Expired;
    }

    public function isClosed(): bool
    {
        return $this->state === MandateState::Closed;
    }

    /**
     * Check if all exercises belonging to this mandate are closed.
     */
    public function allExercisesClosed(): bool
    {
        return ! $this->exercises()
            ->where('state', '!=', ExerciseState::Closed->value)
            ->exists();
    }
}
