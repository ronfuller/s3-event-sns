#!/bin/bash
php artisan migrate:fresh --env=testing
php artisan db:seed --env=testing
php artisan test --parallel --recreate-databases "$1"
