#!/bin/bash
# Arrête le conteneur si une commande échoue, au lieu de démarrer le serveur
# sur une base partiellement migrée (l'ancien comportement masquait les
# erreurs de migration en 500 silencieux sur toutes les routes touchant la DB).
set -e

php artisan migrate --force

# Seed seulement si la table users est vide
php artisan tinker --execute="if (\App\Models\User::count() === 0) { Artisan::call('db:seed', ['--force' => true]); }"

php artisan serve --host=0.0.0.0 --port=$PORT
