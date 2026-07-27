<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Append-only analytics ledger — see the migration docblock for why this exists. */
class ProtectedFormSubmission extends Model
{
    const UPDATED_AT = null;

    protected $guarded = [];
}
