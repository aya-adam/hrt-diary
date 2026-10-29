#!/bin/sh

composer install --no-interaction --quiet --no-dev --no-scripts --optimize-autoloader || exit 2
composer dump-env --no-interaction --quiet prod || exit 2
php bin/console doctrine:database:create --env=prod --no-interaction --quiet --if-not-exists || exit 2
php bin/console doctrine:migrations:migrate --env=prod --no-interaction --quiet || exit 2
php bin/console assets:install public --env=prod --no-interaction --quiet || exit 2
php bin/console lexik:jwt:generate-keypair --env=prod --no-interaction --quiet --skip-if-exists || exit 2
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear --env=prod --no-interaction --quiet || exit 2

exec "$@"