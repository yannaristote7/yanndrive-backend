#!/bin/bash
php artisan migrate --force

# Seed seulement si la table users est vide
php artisan tinker --execute="if (\App\Models\User::count() === 0) { Artisan::call('db:seed', ['--force' => true]); }"

php artisan serve --host=0.0.0.0 --port=$PORT