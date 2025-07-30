#!/usr/bin/env node

import { readFileSync, writeFileSync, statSync, mkdirSync, existsSync } from 'fs';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';
import postcss from 'postcss';
import cssnano from 'cssnano';
import purgecss from '@fullhuman/postcss-purgecss';
import fetch from 'node-fetch';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Colors for console output
const colors = {
  reset: '\x1b[0m',
  bright: '\x1b[1m',
  red: '\x1b[31m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  magenta: '\x1b[35m',
  cyan: '\x1b[36m',
  white: '\x1b[37m'
};

// Helper function for colored logging
function log(message, color = 'white') {
  console.log(`${colors[color]}${message}${colors.reset}`);
}

// Helper function to format file size
function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// Helper function to get file size
function getFileSize(filePath) {
  try {
    const stats = statSync(filePath);
    return stats.size;
  } catch (error) {
    return 0;
  }
}

// Helper function to load purge configuration from JSON file
function loadPurgeConfig(configFile = 'purge.json') {
  try {
    if (existsSync(configFile)) {
      const configContent = readFileSync(configFile, 'utf8');
      const config = JSON.parse(configContent);
      log(`📄 Loaded configuration from ${configFile}`, 'blue');
      return config;
    }
  } catch (error) {
    log(`⚠️  Warning: Could not load configuration from ${configFile}: ${error.message}`, 'yellow');
  }
  return null;
}

// Helper function to get CSS file configuration
function getCSSFileConfig(config, cssFileName) {
  if (!config) return null;
  
  // Try exact filename match first
  if (config[cssFileName]) {
    log(`  📋 Found specific configuration for ${cssFileName}`, 'blue');
    return config[cssFileName];
  }
  
  // Try without .min.css extension
  const baseFileName = cssFileName.replace(/\.min\.css$/, '.css');
  if (config[baseFileName]) {
    log(`  📋 Found configuration for ${baseFileName}`, 'blue');
    return config[baseFileName];
  }
  
  // Try with just the base name
  const nameWithoutExt = cssFileName.replace(/\.(min\.)?css$/, '');
  if (config[nameWithoutExt]) {
    log(`  📋 Found configuration for ${nameWithoutExt}`, 'blue');
    return config[nameWithoutExt];
  }
  
  log(`  ⚠️  No specific configuration found for ${cssFileName}`, 'yellow');
  return null;
}

// Helper function to count CSS selectors
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

// Helper function to fetch URL content
async function fetchUrlContent(url) {
  try {
    log(`  🌐 Fetching: ${url}`, 'cyan');
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    const content = await response.text();
    log(`  ✅ Fetched: ${url} (${content.length} bytes)`, 'green');
    return content;
  } catch (error) {
    log(`  ❌ Failed to fetch ${url}: ${error.message}`, 'red');
    return null;
  }
}

// Helper function to save content to file
function saveContentToFile(filename, content) {
  try {
    const contentDir = './content';
    if (!existsSync(contentDir)) {
      mkdirSync(contentDir, { recursive: true });
    }
    
    const filePath = `${contentDir}/${filename}`;
    writeFileSync(filePath, content);
    log(`  💾 Saved: ${filePath}`, 'green');
    return filePath;
  } catch (error) {
    log(`  ❌ Failed to save ${filename}: ${error.message}`, 'red');
    return null;
  }
}

// Function to fetch URLs and save to content folder
async function fetchUrlsForPurging(urls) {
  log('🌐 Fetching URLs for CSS purging...', 'magenta');
  console.log('');
  
  const savedFiles = [];
  
  for (let i = 0; i < urls.length; i++) {
    const url = urls[i];
    const content = await fetchUrlContent(url);
    
    if (content) {
      // Create filename from URL
      const urlObj = new URL(url);
      const filename = `${urlObj.hostname.replace(/[^a-zA-Z0-9]/g, '_')}_${i + 1}.html`;
      const savedPath = saveContentToFile(filename, content);
      
      if (savedPath) {
        savedFiles.push(savedPath);
      }
    }
    
    // Add delay between requests to be respectful
    if (i < urls.length - 1) {
      await new Promise(resolve => setTimeout(resolve, 1000));
    }
  }
  
  console.log('');
  log(`📁 Saved ${savedFiles.length} files to content folder`, 'blue');
  return savedFiles;
}

// Helper function to apply ignore patterns to CSS content
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
      log(`  🚫 Applied ignore pattern: ${pattern} (removed ${matches.length} rules)`, 'yellow');
    } else {
      log(`  🚫 Applied ignore pattern: ${pattern} (no matches found)`, 'yellow');
    }
  }
  
  return filteredCSS;
}

