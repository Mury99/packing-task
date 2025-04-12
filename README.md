### init
- `printf "UID=$(id -u)\nGID=$(id -g)" > .env`
- `docker-compose up -d`
- `docker-compose run shipmonk-packing-app bash`
- `composer install && bin/doctrine orm:schema-tool:create && bin/doctrine dbal:run-sql "$(cat data/packaging-data.sql)"`

### run
- `php run.php "$(cat sample.json)"`

- PHP ECS: `vendor/bin/ecs`
- PHP ECS Fixer: `vendor/bin/ecs --fix`
- PHPStan: `vendor/bin/phpstan analyse src tests`
- PHPUnit: `vendor/bin/phpunit tests`

- or `composer tests` (ECS, PHPStan, PHPUnit)

### APIs
- https://www.3dbinpacking.com/en/api-doc#pack-a-shipment

### adminer
- Open `http://localhost:8080/?server=mysql&username=root&db=packing`
- Password: secret
