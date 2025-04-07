# Job Board API with Advanced Filtering

A Laravel-based API for managing job listings with powerful filtering capabilities similar to Airtable. The system uses Entity-Attribute-Value (EAV) design patterns alongside traditional relational database models.

## Table of Contents

- [Overview](#overview)
- [Project Setup](#project-setup)
  - [Prerequisites](#prerequisites)
  - [Installation Steps](#installation-steps)
  - [Testing Environment Setup](#testing-environment-setup)
- [Documentation](#documentation)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

## Overview

The Job Board API provides a comprehensive solution for managing job listings with advanced filtering capabilities. For full details on the filtering syntax and options, please refer to the detailed documentation in the `-project_root/-astudio-job-matrix/README.md` file.

## Project Setup

### Prerequisites

- Docker and Docker Compose
- Postman (for API testing)

### Installation Steps

1. Clone the repository:
   ```bash
   git clone [repository-url]
   cd [repository-directory]
   ```

2. Create a `.env` file:
   ```bash
   cp .env.example .env
   ```

3. Configure your `.env` file with the database settings:
   ```
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=astudio_jobmatrix
   DB_USERNAME=app_user
   DB_PASSWORD=app_password
   ```

4. Start Docker containers:
   ```bash
   docker-compose down
   docker-compose up -d
   ```

5. Run database migrations:
   ```bash
   docker exec -it compose_php_server_8.2-fpm-alpine php artisan migrate
   ```

6. Seed the database:
   ```bash
   docker exec -it compose_php_server_8.2-fpm-alpine php artisan db:seed
   ```

   Individual seeders can be run with:
   ```bash
   docker exec -it compose_php_server_8.2-fpm-alpine php artisan db:seed --class=CoreJobsTableSeeder
   docker exec -it compose_php_server_8.2-fpm-alpine php artisan db:seed --class=LanguagesTableSeeder
   docker exec -it compose_php_server_8.2-fpm-alpine php artisan db:seed --class=LocationsTableSeeder
   docker exec -it compose_php_server_8.2-fpm-alpine php artisan db:seed --class=CategoriesTableSeeder
   docker exec -it compose_php_server_8.2-fpm-alpine php artisan db:seed --class=AttributesTableSeeder
   docker exec -it compose_php_server_8.2-fpm-alpine php artisan db:seed --class=JobAttributeValuesTableSeeder
   ```

### Testing Environment Setup

1. Create a test database:
   ```bash
   docker exec -it compose_mysql_db_server mysql -u root -p
   # Enter root password when prompted
   CREATE DATABASE astudio_jobmatrix_test;
   GRANT ALL PRIVILEGES ON astudio_jobmatrix_test.* TO 'app_user'@'%';
   FLUSH PRIVILEGES;
   exit
   ```

2. Create a `.env.testing` file:
   ```
   APP_ENV=testing
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=astudio_jobmatrix_test
   DB_USERNAME=app_user
   DB_PASSWORD=app_password
   ```

## Documentation

Detailed documentation about the API can be found in the project's main README file. For comprehensive information, please refer to `-project_root/-astudio-job-matrix/README.md` for details on:

- Query Parameter Format
  - General Format
  - Basic Conditions
  - Relationship Conditions
  - EAV (Dynamic Attribute) Conditions
  - Complex Expressions
  - Grouping
  - Complex Examples

- Supported Filter Types
  - Basic Filters
  - Relationship Filters
  - EAV Filters

- Logical Operators & Grouping

- Schema Design
  - Core Tables
  - Relationship Tables

- Architecture / Implementation Details

- API Documentation
  - Endpoints (GET /api/jobs)
  - Response Format
  - Error Handling

## Testing

### Running Tests

Run the test suite:
```bash
docker exec -it compose_php_server_8.2-fpm-alpine php artisan test
```

Test specific files:
```bash
docker exec -it compose_php_server_8.2-fpm-alpine php artisan test tests/Feature/Models/CoreJobTest.php
```

Test specific methods:
```bash
docker exec -it compose_php_server_8.2-fpm-alpine php artisan test tests/Feature/Models/CoreJobTest.php --filter testStringFieldFiltering
```

### Testing with Postman

1. Import the provided Postman collection file `-Job Filtering API.postman_collection.json`
2. Set up environment variables in Postman:
   - Create a new environment (e.g., "Job Board API")
   - Add a variable called `baseUrl` with value `http://localhost:80/api`
3. Update request URLs to use the environment variable
4. Run the requests to test different filtering scenarios

## Troubleshooting

### Memory Issues

If you encounter memory issues during testing:

- Update memory_limit in php.ini:
  ```bash
  # Check the -services&configs/-php-config/php.ini file and set:
  memory_limit = 1024M
  ```

- Set XDEBUG_MODE=off in docker-compose.yml:
  ```yaml
  environment:
    - XDEBUG_MODE=off
  ```

- For extreme cases:
  ```bash
  APP_ENV=testing php -d memory_limit=-1 artisan tinker
  ```

### Other Issues

- Clear Laravel cache:
  ```bash
  docker exec -it compose_php_server_8.2-fpm-alpine sh -c "php artisan cache:clear && php artisan config:clear && php artisan route:clear"
  ```

- Rebuild containers:
  ```bash
  docker-compose down -v && docker-compose up --build -d
  ```

- Update dependencies:
  ```bash
  docker exec -it compose_php_server_8.2-fpm-alpine composer update
  docker exec -it compose_php_server_8.2-fpm-alpine composer dump-autoload
  ```