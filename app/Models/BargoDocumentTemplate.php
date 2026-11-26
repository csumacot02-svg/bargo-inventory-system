<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BargoDocumentTemplate extends Model
{
    public const SLUG_ORDER_OF_PAYMENT = 'order-of-payment';

    protected $fillable = [
        'slug',
        'label',
        'disk_path',
        'original_filename',
        'uploaded_by',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
