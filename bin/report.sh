#!/bin/bash
set -e
php artisan test --parallel --coverage-html public/report
