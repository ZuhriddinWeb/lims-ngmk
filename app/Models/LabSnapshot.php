<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laboratoriyaning oxirgi jonli holati (zavod LIMS'i yuborgan, faqat ruxsat berilgan bloklar).
 */
class LabSnapshot extends Model
{
    protected $fillable = ['lab_id', 'payload', 'datasets', 'bytes', 'generated_at', 'received_at'];

    protected $casts = [
        'payload' => 'array',
        'bytes' => 'integer',
        'generated_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }
}