// Helper function to apply blocklist patterns to CSS content
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
      log(`  🚫 Applied blocklist pattern: ${pattern} (removed ${matches.length} rules)`, 'red');
    } else {
      log(`  🚫 Applied blocklist pattern: ${pattern} (no matches found)`, 'red');
    }
  }
  
  return filteredCSS;
}

// Main CSS purging function
async function purgeCSS(inputFile, outputFile, contentFiles = [], config = null) {
  try {
    log(`🧹 Starting CSS purging for ${inputFile}...`, 'magenta');
    
    // Get original file size
    const originalFileSize = getFileSize(inputFile);
    log(`  📏 Original file size: ${formatFileSize(originalFileSize)}`, 'white');
    
    // Read CSS content for selector counting
    let cssContent = readFileSync(inputFile, 'utf8');
    const originalSelectors = countSelectors(cssContent);
    log(`  🎯 Original selectors: ${originalSelectors}`, 'white');
    
    // Apply ignore patterns if configured
    if (config && config.options && config.options.ignore) {
      log(`  🚫 Applying ignore patterns...`, 'yellow');
      cssContent = applyIgnorePatterns(cssContent, config.options.ignore);
      const afterIgnoreSelectors = countSelectors(cssContent);
      const ignoredSelectors = originalSelectors - afterIgnoreSelectors;
      log(`  🚫 Ignored ${ignoredSelectors} selectors`, 'yellow');
    }
    
    // Build content array for PurgeCSS
    const purgeContent = [
      './content/*.html'
    ];
    
    // Add content files if provided
    if (contentFiles.length > 0) {
      purgeContent.push(...contentFiles);
      log(`  📁 Using ${contentFiles.length} content files for purging`, 'blue');
    }
    
    // Prepare PurgeCSS configuration
    const purgeConfig = {
      content: purgeContent,
      defaultExtractor: content => content.match(/[\w-/:]+(?<!:)/g) || []
    };
    
    // Merge with JSON configuration if provided
    if (config && config.options) {
      if (config.options.safelist) {
        purgeConfig.safelist = config.options.safelist;
      }
      if (config.options.defaultExtractor) {
        // Note: This would need to be evaluated as a function, but for safety we'll keep the default
        log(`  ⚠️  Custom extractor from config not applied (using default)`, 'yellow');
      }
    }
    
    // Run PostCSS with purgecss and cssnano
    const plugins = [
      purgecss(purgeConfig),
      cssnano({ preset: 'default' })
    ];

    const result = await postcss(plugins).process(cssContent, { from: inputFile });
    let purgedCSS = result.css;
    
    // Apply blocklist patterns after PurgeCSS processing
    if (config && config.options && config.options.blocklist) {
      log(`  🚫 Applying blocklist patterns...`, 'red');
      const beforeBlocklistSelectors = countSelectors(purgedCSS);
      purgedCSS = applyBlocklistPatterns(purgedCSS, config.options.blocklist);
      const afterBlocklistSelectors = countSelectors(purgedCSS);
      const blockedSelectors = beforeBlocklistSelectors - afterBlocklistSelectors;
      log(`  🚫 Blocked ${blockedSelectors} selectors`, 'red');
    }
    
    log(`  ✅ CSS purging completed`, 'green');
    
    // Save output
    writeFileSync(outputFile, purgedCSS);
    
    // Get final file size from filesystem
    const finalFileSize = getFileSize(outputFile);
    const reduction = originalFileSize > 0 ? ((originalFileSize - finalFileSize) / originalFileSize * 100).toFixed(1) : 0;
    
    // Count final selectors
    const finalSelectors = countSelectors(purgedCSS);
    
    log(`  📁 Saved: ${outputFile}`, 'green');
    log(`  📊 Final size: ${formatFileSize(finalFileSize)} (${reduction}% reduction)`, 'yellow');
    log(`  🎯 Final selectors: ${finalSelectors}`, 'yellow');
    
    console.log(''); // Empty line for spacing
    
    return { 
      success: true, 
      originalSize: originalFileSize, 
      finalSize: finalFileSize, 
      reduction,
      originalSelectors,
      finalSelectors
    };
    
  } catch (error) {
    log(`❌ Error purging ${inputFile}:`, 'red');
    log(`   ${error.message}`, 'red');
    console.log(''); // Empty line for spacing
    return { success: false, error: error.message };
  }
}

