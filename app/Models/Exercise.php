<?php

namespace App\Models;

use App\Enums\ExerciseState;
use App\Models\Concerns\BelongsToResidence;
use Database\Factories\ExerciseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use BelongsToResidence, HasFactory;

    protected $fillable = [
        'residence_id',
        'mandate_id',
        'label',
        'started_on',
        'ended_on',
        'state',
        'closed_at',
        'closed_by_user_id',
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
            'state' => ExerciseState::class,
            'closed_at' => 'datetime',
        ];
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function mandate(): BelongsTo
    {
        return $this->belongsTo(Mandate::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function isOpen(): bool
    {
        return $this->state === ExerciseState::Open;
    }

    public function isClosing(): bool
    {
        return $this->state === ExerciseState::Closing;
    }

    public function isClosed(): bool
    {
        return $this->state === ExerciseState::Closed;
    }
}
