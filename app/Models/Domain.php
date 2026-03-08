<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Domain extends Model
{
    use HasFactory;

    /**
     * Nom de la table associée au modèle.
     * Laravel devine "domains" automatiquement,
     * mais on le précise pour clarté architecturale.
     */
    protected $table = 'domains';

    /**
     * Champs autorisés à l'insertion en masse.
     * Sécurité contre le Mass Assignment.
     */
    protected $fillable = [
        'domain'
    ];

    /**
     * Désactive les timestamps si nécessaire.
     * Ici on les garde car le CDC impose audit & traçabilité.
     */
    public $timestamps = true;
}