# React-Luma Module Tests

This directory contains **PHP tests** (using Pest) for the React-Luma Magento 2 module. The module also includes **JavaScript tests** for `css-purge.js` located in `../css-purge-tests/`.

## Architecture

This test suite uses a **hybrid bootstrap architecture** that combines Pest PHP testing framework with Magento's class autoloader. The architecture allows testing Magento modules without requiring a full Magento installation.

### Key Components

1. **`bootstrap.php`** - Hybrid bootstrap that:
   - Loads Pest dependencies (PHPUnit 10+)
   - Registers Magento class autoloader (excluding PHPUnit classes)
   - Sets up a mock ObjectManager for dependency injection
   - Enables "PEST mode" where `die()` calls return values instead of exiting

2. **Test Structure**:
   - Tests are located in `Unit/` directory
   - Each test file follows Pest syntax
   - Mock classes are defined inline or in test helper classes
   - Reflection API is used to inject mocks into protected/private properties

3. **Mock Strategy**:
   - Anonymous classes extend Magento classes (skip constructors to avoid dependencies)
   - Custom mock classes implement Magento interfaces (e.g., `ScopeConfigInterface`)
   - Reflection is used to inject mocks into `_scopeConfig` and other protected properties

## Quick Start

### Run All PHP Tests
```bash
cd /var/www/html/react-luma/vendor/genaker/react-luma/tests
php vendor/bin/pest
```

### Run Specific Test File
```bash
cd /var/www/html/react-luma/vendor/genaker/react-luma/tests
php vendor/bin/pest Unit/Gallery.test.php
```

### Run JavaScript Tests
```bash
cd /var/www/html/react-luma/vendor/genaker/react-luma/css-purge-tests
npm test
```

## Running Tests

### Prerequisites

1. **Install PHP dependencies**:
   ```bash
   cd /var/www/html/react-luma/vendor/genaker/react-luma/tests
   composer install
   ```

2. **Install JavaScript dependencies** (for JS tests):
   ```bash
   cd /var/www/html/react-luma/vendor/genaker/react-luma/css-purge-tests
   npm install
   ```

### PHP Test Examples

**Run all tests:**
```bash
php vendor/bin/pest
```

**Run specific test file:**
```bash
php vendor/bin/pest Unit/Gallery.test.php
php vendor/bin/pest Unit/DeferJS.test.php
php vendor/bin/pest Unit/DeferCSS.test.php
```

**Run with coverage:**
```bash
php vendor/bin/pest --coverage
```

**Run with verbose output:**
```bash
php vendor/bin/pest -v
```

### Test File Structure

Each test file follows this pattern:

```php
<?php
// Mock classes (defined before test helper)
class MockScopeConfig implements \Magento\Framework\App\Config\ScopeConfigInterface { ... }

// Test helper class (uses reflection to inject mocks)
class GalleryTestHelper { ... }

// Test cases using Pest syntax
beforeEach(function () { ... });

test('test description', function () { ... });
```

### Writing Tests

1. **Create mock classes** that implement Magento interfaces or extend Magento classes
2. **Use reflection** to inject mocks into protected properties (e.g., `_scopeConfig`)
3. **Test public methods** directly or use reflection for private methods
4. **Use Pest assertions** (`expect()`, `toBe()`, `toBeTrue()`, etc.)

### Example: Testing Configuration

```php
test('isBase64ImageEnabled returns true when config is enabled', function () {
    $mockScopeConfig = new MockScopeConfig([
        'react_vue_config/product/base64_image' => '1'
    ]);
    $helper = new GalleryTestHelper([
        'scopeConfig' => $mockScopeConfig
    ]);
    
    $result = $helper->callMethod('isBase64ImageEnabled');
    
    expect($result)->toBeTrue();
});
```

---

## PHP Tests - Modern Pest Architecture

This directory contains Pest PHP tests for the `ReactInjectPlugin` and `DeferJS` classes, implementing the modern Pest testing architecture for Magento 2.

## Architecture

Based on: https://yegorshytikov.medium.com/modern-pest-testing-architecture-for-magento-2-818d5ea2b406

### Hybrid Bootstrap Architecture

