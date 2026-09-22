<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedMemberEmail extends Model
{
    protected $fillable = [
        'email',
        'blocked_by',
    ];

    public function blockedBy()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}
