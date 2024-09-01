#!/bin/bash
php artisan icons:cache
php artisan filament:cache-components
php artisan data:cache-structures
php artisan optimize