```
┌─────────────────────────────────────────────────┐
│ HYBRID BOOTSTRAP ARCHITECTURE                   │
└─────────────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│ 1. Pest Autoloader (PHPUnit 10+)        │
│ - Modern testing framework              │
│ - Beautiful syntax                      │
│ - Fast execution                        │
│ Location: tests/vendor/                  │
└──────────────────────────────────────────┘
↓
┌──────────────────────────────────────────┐
│ 2. Custom Magento Autoloader             │
│ - Loads Magento classes                  │ 
│ - Excludes PHPUnit classes               │
│ - Preserves Pest's PHPUnit               │
│ Location: tests/bootstrap.php            │
└──────────────────────────────────────────┘
↓
┌──────────────────────────────────────────┐
│ 3. Test Environment                     │
│ - Pest tests                            │
│ - Magento classes                       │
│ - No conflicts                          │
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

- **Pest PHP installed** (v2.36.0 with PHPUnit 10+)  
- **Test structure created** with proper bootstrap  
- **Custom autoloader** filtering PHPUnit classes  
- **Magento bootstrap** integration  
- **All tests passing** (37 tests, 112 assertions)  
- **Isolated vendor directory** in tests/ folder  

## Directory Structure

```
vendor/genaker/react-luma/
├── tests/                      # PHP Tests (Pest)
│   ├── Pest.php               # Pest configuration
│   ├── bootstrap.php          # Hybrid bootstrap (Pest + Magento)
│   ├── composer.json          # PHP test dependencies
│   ├── phpunit.xml            # PHPUnit configuration
│   ├── README.md              # This file
│   ├── Unit/
│   │   ├── DeferJS.test.php          # DeferJS tests
│   │   └── ReactInjectPlugin.test.php # ReactInjectPlugin tests
│   └── vendor/                # PHP test dependencies (isolated)
│
└── css-purge-tests/           # JavaScript Tests (Jest)
    ├── package.json           # JS test dependencies
    ├── README.md              # JS tests documentation
    ├── css-purge.test.js      # CSS purge tests
    └── node_modules/          # JS test dependencies
```

## Running PHP Tests

### From Tests Directory (Recommended)

```bash
cd /var/www/html/react-luma/vendor/genaker/react-luma/tests

# Run all PHP tests
php vendor/bin/pest

# Run specific test file
php vendor/bin/pest Unit/DeferJS.test.php
php vendor/bin/pest Unit/DeferCSS.test.php
php vendor/bin/pest Unit/Gallery.test.php
php vendor/bin/pest Unit/ReactInjectPlugin.test.php

# Run with filter
php vendor/bin/pest --filter="isBase64ImageEnabled"
php vendor/bin/pest --filter="Action Filter"

# Run without coverage (faster)
php vendor/bin/pest --no-coverage

# Run with coverage
php vendor/bin/pest --coverage
```

### From Module Root

```bash
cd /var/www/html/react-luma/vendor/genaker/react-luma

# Run all tests
php tests/vendor/bin/pest

# Run specific test file
php tests/vendor/bin/pest Unit/DeferJS.test.php
```

## Running JavaScript Tests

### From CSS Purge Tests Directory

```bash
cd /var/www/html/react-luma/vendor/genaker/react-luma/css-purge-tests

# Run all JavaScript tests
npm test

# Run tests in watch mode
npm run test:watch

