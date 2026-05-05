<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Counter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    // --- Relaciones ---

    // Historial de llamadas atendidas en esta ventanilla
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Traer las asignaciones activas para la ventanilla
     */
    public function activeAssignments(): HasMany
    {
        return $this->hasMany(CounterAssignment::class)
            ->whereNull('closed_at');
    }

    /**
     * Trae a los usuarios que esta asignados a la ventanillla y que esten activos
     */
    public function operator(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, CounterAssignment::class, 'counter_id', 'id', 'id', 'user_id')
            ->whereNull('counter_assignments.closed_at');
    }

    // --- Scopes ---

    public function scopeActive($query): Builder
    {
        return $query->where('status', true);
    }
}
