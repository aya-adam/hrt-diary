#!/bin/sh

~/.nodenv/versions/24/bin/npm ci --no-fund --no-audit --prefer-offline --no-progress || exit 2
~/.nodenv/versions/24/bin/npm run build || exit 2

exec "$@"