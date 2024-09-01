#!/bin/bash
set -e
./vendor/bin/pest --parallel --processes=12 "$1"
