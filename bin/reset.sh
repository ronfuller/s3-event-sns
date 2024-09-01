#!/bin/bash
php artisan migrate:fresh --seed
php artisan optimize:clear
php artisan filament:clear-cached-components
php artisan apitoken:generate
