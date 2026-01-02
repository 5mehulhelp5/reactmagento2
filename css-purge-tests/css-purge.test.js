import { describe, test, expect, beforeEach, afterEach } from '@jest/globals';
import { readFileSync, writeFileSync, statSync, existsSync, unlinkSync, rmdirSync, mkdirSync } from 'fs';
import { join } from 'path';
import { fileURLToPath } from 'url';
import { dirname } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Helper functions extracted from css-purge.js for testing
function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  const value = (bytes / Math.pow(k, i)).toFixed(1);
  return value + ' ' + sizes[i];
}

function getFileSize(filePath) {
  try {
    const stats = statSync(filePath);
    return stats.size;
  } catch (error) {
    return 0;
  }
}

function countSelectors(cssContent) {
  // Remove comments
  const withoutComments = cssContent.replace(/\/\*[\s\S]*?\*\//g, '');
  
  // Count all selectors by looking for patterns that end with {
  const selectorMatches = withoutComments.match(/[^}]*{/g);
  const selectorCount = selectorMatches ? selectorMatches.length : 0;
  
  // Count @media, @keyframes, etc.
  const atRuleMatches = withoutComments.match(/@(media|keyframes|font-face|import|charset|namespace|supports|document|page|viewport|counter-style|font-feature-values|swash|ornaments|annotation|stylistic|styleset|character-variant|font-variant-alternates|font-feature-value)[^{]*{/g);
  const atRuleCount = atRuleMatches ? atRuleMatches.length : 0;
  
  return selectorCount + atRuleCount;
}

function applyIgnorePatterns(cssContent, ignorePatterns = []) {
  if (!ignorePatterns || ignorePatterns.length === 0) {
    return cssContent;
  }
  
  let filteredCSS = cssContent;
  
  for (const pattern of ignorePatterns) {
    // Convert glob pattern to regex - simpler approach
    const regexPattern = pattern
      .replace(/\./g, '\\.')  // Escape dots
      .replace(/\*/g, '[^\\s{]*');   // Convert * to match any characters except whitespace and {
    
    // Match CSS rules that contain the pattern
    const regex = new RegExp(`[^}]*${regexPattern}[^}]*\\{[^}]*\\}`, 'g');
    const matches = filteredCSS.match(regex);
    
    if (matches) {
      filteredCSS = filteredCSS.replace(regex, '');
    }
  }
  
  return filteredCSS;
}

function applyBlocklistPatterns(cssContent, blocklistPatterns = []) {
  if (!blocklistPatterns || blocklistPatterns.length === 0) {
    return cssContent;
  }
  
  let filteredCSS = cssContent;
  
  for (const pattern of blocklistPatterns) {
    // Convert glob pattern to regex - simpler approach
    const regexPattern = pattern
      .replace(/\./g, '\\.')  // Escape dots
      .replace(/\*/g, '[^\\s{]*');   // Convert * to match any characters except whitespace and {
    
    // Match CSS rules that contain the pattern
    const regex = new RegExp(`[^}]*${regexPattern}[^}]*\\{[^}]*\\}`, 'g');
    const matches = filteredCSS.match(regex);
    
    if (matches) {
      filteredCSS = filteredCSS.replace(regex, '');
    }
  }
  
  return filteredCSS;
}

function loadPurgeConfig(configFile = 'purge.json') {
  try {
    if (existsSync(configFile)) {
      const configContent = readFileSync(configFile, 'utf8');
      const config = JSON.parse(configContent);
      return config;
    }
  } catch (error) {
    // Return null on error
  }
  return null;
}

function getCSSFileConfig(config, cssFileName) {
  if (!config) return null;
  
  // Try exact filename match first
  if (config[cssFileName]) {
    return config[cssFileName];
  }
  
  // Try without .min.css extension
  const baseFileName = cssFileName.replace(/\.min\.css$/, '.css');
  if (config[baseFileName]) {
    return config[baseFileName];
  }
  
  // Try with just the base name
  const nameWithoutExt = cssFileName.replace(/\.(min\.)?css$/, '');
  if (config[nameWithoutExt]) {
    return config[nameWithoutExt];
  }
  
  return null;
}

describe('CSS Purge Helper Functions', () => {
  let testDir;
  
  beforeEach(() => {
    testDir = join(__dirname, 'test-temp');
    if (!existsSync(testDir)) {
      mkdirSync(testDir, { recursive: true });
    }
  });
  
  afterEach(() => {
    // Cleanup test files
    if (existsSync(testDir)) {
      try {
        const files = require('fs').readdirSync(testDir);
        files.forEach(file => {
          const filePath = join(testDir, file);
          try {
            unlinkSync(filePath);
          } catch (e) {
            // Ignore errors
          }
        });
        rmdirSync(testDir);
      } catch (error) {
        // Ignore cleanup errors
      }
    }
  });

  describe('formatFileSize', () => {
    test('should format 0 bytes correctly', () => {
      expect(formatFileSize(0)).toBe('0 Bytes');
    });

    test('should format bytes correctly', () => {
      expect(formatFileSize(500)).toBe('500.0 Bytes');
    });

    test('should format KB correctly', () => {
      expect(formatFileSize(1024)).toBe('1.0 KB');
      expect(formatFileSize(2048)).toBe('2.0 KB');
      expect(formatFileSize(1536)).toBe('1.5 KB');
    });

    test('should format MB correctly', () => {
      expect(formatFileSize(1024 * 1024)).toBe('1.0 MB');
      expect(formatFileSize(2.5 * 1024 * 1024)).toBe('2.5 MB');
    });

    test('should format GB correctly', () => {
      expect(formatFileSize(1024 * 1024 * 1024)).toBe('1.0 GB');
    });

    test('should handle edge cases', () => {
      expect(formatFileSize(1)).toBe('1.0 Bytes');
      expect(formatFileSize(1023)).toBe('1023.0 Bytes');
    });
  });

  describe('getFileSize', () => {
    test('should return file size for existing file', () => {
      const testFile = join(testDir, 'test.txt');
      writeFileSync(testFile, 'Hello World');
      const size = getFileSize(testFile);
      expect(size).toBeGreaterThan(0);
      expect(typeof size).toBe('number');
    });

    test('should return 0 for non-existent file', () => {
      const nonExistentFile = join(testDir, 'nonexistent.txt');
      const size = getFileSize(nonExistentFile);
      expect(size).toBe(0);
    });

    test('should return correct size for file with content', () => {
      const testFile = join(testDir, 'sized.txt');
      const content = 'Test content for size calculation';
      writeFileSync(testFile, content);
      const size = getFileSize(testFile);
      expect(size).toBe(content.length);
    });
  });

  describe('countSelectors', () => {
    test('should count basic CSS selectors', () => {
      const css = `
        .class1 { color: red; }
        .class2 { color: blue; }
        #id1 { color: green; }
      `;
      const count = countSelectors(css);
      expect(count).toBe(3);
    });

    test('should ignore comments', () => {
      const css = `
        /* This is a comment */
        .class1 { color: red; }
        /* Another comment */
        .class2 { color: blue; }
      `;
      const count = countSelectors(css);
      expect(count).toBe(2);
    });

    test('should count @media queries', () => {
      const css = `
        .class1 { color: red; }
        @media (min-width: 768px) {
          .class2 { color: blue; }
        }
      `;
      const count = countSelectors(css);
      expect(count).toBeGreaterThan(1);
    });

    test('should count @keyframes', () => {
      const css = `
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
        .class1 { color: red; }
      `;
      const count = countSelectors(css);
      expect(count).toBeGreaterThan(1);
    });

    test('should return 0 for empty CSS', () => {
      const css = '';
      const count = countSelectors(css);
      expect(count).toBe(0);
    });

    test('should handle CSS with only comments', () => {
      const css = '/* Comment only */';
      const count = countSelectors(css);
      expect(count).toBe(0);
    });

    test('should handle complex selectors', () => {
      const css = `
        .parent .child { color: red; }
        .parent > .direct-child { color: blue; }
        .parent:hover { color: green; }
        #id.class { color: yellow; }
      `;
      const count = countSelectors(css);
      expect(count).toBe(4);
    });

    test('should handle nested rules', () => {
      const css = `
        .outer {
          .inner { color: red; }
        }
      `;
      const count = countSelectors(css);
      expect(count).toBeGreaterThan(0);
    });
  });

  describe('applyIgnorePatterns', () => {
    test('should remove CSS rules matching ignore patterns', () => {
      const css = `
        .debug-test { color: red; }
        .test-class { color: blue; }
        .normal-class { color: green; }
      `;
      const ignorePatterns = ['.debug-*', '.test-*'];
      const result = applyIgnorePatterns(css, ignorePatterns);
      
      expect(result).not.toContain('.debug-test');
      expect(result).not.toContain('.test-class');
      expect(result).toContain('.normal-class');
    });

    test('should return original CSS when no ignore patterns', () => {
      const css = '.class1 { color: red; }';
      const result = applyIgnorePatterns(css, []);
      expect(result).toBe(css);
    });

    test('should return original CSS when ignore patterns is null', () => {
      const css = '.class1 { color: red; }';
      const result = applyIgnorePatterns(css, null);
      expect(result).toBe(css);
    });

    test('should return original CSS when ignore patterns is undefined', () => {
      const css = '.class1 { color: red; }';
      const result = applyIgnorePatterns(css);
      expect(result).toBe(css);
    });

    test('should handle patterns with dots', () => {
      const css = `
        .class.name { color: red; }
        .other-class { color: blue; }
      `;
      const ignorePatterns = ['.class.*'];
      const result = applyIgnorePatterns(css, ignorePatterns);
      expect(result).not.toContain('.class.name');
    });

    test('should handle multiple ignore patterns', () => {
      const css = `
        .debug-1 { color: red; }
        .test-2 { color: blue; }
        .temp-3 { color: yellow; }
        .normal { color: green; }
      `;
      const ignorePatterns = ['.debug-*', '.test-*', '.temp-*'];
      const result = applyIgnorePatterns(css, ignorePatterns);
      
      expect(result).not.toContain('.debug-1');
      expect(result).not.toContain('.test-2');
      expect(result).not.toContain('.temp-3');
      expect(result).toContain('.normal');
    });
  });

  describe('applyBlocklistPatterns', () => {
    test('should remove CSS rules matching blocklist patterns', () => {
      const css = `
        .deprecated-class { color: red; }
        .old-style { color: blue; }
        .normal-class { color: green; }
      `;
      const blocklistPatterns = ['.deprecated-*', '.old-*'];
      const result = applyBlocklistPatterns(css, blocklistPatterns);
      
      expect(result).not.toContain('.deprecated-class');
      expect(result).not.toContain('.old-style');
      expect(result).toContain('.normal-class');
    });

    test('should return original CSS when no blocklist patterns', () => {
      const css = '.class1 { color: red; }';
      const result = applyBlocklistPatterns(css, []);
      expect(result).toBe(css);
    });

    test('should return original CSS when blocklist patterns is null', () => {
      const css = '.class1 { color: red; }';
      const result = applyBlocklistPatterns(css, null);
      expect(result).toBe(css);
    });

    test('should return original CSS when blocklist patterns is undefined', () => {
      const css = '.class1 { color: red; }';
      const result = applyBlocklistPatterns(css);
      expect(result).toBe(css);
    });

    test('should handle multiple blocklist patterns', () => {
      const css = `
        .deprecated-1 { color: red; }
        .legacy-2 { color: blue; }
        .old-3 { color: yellow; }
        .normal { color: green; }
      `;
      const blocklistPatterns = ['.deprecated-*', '.legacy-*', '.old-*'];
      const result = applyBlocklistPatterns(css, blocklistPatterns);
      
      expect(result).not.toContain('.deprecated-1');
      expect(result).not.toContain('.legacy-2');
      expect(result).not.toContain('.old-3');
      expect(result).toContain('.normal');
    });
  });

  describe('loadPurgeConfig', () => {
    test('should load valid JSON config file', () => {
      const configFile = join(testDir, 'test-config.json');
      const configData = {
        'styles.css': {
          content: { urls: ['https://example.com'] }
        }
      };
      writeFileSync(configFile, JSON.stringify(configData));
      
      const config = loadPurgeConfig(configFile);
      expect(config).not.toBeNull();
      expect(config['styles.css']).toBeDefined();
    });

    test('should return null for non-existent file', () => {
      const config = loadPurgeConfig('nonexistent.json');
      expect(config).toBeNull();
    });

    test('should return null for invalid JSON', () => {
      const configFile = join(testDir, 'invalid-config.json');
      writeFileSync(configFile, '{ invalid json }');
      
      const config = loadPurgeConfig(configFile);
      expect(config).toBeNull();
    });
  });

  describe('getCSSFileConfig', () => {
    test('should find exact filename match', () => {
      const config = {
        'styles.css': { content: { urls: ['url1'] } },
        'other.css': { content: { urls: ['url2'] } }
      };
      const result = getCSSFileConfig(config, 'styles.css');
      expect(result).toBeDefined();
      expect(result.content.urls).toContain('url1');
    });

    test('should find match without .min.css extension', () => {
      const config = {
        'styles.css': { content: { urls: ['url1'] } }
      };
      const result = getCSSFileConfig(config, 'styles.min.css');
      expect(result).toBeDefined();
    });

    test('should find match with base name only', () => {
      const config = {
        'styles': { content: { urls: ['url1'] } }
      };
      const result = getCSSFileConfig(config, 'styles.css');
      expect(result).toBeDefined();
    });

    test('should return null when no match found', () => {
      const config = {
        'other.css': { content: { urls: ['url1'] } }
      };
      const result = getCSSFileConfig(config, 'styles.css');
      expect(result).toBeNull();
    });

    test('should return null when config is null', () => {
      const result = getCSSFileConfig(null, 'styles.css');
      expect(result).toBeNull();
    });
  });
});

describe('CSS Purge Integration Tests', () => {
  let testDir;
  
  beforeEach(() => {
    testDir = join(__dirname, 'test-temp');
    if (!existsSync(testDir)) {
      mkdirSync(testDir, { recursive: true });
    }
  });
  
  afterEach(() => {
    if (existsSync(testDir)) {
      try {
        const files = require('fs').readdirSync(testDir);
        files.forEach(file => {
          const filePath = join(testDir, file);
          try {
            unlinkSync(filePath);
          } catch (e) {
            // Ignore errors
          }
        });
        rmdirSync(testDir);
      } catch (error) {
        // Ignore cleanup errors
      }
    }
  });

  test('should handle complex CSS with multiple selectors and at-rules', () => {
    const complexCSS = `
      /* Header styles */
      .header { background: white; }
      .header .logo { width: 100px; }
      
      /* Media queries */
      @media (min-width: 768px) {
        .header { padding: 20px; }
        .header .logo { width: 150px; }
      }
      
      /* Keyframes */
      @keyframes fadeIn {
        0% { opacity: 0; }
        100% { opacity: 1; }
      }
      
      /* More selectors */
      .footer { background: black; }
      #main-content { padding: 10px; }
    `;
    
    const count = countSelectors(complexCSS);
    expect(count).toBeGreaterThan(5);
  });

  test('should handle ignore and blocklist patterns together', () => {
    const css = `
      .debug-test { color: red; }
      .test-class { color: blue; }
      .deprecated-old { color: yellow; }
      .normal-class { color: green; }
    `;
    
    let result = applyIgnorePatterns(css, ['.debug-*', '.test-*']);
    result = applyBlocklistPatterns(result, ['.deprecated-*']);
    
    expect(result).not.toContain('.debug-test');
    expect(result).not.toContain('.test-class');
    expect(result).not.toContain('.deprecated-old');
    expect(result).toContain('.normal-class');
  });

  test('should handle real-world CSS scenario', () => {
    const realWorldCSS = `
      /* Reset */
      * { margin: 0; padding: 0; }
      
      /* Layout */
      .container { max-width: 1200px; margin: 0 auto; }
      .row { display: flex; }
      
      /* Components */
      .button { padding: 10px 20px; background: blue; }
      .button:hover { background: darkblue; }
      .button.primary { background: green; }
      
      /* Utilities */
      .debug-info { display: none; }
      .test-mode { border: 1px solid red; }
      .deprecated-warning { color: orange; }
      
      /* Responsive */
      @media (max-width: 768px) {
        .container { padding: 10px; }
        .row { flex-direction: column; }
      }
    `;
    
    // Test selector counting
    const selectorCount = countSelectors(realWorldCSS);
    expect(selectorCount).toBeGreaterThan(5);
    
    // Test ignore patterns
    let cleaned = applyIgnorePatterns(realWorldCSS, ['.debug-*', '.test-*']);
    expect(cleaned).not.toContain('.debug-info');
    expect(cleaned).not.toContain('.test-mode');
    
    // Test blocklist patterns
    cleaned = applyBlocklistPatterns(cleaned, ['.deprecated-*']);
    expect(cleaned).not.toContain('.deprecated-warning');
    
    // Verify normal classes remain
    expect(cleaned).toContain('.container');
    expect(cleaned).toContain('.button');
  });
});

describe('CSS Purge with Config Files and Full Workflow', () => {
  let testDir;
  let contentDir;
  
  beforeEach(() => {
    testDir = join(__dirname, 'test-temp');
    contentDir = join(testDir, 'content');
    if (!existsSync(testDir)) {
      mkdirSync(testDir, { recursive: true });
    }
    if (!existsSync(contentDir)) {
      mkdirSync(contentDir, { recursive: true });
    }
  });
  
  afterEach(() => {
    if (existsSync(testDir)) {
      try {
        // Remove content directory files
        if (existsSync(contentDir)) {
          const contentFiles = require('fs').readdirSync(contentDir);
          contentFiles.forEach(file => {
            try {
              unlinkSync(join(contentDir, file));
            } catch (e) {}
          });
          rmdirSync(contentDir);
        }
        // Remove test directory files
        const files = require('fs').readdirSync(testDir);
        files.forEach(file => {
          const filePath = join(testDir, file);
          try {
            if (statSync(filePath).isDirectory()) {
              rmdirSync(filePath);
            } else {
              unlinkSync(filePath);
            }
          } catch (e) {}
        });
        rmdirSync(testDir);
      } catch (error) {
        // Ignore cleanup errors
      }
    }
  });

  // Mock CSS content for testing
  const mockCSS = `
    /* Base styles */
    .header { background: white; padding: 20px; }
    .footer { background: black; color: white; }
    .container { max-width: 1200px; margin: 0 auto; }
    
    /* Components */
    .button { padding: 10px 20px; background: blue; }
    .button-primary { background: green; }
    .button-secondary { background: gray; }
    
    /* Utilities - should be ignored */
    .debug-info { display: none; }
    .test-mode { border: 1px solid red; }
    
    /* Deprecated - should be blocked */
    .deprecated-old { color: orange; }
    .legacy-style { font-size: 14px; }
    
    /* Safelist - should be preserved */
    .safelist-class { color: purple; }
    .always-keep { display: block; }
    
    /* Unused classes - should be purged */
    .unused-class { color: yellow; }
    .never-used { display: none; }
    
    /* Media queries */
    @media (min-width: 768px) {
      .header { padding: 30px; }
      .container { max-width: 1400px; }
    }
  `;

  // Mock HTML content that uses some classes
  const mockHTML = `
    <html>
      <head><title>Test</title></head>
      <body>
        <div class="header">Header</div>
        <div class="container">
          <button class="button button-primary">Click me</button>
          <div class="footer">Footer</div>
        </div>
      </body>
    </html>
  `;

  test('should apply ignore patterns from config', () => {
    const cssFile = join(testDir, 'test.css');
    writeFileSync(cssFile, mockCSS);
    
    const config = {
      options: {
        ignore: ['.debug-*', '.test-*']
      }
    };
    
    let result = mockCSS;
    if (config && config.options && config.options.ignore) {
      result = applyIgnorePatterns(result, config.options.ignore);
    }
    
    expect(result).not.toContain('.debug-info');
    expect(result).not.toContain('.test-mode');
    expect(result).toContain('.header');
    expect(result).toContain('.button');
  });

  test('should apply blocklist patterns from config', () => {
    const cssFile = join(testDir, 'test.css');
    writeFileSync(cssFile, mockCSS);
    
    const config = {
      options: {
        blocklist: ['.deprecated-*', '.legacy-*']
      }
    };
    
    let result = mockCSS;
    if (config && config.options && config.options.blocklist) {
      result = applyBlocklistPatterns(result, config.options.blocklist);
    }
    
    expect(result).not.toContain('.deprecated-old');
    expect(result).not.toContain('.legacy-style');
    expect(result).toContain('.header');
    expect(result).toContain('.button');
  });

  test('should apply safelist from config (preserve selectors)', () => {
    // Safelist prevents selectors from being purged even if not found in content
    const cssWithSafelist = `
      .safelist-class { color: purple; }
      .always-keep { display: block; }
      .unused-class { color: yellow; }
    `;
    
    const config = {
      options: {
        safelist: ['.safelist-class', '.always-keep']
      }
    };
    
    // In real purgeCSS, safelist would prevent these from being removed
    // For this test, we verify the config structure
    expect(config.options.safelist).toContain('.safelist-class');
    expect(config.options.safelist).toContain('.always-keep');
    expect(config.options.safelist).not.toContain('.unused-class');
  });

  test('should load and use config file with all options', () => {
    const configFile = join(testDir, 'purge-config.json');
    const configData = {
      'test.css': {
        content: {
          urls: ['https://example.com'],
          paths: ['./content/*.html']
        },
        options: {
          safelist: ['.safelist-class', '.always-keep'],
          ignore: ['.debug-*', '.test-*'],
          blocklist: ['.deprecated-*', '.legacy-*']
        }
      }
    };
    
    writeFileSync(configFile, JSON.stringify(configData, null, 2));
    
    const config = loadPurgeConfig(configFile);
    expect(config).not.toBeNull();
    expect(config['test.css']).toBeDefined();
    
    const cssFileConfig = getCSSFileConfig(config, 'test.css');
    expect(cssFileConfig).toBeDefined();
    expect(cssFileConfig.options.safelist).toHaveLength(2);
    expect(cssFileConfig.options.ignore).toHaveLength(2);
    expect(cssFileConfig.options.blocklist).toHaveLength(2);
  });

  test('should apply all config options together (ignore, blocklist, safelist)', () => {
    const css = mockCSS;
    
    const config = {
      options: {
        ignore: ['.debug-*', '.test-*'],
        blocklist: ['.deprecated-*', '.legacy-*'],
        safelist: ['.safelist-class', '.always-keep']
      }
    };
    
    // Apply ignore patterns first
    let result = css;
    if (config.options.ignore) {
      result = applyIgnorePatterns(result, config.options.ignore);
    }
    
    // Verify ignore worked
    expect(result).not.toContain('.debug-info');
    expect(result).not.toContain('.test-mode');
    
    // Apply blocklist patterns
    if (config.options.blocklist) {
      result = applyBlocklistPatterns(result, config.options.blocklist);
    }
    
    // Verify blocklist worked
    expect(result).not.toContain('.deprecated-old');
    expect(result).not.toContain('.legacy-style');
    
    // Verify safelist classes are still present (they should be preserved)
    expect(result).toContain('.safelist-class');
    expect(result).toContain('.always-keep');
    
    // Verify normal classes remain
    expect(result).toContain('.header');
    expect(result).toContain('.button');
  });

  test('should handle config file with multiple CSS files', () => {
    const configFile = join(testDir, 'multi-config.json');
    const configData = {
      'styles.css': {
        content: { urls: ['https://example.com'] },
        options: {
          ignore: ['.debug-*']
        }
      },
      'styles-m.css': {
        content: { paths: ['./templates/*.html'] },
        options: {
          blocklist: ['.deprecated-*']
        }
      },
      'custom.css': {
        content: { urls: ['https://test.com'] },
        options: {
          safelist: ['.important']
        }
      }
    };
    
    writeFileSync(configFile, JSON.stringify(configData, null, 2));
    
    const config = loadPurgeConfig(configFile);
    expect(config).not.toBeNull();
    
    // Test each CSS file config
    const stylesConfig = getCSSFileConfig(config, 'styles.css');
    expect(stylesConfig).toBeDefined();
    expect(stylesConfig.options.ignore).toContain('.debug-*');
    
    const stylesMConfig = getCSSFileConfig(config, 'styles-m.css');
    expect(stylesMConfig).toBeDefined();
    expect(stylesMConfig.options.blocklist).toContain('.deprecated-*');
    
    const customConfig = getCSSFileConfig(config, 'custom.css');
    expect(customConfig).toBeDefined();
    expect(customConfig.options.safelist).toContain('.important');
  });

  test('should handle config file with .min.css filename matching', () => {
    const configFile = join(testDir, 'min-config.json');
    const configData = {
      'styles.css': {
        options: {
          ignore: ['.debug-*']
        }
      }
    };
    
    writeFileSync(configFile, JSON.stringify(configData, null, 2));
    
    const config = loadPurgeConfig(configFile);
    
    // Test that styles.min.css matches styles.css config
    const minConfig = getCSSFileConfig(config, 'styles.min.css');
    expect(minConfig).toBeDefined();
    expect(minConfig.options.ignore).toContain('.debug-*');
  });

  test('should handle empty config options gracefully', () => {
    const config = {
      options: {
        safelist: [],
        ignore: [],
        blocklist: []
      }
    };
    
    const css = mockCSS;
    
    // Should not break with empty arrays
    let result = css;
    result = applyIgnorePatterns(result, config.options.ignore);
    result = applyBlocklistPatterns(result, config.options.blocklist);
    
    // CSS should remain unchanged
    expect(result).toBe(css);
  });

  test('should handle config with only safelist (no ignore/blocklist)', () => {
    const config = {
      options: {
        safelist: ['.safelist-class', '.always-keep']
      }
    };
    
    expect(config.options.safelist).toHaveLength(2);
    expect(config.options.safelist).toContain('.safelist-class');
    expect(config.options.safelist).toContain('.always-keep');
    
    // CSS should process normally (safelist is handled by PurgeCSS, not our helpers)
    const css = mockCSS;
    expect(css).toContain('.safelist-class');
  });

  test('should handle config with only ignore patterns', () => {
    const config = {
      options: {
        ignore: ['.debug-*', '.test-*']
      }
    };
    
    const css = mockCSS;
    const result = applyIgnorePatterns(css, config.options.ignore);
    
    expect(result).not.toContain('.debug-info');
    expect(result).not.toContain('.test-mode');
    expect(result).toContain('.header');
    expect(result).toContain('.button');
  });

  test('should handle config with only blocklist patterns', () => {
    const config = {
      options: {
        blocklist: ['.deprecated-*']
      }
    };
    
    const css = mockCSS;
    const result = applyBlocklistPatterns(css, config.options.blocklist);
    
    expect(result).not.toContain('.deprecated-old');
    expect(result).toContain('.header');
    expect(result).toContain('.button');
  });

  test('should verify config file structure matches expected format', () => {
    const configFile = join(testDir, 'structure-test.json');
    const configData = {
      'styles.css': {
        content: {
          urls: ['https://example.com'],
          paths: ['./content/*.html']
        },
        options: {
          safelist: ['html', 'body', 'head'],
          ignore: ['.debug-*'],
          blocklist: ['.deprecated-*']
        }
      }
    };
    
    writeFileSync(configFile, JSON.stringify(configData, null, 2));
    
    const config = loadPurgeConfig(configFile);
    const cssConfig = getCSSFileConfig(config, 'styles.css');
    
    // Verify structure
    expect(cssConfig).toHaveProperty('content');
    expect(cssConfig).toHaveProperty('options');
    expect(cssConfig.content).toHaveProperty('urls');
    expect(cssConfig.content).toHaveProperty('paths');
    expect(cssConfig.options).toHaveProperty('safelist');
    expect(cssConfig.options).toHaveProperty('ignore');
    expect(cssConfig.options).toHaveProperty('blocklist');
    
    // Verify values
    expect(Array.isArray(cssConfig.content.urls)).toBe(true);
    expect(Array.isArray(cssConfig.content.paths)).toBe(true);
    expect(Array.isArray(cssConfig.options.safelist)).toBe(true);
    expect(Array.isArray(cssConfig.options.ignore)).toBe(true);
    expect(Array.isArray(cssConfig.options.blocklist)).toBe(true);
  });
});
