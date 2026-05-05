<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_id',
        'ticket_number', // A-001
        'number',        // 1
        'position',      // Orden de llegada
        'priority',      // 0, 1
        'client_document',
        'client_name',
        'client_phone',
        'client_email',
        'call_status_id',
    ];

    protected $casts = [
        'number' => 'integer',
        'position' => 'integer',
        'priority' => 'integer',
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'created_at_formatted',
        // 'is_preferencial' // Si también usas este en el frontend, agrégalo aquí
    ];

    // --- Relaciones ---

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function callStatuse(): BelongsTo
    {
        return $this->belongsTo(CallStatuse::class, 'call_status_id');
    }

    // Obtener la última llamada asociada (útil para saber quién lo está atendiendo ahora)
    public function latestCall(): HasOne
    {
        return $this->hasOne(Call::class)->latestOfMany();
    }

    // --- Scopes ---
    public function scopeWaiting($query): Builder
    {
        $waitingId = CallStatuse::idBySlug('waiting');

        return $query->where('call_status_id', $waitingId ?? -1);
    }

    public function scopeToday($query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeOrderedQueue($query): Builder
    {
        return $query->orderBy('position', 'asc');
    }

    // ------------------------------------- Attributes ---

    // Helper para saber si es Preferencial
    public function getIsPreferencialAttribute(): bool
    {
        return $this->priority > 0;
    }

    /**
     * Retorna ejemplo: "14:30" o "02:30 PM"
     */
    public function getCreatedAtFormattedAttribute(): string
    {
        // Opción A: Formato 24 horas (Ej: 14:30)
        // return $this->created_at->format('H:i');

        // Opción B: Formato 12 horas con AM/PM (Ej: 02:30 PM)
        return $this->created_at?->format('h:i A') ?? '--:--';
    }
}
