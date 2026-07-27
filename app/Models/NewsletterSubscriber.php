<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'interests' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $subscriber) {
            $subscriber->unsubscribe_token ??= Str::random(48);
        });
    }
}
