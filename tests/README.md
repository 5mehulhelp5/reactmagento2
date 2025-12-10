# ReactInjectPlugin Tests - Modern Pest Architecture

This directory contains Pest PHP tests for the `ReactInjectPlugin` class, implementing the modern Pest testing architecture for Magento 2.

## Architecture

Based on: https://yegorshytikov.medium.com/modern-pest-testing-architecture-for-magento-2-818d5ea2b406

### Hybrid Bootstrap Architecture

```
┌─────────────────────────────────────────────────┐
│ HYBRID BOOTSTRAP ARCHITECTURE                   │
└─────────────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│ 1. Pest Autoloader (PHPUnit 10+)        │
│ ✓ Modern testing framework              │
│ ✓ Beautiful syntax                      │
│ ✓ Fast execution                        │
│ Location: tests/vendor/                  │
└──────────────────────────────────────────┘
↓
┌──────────────────────────────────────────┐
│ 2. Custom Magento Autoloader             │
│ ✓ Loads Magento classes                  │ 
│ ✓ Excludes PHPUnit classes               │
│ ✓ Preserves Pest's PHPUnit               │
│ Location: tests/bootstrap.php            │
└──────────────────────────────────────────┘
↓
┌──────────────────────────────────────────┐
│ 3. Test Environment                     │
│ ✓ Pest tests ✓                          │
│ ✓ Magento classes ✓                     │
│ ✓ No conflicts ✓                        │
└──────────────────────────────────────────┘
```

### Key Innovation: Selective Autoloader

The core innovation is a **selective autoloader** that loads Magento classes while explicitly skipping PHPUnit:

```php
spl_autoload_register(function ($class) {
    // SKIP PHPUnit classes - use Pest's version
    if (strpos($class, 'PHPUnit\\') === 0) {
        return false;
    }
    // Load Magento classes...
}, true, false);
```

## Current Implementation

✅ **Pest PHP installed** (v2.36.0 with PHPUnit 10+)  
✅ **Test structure created** with proper bootstrap  
✅ **Custom autoloader** filtering PHPUnit classes  
✅ **Magento bootstrap** integration  
✅ **All tests passing** (37 tests, 112 assertions)  
✅ **Isolated vendor directory** in tests/ folder  

## Directory Structure

```
tests/
├── Pest.php                    # Pest configuration
├── bootstrap.php               # Hybrid bootstrap (Pest + Magento)
├── composer.json               # Test dependencies (Pest, etc.)
├── composer.lock               # Lock file
├── README.md                   # This file
├── Unit/
│   └── ReactInjectPlugin.test.php  # Main test file
└── vendor/                     # Test dependencies (isolated)
    └── ...
```

## Running Tests

### From Module Root

```bash
# Run all tests
cd /var/www/html/react-luma/reactmagento2
tests/vendor/bin/pest --configuration=tests/phpunit.xml

# Run specific test file
tests/vendor/bin/pest --configuration=tests/phpunit.xml tests/Unit/ReactInjectPlugin.test.php

# Run with filter
tests/vendor/bin/pest --configuration=tests/phpunit.xml tests/Unit/ReactInjectPlugin.test.php --filter="Action Filter"

# Run with coverage
tests/vendor/bin/pest --configuration=tests/phpunit.xml --coverage
```

### From Tests Directory

```bash
cd /var/www/html/react-luma/reactmagento2/tests
composer test

# Or directly
vendor/bin/pest
```

## Test Coverage

The test suite covers:

- ✅ **Basic functionality** - Instantiation, configuration
- ✅ **Action filter** - Only allowed actions are optimized
- ✅ **Page type detection** - Product, category, search pages
- ✅ **Per-page CSS optimization** - Product/Category specific CSS
- ✅ **Critical CSS** - Inline vs preload configuration
- ✅ **JavaScript optimization** - RequireJS, React/Vue handling
- ✅ **Configuration variants** - All config combinations tested

## Key Files

- `tests/bootstrap.php` - Hybrid bootstrap implementing Pest + Magento architecture
- `tests/Pest.php` - Pest configuration and test setup
- `tests/Unit/ReactInjectPlugin.test.php` - Comprehensive unit tests
- `tests/composer.json` - Test dependencies configuration
- `tests/phpunit.xml` - PHPUnit configuration
- `tests/vendor/bin/pest` - Pest executable (from vendor)

## How It Works

1. **Isolated Dependencies**: Test dependencies are in `tests/vendor/`, separate from Magento's vendor
2. **Selective Autoloading**: Custom autoloader loads Magento classes but skips PHPUnit
3. **Mock ObjectManager**: Uses mock ObjectManager for testing without full Magento bootstrap
4. **Hybrid Bootstrap**: Loads Pest's PHPUnit 10+ first, then Magento classes

## Troubleshooting

### Tests not running?

```bash
# Ensure dependencies are installed
cd tests
composer install

# Check Pest is accessible
vendor/bin/pest --version
```

### PHPUnit conflicts?

The selective autoloader prevents PHPUnit conflicts by:
- Loading Pest's PHPUnit 10+ first
- Filtering out PHPUnit classes from Magento's autoloader
- Using isolated vendor directory

## Development

### Adding New Tests

1. Create test file in `tests/Unit/` or `tests/Feature/`
2. Use Pest syntax:
```php
test('description', function () {
    expect($value)->toBe($expected);
});
```

### Running Specific Tests

```bash
# Filter by name (from module root)
tests/vendor/bin/pest --configuration=tests/phpunit.xml --filter="product page"

# Filter by name (from tests directory - phpunit.xml found automatically)
cd tests
vendor/bin/pest --filter="product page"

# Filter by group
tests/vendor/bin/pest --configuration=tests/phpunit.xml --group=unit
```

## References

- [Modern Pest Testing Architecture for Magento 2](https://yegorshytikov.medium.com/modern-pest-testing-architecture-for-magento-2-818d5ea2b406)
- [Pest PHP Documentation](https://pestphp.com/docs)