// Main execution
async function main() {
  const args = process.argv.slice(2);
  
  // Parse command line arguments
  let cssFile = null;
  let urls = [];
  let localPaths = [];
  let configFile = null;
  
  for (let i = 0; i < args.length; i++) {
    const arg = args[i];
    
    if (arg === '--css' && i + 1 < args.length) {
      cssFile = args[i + 1];
      i++; // Skip next argument since we consumed it
    } else if (arg === '--config' && i + 1 < args.length) {
      configFile = args[i + 1];
      i++; // Skip next argument since we consumed it
    } else if (arg === '--url') {
      // Collect all URLs after --url until next parameter
      for (let j = i + 1; j < args.length; j++) {
        if (args[j].startsWith('--')) {
          break;
        }
        urls.push(args[j]);
      }
      // Don't break - continue to check for more --url parameters
    } else if (arg === '--path') {
      // Collect all paths after --path until next parameter
      for (let j = i + 1; j < args.length; j++) {
        if (args[j].startsWith('--')) {
          break;
        }
        localPaths.push(args[j]);
      }
      // Don't break - continue to check for more --path parameters
    }
  }
  
  if (!cssFile) {
    log('❌ Usage: node css-purge.js --css <css-file> [--config <config-file>] [--url <url1> <url2> ...] [--path <path1> <path2> ...]', 'red');
    log('   Example: node css-purge.js --css styles.css --url https://example.com --path ./templates/*.html', 'yellow');
    log('   Multiple parameters: --path p1 --path p2 --url u1 --url u2', 'yellow');
    log('   Configuration file: --config purge.json', 'yellow');
    process.exit(1);
  }
  
  // Check if input file exists
  if (!existsSync(cssFile)) {
    log(`❌ Input file not found: ${cssFile}`, 'red');
    process.exit(1);
  }
  
  // Load configuration from JSON file if specified
  let config = null;
  if (configFile) {
    config = loadPurgeConfig(configFile);
  } else {
    // Try to load default purge.json
    config = loadPurgeConfig();
  }
  
  // Get CSS file-specific configuration
  const cssFileName = cssFile.split('/').pop(); // Get just the filename
  const cssFileConfig = getCSSFileConfig(config, cssFileName);
  
  // Merge configuration with command line arguments
  if (cssFileConfig && cssFileConfig.content) {
    if (cssFileConfig.content.urls && cssFileConfig.content.urls.length > 0) {
      urls = [...new Set([...urls, ...cssFileConfig.content.urls])]; // Remove duplicates
    }
    if (cssFileConfig.content.paths && cssFileConfig.content.paths.length > 0) {
      localPaths = [...new Set([...localPaths, ...cssFileConfig.content.paths])]; // Remove duplicates
    }
  }
  
  // Handle --url parameter
  let contentFiles = [];
  if (urls.length > 0) {
    log('🌐 URLs detected for content fetching...', 'magenta');
    contentFiles = await fetchUrlsForPurging(urls);
  }
  
  // Handle --path parameter
  if (localPaths.length > 0) {
    log('📁 Local paths detected for content...', 'magenta');
    for (const path of localPaths) {
      if (existsSync(path)) {
        contentFiles.push(path);
        log(`  ✅ Added local path: ${path}`, 'green');
      } else {
        log(`  ❌ Local path not found: ${path}`, 'red');
      }
    }
    console.log('');
  }
  
  // Generate output filename
  const outputFile = cssFile.replace(/\.(css|min\.css)$/, '.purged.min.css');
  
  // Run CSS purging with configuration options
  const result = await purgeCSS(cssFile, outputFile, contentFiles, cssFileConfig);
  
  if (result.success) {
    log('🎉 CSS purging completed successfully!', 'green');
    log(`📁 Output file: ${outputFile}`, 'blue');
  } else {
    log('❌ CSS purging failed!', 'red');
    process.exit(1);
  }
}

// Run the script
main().catch(error => {
  log(`❌ Unexpected error: ${error.message}`, 'red');
  process.exit(1);
}); 