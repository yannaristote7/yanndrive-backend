<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'path',
        'mime_type',
        'size'
    ];

    /**
     * Relation : un document appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function sharedWith()
{
    return $this->belongsToMany(User::class);
}
}