# Run tests with coverage
npm run test:coverage
```

### Note on ES Modules

JavaScript tests use ES modules. Jest requires the experimental VM modules flag:
```bash
NODE_OPTIONS=--experimental-vm-modules npm test
```

This is already configured in `package.json` scripts, so `npm test` works directly.

## Running All Tests (PHP + JavaScript)


## PHP Test Coverage

The PHP test suite covers:

### ReactInjectPlugin Tests
- **Basic functionality** - Instantiation, configuration
- **Action filter** - Only allowed actions are optimized
- **Page type detection** - Product, category, search pages
- **Per-page CSS optimization** - Product/Category specific CSS
- **Critical CSS** - Inline vs preload configuration
- **JavaScript optimization** - RequireJS, React/Vue handling
- **Configuration variants** - All config combinations tested
- **Store-specific CSS** - Multi-store support

### DeferJS Tests
- **Desktop media query detection** - Identifies desktop-only stylesheets
- **Link tag extraction** - Extracts href and media attributes
- **Deferral logic** - Replaces link tags with deferred scripts
- **Configuration** - Admin config and GET parameter override
- **Default enabled** - Verifies default behavior
- **In-place replacement** - Script replaces link tag position
- **No-defer attribute** - Scripts preserved from being moved

**Current Status**: 19 tests passing (42 assertions)

## JavaScript Test Coverage

The JavaScript test suite (in `../css-purge-tests/`) covers:

- **formatFileSize()** - File size formatting (Bytes, KB, MB, GB)
- **getFileSize()** - Get file size from filesystem
- **countSelectors()** - Count CSS selectors and at-rules
- **applyIgnorePatterns()** - Remove CSS rules matching ignore patterns
- **applyBlocklistPatterns()** - Remove CSS rules matching blocklist patterns
- **loadPurgeConfig()** - Load configuration from JSON file
- **getCSSFileConfig()** - Get CSS file-specific configuration
- **Integration tests** - Real-world CSS scenarios

**Current Status**: 39 tests passing

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

### PHP Tests Not Running?

```bash
# Ensure PHP dependencies are installed
cd /var/www/html/react-luma/vendor/genaker/react-luma/tests
composer install

# Check Pest is accessible
vendor/bin/pest --version

# Check PHP version (requires PHP 8.1+)
php --version
```

### JavaScript Tests Not Running?

```bash
# Ensure JS dependencies are installed
cd /var/www/html/react-luma/vendor/genaker/react-luma/css-purge-tests
npm install

# Check Node.js version (requires Node.js 18+)
node --version

# Check Jest is accessible
npx jest --version
```

### PHPUnit Conflicts?

The selective autoloader prevents PHPUnit conflicts by:
- Loading Pest's PHPUnit 10+ first
- Filtering out PHPUnit classes from Magento's autoloader
- Using isolated vendor directory

### ES Module Issues in JavaScript Tests?

If you see "Cannot use import statement outside a module":
- Ensure `package.json` has `"type": "module"`
- Use `NODE_OPTIONS=--experimental-vm-modules` flag (already in npm scripts)
- Check Node.js version supports ES modules (Node 18+)

## Development

### Adding New PHP Tests

1. Create test file in `tests/Unit/` or `tests/Feature/`
2. Use Pest syntax:
```php
test('description', function () {
    expect($value)->toBe($expected);
});
```

3. Run the new test:
```bash
cd tests
vendor/bin/pest Unit/YourNewTest.test.php
```

### Adding New JavaScript Tests

1. Add tests to `css-purge-tests/css-purge.test.js` or create new test file
2. Use Jest syntax:
```javascript
test('description', () => {
    expect(value).toBe(expected);
});
```

3. Run the new test:
```bash
cd css-purge-tests
npm test
```

### Running Specific Tests

#### PHP Tests
```bash
cd tests

# Filter by name
vendor/bin/pest --filter="deferStylesL"
vendor/bin/pest --filter="product page"

# Run specific file
vendor/bin/pest Unit/DeferJS.test.php

# Run with group
vendor/bin/pest --group=unit
```

#### JavaScript Tests
```bash
cd css-purge-tests

# Run specific test file
npm test css-purge.test.js

# Run tests matching pattern
npm test -- -t "formatFileSize"

# Run in watch mode during development
npm run test:watch
```

## Test Summary

### PHP Tests
- **Framework**: Pest PHP (v2.36.0) with PHPUnit 10+
- **Location**: `tests/`
- **Test Files**: 
  - `Unit/DeferJS.test.php` (19+ tests)
  - `Unit/DeferCSS.test.php` (11 tests)
  - `Unit/Gallery.test.php` (10 tests)
  - `Unit/ReactInjectPlugin.test.php` (37+ tests)
- **Total**: 77+ tests passing

### JavaScript Tests
- **Framework**: Jest (v29.7.0)
- **Location**: `css-purge-tests/`
- **Test File**: `css-purge.test.js`
- **Total**: 39 tests passing

## References

- [Modern Pest Testing Architecture for Magento 2](https://yegorshytikov.medium.com/modern-pest-testing-architecture-for-magento-2-818d5ea2b406)
- [Pest PHP Documentation](https://pestphp.com/docs)
- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Jest ES Modules Support](https://jestjs.io/docs/ecmascript-modules)