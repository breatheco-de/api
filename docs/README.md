# ![alt text](https://assets.breatheco.de/apis/img/images.php?blob&random&cat=icon&tags=breathecode,32) BreatheCode API

### [Installation Instructions](./INSTALL.md) - [Project Structue](./STRUCTURE.md)

This repository contains all the endpoints available in the main BreatheCode API
you can find a details explanation of all the endpoints here:

[https://documenter.getpostman.com/view/1757920/talent-tree-api/RVu2kq1k](https://documenter.getpostman.com/view/1757920/talent-tree-api/RVu2kq1k)

### Requierments:

1. PHP 7 ([click here if you are using cloud 9](https://community.c9.io/t/how-to-upgrade-a-php-workspace-to-version-7/8570)
2. Composer
3. MySQL (for now)

### Tests

Run all the tests
```
$ bash api/src/tests/initialize.sh
```

Run a particular group of tests
```
$ ./vendor/bin/phpunit api/src/tests/CatalogTest.php --colors
```

## Migrations

***Create a migration***
```php
php vendor/bin/phinx create MyFirstMigration -c api/phinx-config.php
```
You need to create the content of the files manually.

***Run the migrations***
```sh

// For the *dev* environment
php vendor/bin/phinx migrate -e dev -c api/phinx-config.php -e dev

// For unit *testing* environment
php vendor/bin/phinx migrate -e utest -c api/phinx-config.php -e utest

```
***Status of current migrations for enviroment "utest"***
```sh
php vendor/bin/phinx status -c api/phinx-config.php -e utest
```
***Set a breakpoint for environment "utest"***
```sh
php vendor/bin/phinx breakpoint -t <20171129101240> -c api/phinx-config.php -e utest
```

## Seeders

Create a new seeder
```sh
php vendor/bin/phinx seed:create UserSeeder -c api/phinx-config.php -e utest
```

Run the seeder
```sh
$ php vendor/bin/phinx seed:run -s TalentTreeSeeder -c api/phinx-config.php -e utest
```

### Running PHP on Sitegrground (production)

Sitegroung has several versions of PHP, you can pick the right one by typing:
```
For PHP 7 : /usr/local/php70/bin/php-cli
For PHP 7.1: /usr/local/php70/bin/php-cli

/usr/local/php70/bin/php-cli vendor/bin/phinx migrate -e utest -c api/phinx-config.php
```
