<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'location',
        'logo_path',
        'footer_text',
        'theme_color',
        'display_notification',
        'display_font_size',
        'display_font_color',
        'print_preview_enabled',
        'voice_enabled',
        'kiosk_token',
        'kiosk_code',
        'ticket_cooldown_minutes', // Nuevo campo de control de tiempo
    ];

    protected $casts = [
        'print_preview_enabled' => 'boolean',
        'voice_enabled' => 'boolean',
        'display_font_size' => 'integer',
    ];
}
