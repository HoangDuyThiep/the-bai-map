<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'note',
        'created_by',
    ];

    public function salesReports()
    {
        return $this->hasMany(SalesReport::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}