<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormFingerprint extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'form_types' => 'array',
            'ip_hashes' => 'array',
            'blocked' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function distinctIpCount(): int
    {
        return count($this->ip_hashes ?? []);
    }
}
