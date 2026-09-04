<?php

namespace App\Models\Concerns;

use App\Exceptions\ImmutableRecordException;

trait ImmutableRecord
{
    /**
     * Boot the trait to block Eloquent updates and deletes.
     */
    public static function bootImmutableRecord(): void
    {
        static::updating(function () {
            throw new ImmutableRecordException(static::class.' is insert-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new ImmutableRecordException(static::class.' is insert-only and cannot be deleted.');
        });
    }

    /**
     * Prevent save on existing insert-only model.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new ImmutableRecordException(static::class.' is insert-only and cannot be updated.');
        }

        return parent::save($options);
    }

    /**
     * Prevent updates on insert-only model.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $options
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new ImmutableRecordException(static::class.' is insert-only and cannot be updated.');
    }

    /**
     * Prevent deletes on insert-only model.
     */
    public function delete(): ?bool
    {
        throw new ImmutableRecordException(static::class.' is insert-only and cannot be deleted.');
    }
}
