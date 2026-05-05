<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'prefix',
        'start_number',
        'status',
        'ask_document',
        'ask_name',
        'name_required',
        'ask_email',
        'ask_phone',
    ];

    protected $casts = [
        'status' => 'boolean',
        'ask_document' => 'boolean',
        'ask_name' => 'boolean',
        'name_required' => 'boolean',
        'ask_email' => 'boolean',
        'ask_phone' => 'boolean',
        'start_number' => 'integer',
    ];

    protected $appends = [
        'encrypted_id',
    ];

    // --- Relaciones ---

    // Tickets pendientes o procesados de este servicio
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    // Historial de llamadas de este servicio (Snapshot)
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Obtiene las asignaciones (sesiones) que están atendiendo este servicio actualmente.
     */
    public function activeAssignments(): BelongsToMany
    {
        // "Un servicio puede ser atendido por muchas asignaciones a la vez"
        return $this->belongsToMany(CounterAssignment::class, 'assignment_services')
            ->whereNull('counter_assignments.closed_at') // Filtro útil: solo las activas
            ->withTimestamps();
    }

    // --- Scopes ---

    public function scopeActive($query): Builder
    {
        return $query->where('status', true);
    }

    // ================Atributos
    public function getEncryptedIdAttribute(): string
    {
        return Crypt::encryptString($this->id);
    }
}
