<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentShare extends Model
{
    use HasFactory;
    protected $fillable = [
        'document_id',
        'token',
        'expires_at',
        'max_downloads',
        'permission',
        'allow_download',
        'password',
    ];
    protected $hidden = ['password'];

    protected $dates = ['expires_at'];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function isExpired()
    {
        return now()->greaterThan($this->expires_at);
    }

    public function hasReachedLimit()
    {
        return $this->max_downloads &&
               $this->download_count >= $this->max_downloads;
    }

    public function shares()
{
    return $this->hasMany(DocumentShare::class);
}

public function logs()
{
    return $this->morphMany(ActivityLog::class, 'loggable');
}
}
