<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeavesAnomalyResolution extends Model
{
    use HasFactory;

    protected $fillable = [
        'anomaly_id',
        'manager_id',
        'resolution_note',
        'resolution_action',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}