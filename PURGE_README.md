# CSS Purge a React-Luma Tool for Magento and another platforms 

A powerful Node.js tool for purging unused CSS using PurgeCSS, with support for URL fetching, local content scanning, and advanced configuration options.

## Features

- 🎯 **CSS Purging**: Remove unused CSS selectors using PurgeCSS
- 🌐 **URL Fetching**: Automatically fetch content from URLs for purging
- 📁 **Local Content**: Scan local files and directories for content
- ⚙️ **File-specific Configuration**: Different settings per CSS file
- 🚫 **Ignore Patterns**: Remove development/debug classes before processing
- 🚫 **Blocklist**: Remove deprecated/legacy classes after processing
- 📊 **Detailed Reporting**: File sizes, selector counts, and reduction statistics
- 🎨 **Color Output**: Beautiful colored console output

## Installation

```bash
# Install dependencies
npm install

# Make sure you have the required packages
npm install postcss cssnano @fullhuman/postcss-purgecss node-fetch chokidar
```

## Basic Usage

### Simple CSS Purge
```bash
node css-purge.js --css path/to/your/file.css
```

### With URL Content
```bash
node css-purge.js --css styles.css --url https://example.com
```

### With Local Content
```bash
node css-purge.js --css styles.css --path ./templates/*.html
```

### Multiple Sources
```bash
node css-purge.js --css styles.css \
  --url https://example.com \
  --url https://example.com/about \
  --path ./templates/*.html \
  --path ./components/*.vue
```

## Command Line Options

| Option | Description | Example |
|--------|-------------|---------|
| `--css <file>` | CSS file to purge | `--css pub/static/styles.css` |
| `--url <url>` | Fetch content from URL | `--url https://example.com` |
| `--path <path>` | Use local file/directory | `--path ./templates/*.html` |
| `--config <file>` | Custom config file | `--config custom-purge.json` |

## Configuration File

Create a `purge.json` file for file-specific configurations:

```json
{
  "styles-m.min.css": {
    "content": {
      "urls": [
        "https://react-luma.cnxt.link",
        "https://react-luma.cnxt.link/men/bottoms-men/pants-men.html"
      ],
      "paths": [
        "content/react_luma_cnxt_link_1.html",
        "content/react_luma_cnxt_link_2.html"
      ]
    },
    "options": {
      "safelist": [
        "html",
        "body",
        "head",
        "script",
        "style"
      ],
      "ignore": [
        ".debug-*",
        ".test-*",
        ".temp-*",
        ".unused-*"
      ],
      "blocklist": [
        ".deprecated-*",
        ".old-*",
        ".legacy-*",
        ".vendor-prefix-*"
      ]
    }
  }
}
```

## Configuration Options

### Content Sources
- **urls**: Array of URLs to fetch content from
- **paths**: Array of local file paths/patterns

### Processing Options
- **safelist**: CSS classes to always keep (even if unused)
- **ignore**: Patterns to remove before PurgeCSS processing
- **blocklist**: Patterns to remove after PurgeCSS processing

## Pattern Matching

### Glob Patterns
The tool supports glob patterns for ignore and blocklist:

```json
{
  "ignore": [
    ".debug-*",        // Matches .debug-info, .debug-error, etc.
    ".test-*",         // Matches .test-component, .test-button, etc.
    ".temp-*",         // Matches .temp-styles, .temp-overlay, etc.
    ".unused-*"        // Matches .unused-class, .unused-element, etc.
  ],
  "blocklist": [
    ".deprecated-*",   // Matches .deprecated-button, .deprecated-form, etc.
    ".old-*",          // Matches .old-navigation, .old-header, etc.
    ".legacy-*",       // Matches .legacy-styles, .legacy-component, etc.
    ".vendor-prefix-*" // Matches .vendor-prefix-box, .vendor-prefix-grid, etc.
  ]
}
```

## Usage Examples

### 1. Basic Purge with Default Config
```bash
node css-purge.js --css pub/static/styles-m.min.css
```
Uses `purge.json` configuration for `styles-m.min.css`

### 2. Custom Content Sources
```bash
node css-purge.js --css styles.css \
  --url https://mysite.com \
  --url https://mysite.com/products \
  --path ./templates/*.html \
  --path ./components/*.vue
```

### 3. Custom Configuration File
```bash
node css-purge.js --css styles.css --config my-purge-config.json
```

### 4. Multiple CSS Files
```bash
# Purge each file with its specific configuration
node css-purge.js --css pub/static/styles-m.min.css
node css-purge.js --css pub/static/styles-l.min.css
node css-purge.js --css pub/static/product-styles-m.min.css
```

## Output Files

The tool creates purged CSS files with the naming convention:
- **Input**: `styles.css`
- **Output**: `styles.purged.min.css`

## Processing Flow

1. **Load Configuration**: Read file-specific config from `purge.json`
2. **Fetch URLs**: Download content from specified URLs
3. **Apply Ignore Patterns**: Remove development/debug classes
4. **PurgeCSS Processing**: Remove unused selectors
5. **Apply Blocklist**: Remove deprecated/legacy classes
6. **Minify**: Compress CSS with CSSNano
7. **Save Output**: Write final purged CSS file

## Example Output

