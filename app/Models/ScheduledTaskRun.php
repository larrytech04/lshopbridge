<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Real run-history for scheduled commands, written by routes/console.php hooks. */
class ScheduledTaskRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'successful' => 'boolean',
        ];
    }
}
