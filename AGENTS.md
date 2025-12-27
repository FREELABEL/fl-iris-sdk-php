# IRIS SDK PHP - Agent Guide

This repository contains the official PHP SDK for IRIS.

## 1. Development & Testing Commands

### Build & Dependencies
- **Install dependencies:** `composer install`
- **Update dependencies:** `composer update`
- **Autoload dump:** `composer dump-autoload`

### Testing (PHPUnit)
- **Run all tests:** `composer test`
- **Run unit tests:** `composer test:unit`
- **Run integration tests:** `composer test:integration`
- **Run single test file:** `vendor/bin/phpunit tests/Unit/ConfigTest.php`
- **Run single test method:** `vendor/bin/phpunit tests/Unit/ConfigTest.php --filter test_config_requires_api_key`
- **Run with coverage:** `vendor/bin/phpunit --coverage-text`

### Static Analysis & Linting
- **PHPStan (Static Analysis):** `composer analyse` (Level 8)
- **Code Style Fixer:** `composer cs-fix` (Fixes style issues in `src/`)

## 2. Code Style & Conventions

### General
- **PHP Version:** 8.1+
- **Strict Types:** `declare(strict_types=1);` at the top of every file.
- **Namespace:** `IRIS\SDK\`
- **Source Root:** `src/` maps to `IRIS\SDK\`
- **Tests Root:** `tests/` maps to `IRIS\SDK\Tests\`

### Formatting
- Follow **PSR-12** coding standards.
- Use 4 spaces for indentation (no tabs).
- Files must end with a single newline.
- Class braces go on the next line.
- Method braces go on the next line.

### Imports
- Group imports by type (core PHP, third-party, project-internal).
- Unused imports must be removed.
- Use fully qualified class names in docblocks if necessary, but prefer importing classes.

### Naming Conventions
- **Classes:** `PascalCase` (e.g., `AuthManager`, `AgentsResource`)
- **Methods:** `camelCase` (e.g., `create`, `asUser`, `testConnection`)
- **Variables:** `camelCase` (e.g., `$apiKey`, `$userId`)
- **Constants:** `UPPER_SNAKE_CASE` (e.g., `VERSION`, `DEFAULT_TIMEOUT`)
- **Test Methods:** `snake_case` starting with `test_` (e.g., `test_config_requires_api_key`)

### Type Safety
- Always use type hints for method arguments and return types.
- Use `?Type` for nullable types.
- Use `void` for methods that return nothing.
- Use strict typing (`declare(strict_types=1);`).

### Error Handling
- Use exceptions for error conditions, not returning false/null (unless specific "find" methods).
- Custom exceptions are in `src/Exceptions/`.
- Throw `InvalidArgumentException` for bad input.
- Throw `IRIS\SDK\Exceptions\IRISException` for SDK-specific runtime errors.

### Structure & Architecture
- **Resources:** Feature modules live in `src/Resources/` (e.g., `Agents`, `Leads`).
- **Data Transfer Objects (DTOs):** Use simple PHP classes or arrays depending on complexity. If using classes, place them near the resource.
- **HTTP Client:** All requests go through `IRIS\SDK\Http\Client`. Do not instantiate Guzzle directly in resources.
- **Configuration:** Use the `IRIS\SDK\Config` class for all settings.

### Documentation
- All public methods must have PHPDoc blocks.
- Include `@param`, `@return`, and `@throws` tags.
- Add concise `@example` snippets for complex methods.

## 3. Project Structure
```
src/
  Auth/           # Authentication logic
  Console/        # CLI commands
  Exceptions/     # Custom exceptions
  Http/           # HTTP client wrapper
  Laravel/        # Laravel integration (Provider/Facade)
  Resources/      # API resources (Agents, Bloqs, Leads, etc.)
  Config.php      # Configuration handler
  IRIS.php        # Main entry point class
tests/
  Unit/           # Unit tests
  Integration/    # Integration tests (hit real API)
  Mocks/          # Test mocks
```

## 4. Key Implementation Details

### Making API Requests
Resource classes should use the injected `Client` instance:
```php
public function get(string $id): array
{
    return $this->client->get("/v1/agents/{$id}");
}
```

### Adding a New Resource
1. Create directory `src/Resources/NewFeature/`
2. Create `NewFeatureResource.php` extending nothing (uses dependency injection).
3. Inject `Client` and `Config` in constructor.
4. Add methods mapping to API endpoints.
5. Register in `IRIS\SDK\IRIS` constructor.

### CLI Commands
- Located in `src/Console/Commands/`
- Extend `Symfony\Component\Console\Command\Command`
- Registered in `src/Console/Application.php`

## 5. Environment Variables
When running integration tests, ensure `.env` contains:
- `IRIS_API_KEY`
- `IRIS_USER_ID`
- `IRIS_BASE_URL` (optional)
