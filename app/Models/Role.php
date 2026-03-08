<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * Champs autorisés à l'insertion en masse.
     * Cela permet de créer un rôle via Role::create([...]).
     */
   protected $fillable = [
    'name',
    'email',
    'password',
    'role_id',  // 🔥 indispensable
];

    /**
     * Relations
     * Un rôle peut avoir plusieurs utilisateurs.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}