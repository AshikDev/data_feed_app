# Data Feed Application

## Overview
This application is designed to process XML data feeds and store the processed data in a database. It's built using the Symfony framework and provides a command-line interface for easy data manipulation.

## Features
- XML data parsing and processing
- Validation of data and files
- Flexible mapping of XML fields to database columns
  - Insertion of data into a database
  - Updating existing data (based on entity_id)
  - Efficient handling of large data feeds
- Keeps error log
- Ability to conduct PHP unit tests for comprehensive testing of the application

## Requirements
- PHP 8.2 or higher
- Symfony 7.0.2
- Composer for managing PHP dependencies
- Any choice of a database system (e.g MySQL)

## Installation
Clone the repository and install dependencies:
```bash
git clone https://github.com/AshikDev/data_feed_app.git
cd data_feed_app
composer install
```

# Configuration
Configure your database of choice by editing the `.env` file. 

### Configure MySQL Credentials in .env file (Default)
Remove the comment mark from the line below:
```bash
# .env
DATABASE_URL="mysql://$MYSQL_USER:$MYSQL_PASSWORD@$MYSQL_HOST:$MYSQL_PORT/$DATABASE_NAME?serverVersion=$MYSQL_VERSION&charset=utf8mb4"
```

Execute the following command to launch the MySQL container:
```bash
docker compose up -d
```

### Alternatively configure MariaDB Credentials in `.env` file
Remove the comment mark from the line below:
```bash
# .env
DATABASE_URL="mysql://$MYSQL_USER:$MYSQL_PASSWORD@$MYSQL_HOST:$MYSQL_PORT/$DATABASE_NAME?serverVersion=$MARIADB_VERSION-MariaDB&charset=utf8mb4"
```

Execute the following command to launch the MariaDB container:
```bash
docker compose -f docker-compose-mariadb.yaml up -d
```

### Alternatively configure PostgreSQL Credentials in `.env` file
Remove the comment mark from the line below:
```bash
# .env
DATABASE_URL="postgresql://$POSTGRES_USER:$POSTGRES_PASSWORD@$POSTGRES_HOST:$POSTGRES_PORT/$DATABASE_NAME?serverVersion=$POSTGRES_VERSION&charset=utf8"
```

Execute the following command to launch the PostgreSQL container:
```bash
docker compose -f docker-compose-postgresql.yaml up -d
```

### Alternatively configure SQLite Credentials in `.env` file
Remove the comment mark from the line below:
```bash
# .env
DATABASE_URL="sqlite:///%kernel.project_dir%/var/sqlite/data_feed.db"
```

## Database Migration
Execute the following commands to perform the database migration:

```bash
php bin/console make:migration
```
```bash
php bin/console doctrine:migrations:migrate
```

# Usage
### Feed Data
Execute the `DataFeed` command with the path to your XML file:

```bash
php bin/console DataFeed /path/to/your/feed.xml
```

### Error Log

Error logs are saved in `var/log/error.log`.
Execute the following command to see the error log:

```bash
tail var/log/error.log
```

Or view the live error log.

```bash
tail -f var/log/error.log
```

Execute the `ClearLog` command to clean the error log:

```bash
php bin/console ClearLog
```

### Unit Test

To test the application for a successful scenario, run the following command:

```bash
php bin/phpunit --filter testExecute tests/Command/DataFeedCommandTest.php
```

To test the application for a failure scenario, run the following command:

```bash
php bin/phpunit --filter testExecuteFailure tests/Command/DataFeedCommandTest.php
```

# File Navigation:

I've completed my tasks on the specified files and am providing the path for your review.

#### Data feed console program

`src/Command/DataFeedCommand.php`

#### PHP unit test

`tests/Command/DataFeedCommandTest.php`

#### Clean Log File

`src/Command/ClearLogFileCommand.php`

#### Entity

`src/Entity/CatalogItem.php`

#### Repository

`src/Repository/CatalogItemRepository.php`