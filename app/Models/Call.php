<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Call extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'service_id',
        'counter_id',
        'user_id',
        'call_status_id',
        'token_letter',
        'token_number',
        'called_date',
        'called_at',
        'started_at',
        'ended_at',
        'waiting_duration',
        'served_duration',
        'turn_around_duration',
    ];

    protected $casts = [
        'called_date' => 'date',
        'called_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'waiting_duration' => 'integer',
        'served_duration' => 'integer',
        'turn_around_duration' => 'integer',
        'token_number' => 'integer',
    ];

    // --- Relaciones (Conectan el Snapshot con la entidad original) ---

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function callStatus(): BelongsTo
    {
        return $this->belongsTo(CallStatuse::class);
    }

    // --- Attributes ---

    // Formatear duración (de segundos a "00:05:00")
    public function getServedTimeFormattedAttribute(): string
    {
        return gmdate('H:i:s', $this->served_duration);
    }

    public function getWaitingTimeFormattedAttribute(): string
    {
        return gmdate('H:i:s', $this->waiting_duration);
    }

    /**
     * Scope para filtrar registros de hoy.
     */
    public function scopeToday(Builder $query): Builder
    {
        // Filtramos por la fecha de creación del registro de la llamada
        return $query->whereDate('created_at', today());
    }

    /**
     * Summary of scopeWaiting
     *
     * @param  mixed  $query
     */
    public function scopeNoShow($query): Builder
    {
        $noShowId = CallStatuse::idBySlug('no_show');

        return $query->where('call_status_id', $noShowId ?? -1);
    }
}
