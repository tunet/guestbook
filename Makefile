SHELL := /bin/bash

tests:
	symfony console doctrine:database:drop --force --env=test || true
	symfony console doctrine:database:create --env=test
	symfony console doctrine:migrations:migrate --no-interaction --env=test
	symfony console doctrine:fixtures:load --no-interaction --env=test
	symfony php vendor/bin/bdi detect drivers
	symfony php bin/phpunit $@

.PHONY: tests
