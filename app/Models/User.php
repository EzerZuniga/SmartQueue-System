<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image_path',
        'status',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'status' => 'boolean',
            'preferences' => 'array',
        ];
    }

    // --------------------- Relaciones ---

    // Historial de atenciones realizadas por este usuario
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    // Traer la ventanilla asignada activa
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(CounterAssignment::class)->whereNull('closed_at')->latest();
    }

    public function currentCounter(): HasOneThrough
    {
        return $this->hasOneThrough(Counter::class, CounterAssignment::class, 'user_id', 'id', 'id', 'counter_id')
            ->whereNull('counter_assignments.closed_at');
    }

    // ------------------------ Scopes ---

    public function scopeActive($query): Builder
    {
        return $query->where('status', true);
    }

    // --- Attributes ---

    // Retorna la URL completa de la imagen o un avatar por defecto
    public function getAvatarUrlAttribute(): string
    {
        return $this->image_path
            ? asset('storage/avatars/'.$this->image_path)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=7F9CF5&background=EBF4FF';
    }
}
