# CSS Purge Tests

This directory contains Jest tests for `css-purge.js`.

## Setup

Install dependencies:
```bash
npm install
```

## Running Tests

Run all tests:
```bash
npm test
```

Run tests in watch mode:
```bash
npm run test:watch
```

Run tests with coverage:
```bash
npm run test:coverage
```

## Test Coverage

The tests cover the following functions from `css-purge.js`:

- `formatFileSize()` - File size formatting (Bytes, KB, MB, GB)
- `getFileSize()` - Get file size from filesystem
- `countSelectors()` - Count CSS selectors and at-rules
- `applyIgnorePatterns()` - Remove CSS rules matching ignore patterns
- `applyBlocklistPatterns()` - Remove CSS rules matching blocklist patterns
- `loadPurgeConfig()` - Load configuration from JSON file
- `getCSSFileConfig()` - Get CSS file-specific configuration

## Issues Fixed

During testing, the following issue was identified and fixed:

- **formatFileSize function**: Updated to always show one decimal place (e.g., "500.0 Bytes" instead of "500 Bytes") for consistency.

## Test Structure

- **Unit Tests**: Test individual helper functions in isolation
- **Integration Tests**: Test functions working together with real-world scenarios

## Notes

- Tests use ES modules (type: "module" in package.json)
- Jest requires `NODE_OPTIONS=--experimental-vm-modules` flag for ES module support
- Test files are automatically cleaned up after each test run