```
📄 Loaded configuration from purge.json
  📋 Found specific configuration for styles-m.min.css
🌐 URLs detected for content fetching...
🌐 Fetching URLs for CSS purging...
  🌐 Fetching: https://react-luma.cnxt.link
  ✅ Fetched: https://react-luma.cnxt.link (71143 bytes)
  💾 Saved: ./content/react_luma_cnxt_link_1.html
📁 Saved 1 files to content folder
📁 Local paths detected for content...
  ✅ Added local path: content/react_luma_cnxt_link_1.html
🧹 Starting CSS purging for pub/static/styles-m.min.css...
  📏 Original file size: 165 KB
  🎯 Original selectors: 1601
  🚫 Applying ignore patterns...
  🚫 Applied ignore pattern: .debug-* (no matches found)
  🚫 Ignored 0 selectors
  📁 Using 2 content files for purging
  🚫 Applying blocklist patterns...
  🚫 Applied blocklist pattern: .deprecated-* (no matches found)
  🚫 Blocked 0 selectors
  ✅ CSS purging completed
  📁 Saved: pub/static/styles-m.purged.min.css
  📊 Final size: 55.1 KB (66.6% reduction)
  🎯 Final selectors: 634
🎉 CSS purging completed successfully!
📁 Output file: pub/static/styles-m.purged.min.css
```

## File Structure

```
vendor/genaker/magento-reactjs/
├── css-purge.js              # Main purge script
├── purge.json                # Default configuration
├── purge.json.example        # Comprehensive example configuration
├── custom-purge.json.example # Custom configuration examples
├── custom-purge.json         # Custom configuration example
├── PURGE_README.md           # This documentation
├── content/                  # Downloaded content files
│   ├── react_luma_cnxt_link_1.html
│   └── react_luma_cnxt_link_2.html
└── pub/static/               # CSS files
    ├── styles-m.min.css
    ├── styles-m.purged.min.css
    ├── styles-l.min.css
    └── styles-l.purged.min.css
```

## Configuration Examples

### Basic Configuration (`purge.json.example`)
The `purge.json.example` file contains comprehensive examples for:
- **Main Styles**: General website styles with multiple URLs and paths
- **Product Styles**: Product-specific styles with product templates
- **Category Styles**: Category page styles with catalog templates
- **Checkout Styles**: Checkout and cart page styles
- **Customer Styles**: Customer account and login styles
- **Admin Styles**: Admin panel styles

### Custom Configuration (`custom-purge.json.example`)
The `custom-purge.json.example` file shows examples for:
- **Component Styles**: Vue/React component styles
- **Mobile Styles**: Mobile-specific styles
- **Print Styles**: Print media styles
- **Dark Theme**: Dark theme styles
- **Animation Styles**: Animation and transition styles

### Getting Started
1. Copy `purge.json.example` to `purge.json`
2. Modify the configuration for your specific needs
3. Or create a custom configuration using `custom-purge.json.example` as a template

## Best Practices

### 1. Content Strategy
- **URLs**: Use for live website content
- **Local Paths**: Use for templates, components, and static files
- **Combination**: Mix both for comprehensive coverage

### 2. Pattern Management
- **Ignore**: Use for development artifacts (`.debug-*`, `.test-*`)
- **Blocklist**: Use for deprecated code (`.deprecated-*`, `.old-*`)
- **Safelist**: Use for critical classes (`.html`, `.body`, `.script`)

### 3. File Organization
- Create separate configurations for different CSS files
- Use descriptive pattern names
- Keep configurations maintainable

### 4. Testing
- Test with different content sources
- Verify critical classes are preserved
- Check file size reductions

## Troubleshooting

### Common Issues

1. **No Configuration Found**
   ```
   ⚠️  No specific configuration found for filename.css
   ```
   - Add configuration to `purge.json`
   - Check filename matching

2. **URL Fetch Failed**
   ```
   ❌ Failed to fetch https://example.com: HTTP 404: Not Found
   ```
   - Verify URL is accessible
   - Check network connectivity

3. **No Content Files**
   ```
   📁 Using 0 content files for purging
   ```
   - Add URLs or paths to configuration
   - Check file paths exist

### Debug Mode
For detailed debugging, check the console output for:
- Configuration loading status
- URL fetch results
- Pattern matching results
- File processing statistics

## Integration

### With Build Process
```bash
# Add to package.json scripts
{
  "scripts": {
    "purge-css": "node css-purge.js --css pub/static/styles-m.min.css",
    "purge-all": "npm run purge-css && node css-purge.js --css pub/static/styles-l.min.css"
  }
}
```

### With CI/CD
```bash
# Add to deployment pipeline
node css-purge.js --css pub/static/styles-m.min.css
node css-purge.js --css pub/static/styles-l.min.css
```

## Performance Tips

1. **Cache Content**: Downloaded content is saved in `./content/` folder
2. **Selective Purging**: Only purge files that need optimization
3. **Pattern Efficiency**: Use specific patterns rather than broad ones
4. **Content Optimization**: Use relevant content sources only

## Support

For issues and questions:
1. Check the console output for error messages
2. Verify configuration file syntax
3. Test with simpler configurations first
4. Review the processing flow and logs 
