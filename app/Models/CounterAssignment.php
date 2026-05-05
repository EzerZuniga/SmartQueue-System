<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CounterAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'counter_id', 'opened_at', 'closed_at'];

    protected $appends = ['time_open'];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // ================================RELACIONES
    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene los servicios relacionados
     */
    public function services(): BelongsToMany
    {
        // "Una asignación puede tener múltiples servicios asociados"
        return $this->belongsToMany(Service::class, 'assignment_services')->withTimestamps();
    }

    // ================================== ATRIBUTOS
    protected function timeOpen(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->opened_at ? $this->opened_at->format('h:i A') : null,
        );
    }
}
