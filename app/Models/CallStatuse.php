<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallStatuse extends Model
{
    /** @use HasFactory<\Database\Factories\CallStatuseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'color', 'is_final'];

    protected static array $slugIdCache = [];

    // --- Relaciones ---

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'call_status_id');
    }

    // --- Scopes ---

    // Ej: CallStatus::slug('waiting')->first()
    public function scopeSlug($query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public static function idBySlug(string $slug): ?int
    {
        if (array_key_exists($slug, static::$slugIdCache)) {
            return static::$slugIdCache[$slug];
        }

        static::$slugIdCache[$slug] = static::query()
            ->where('slug', $slug)
            ->value('id');

        return static::$slugIdCache[$slug];
    }
}
