#!/usr/bin/env node

import { readFileSync, writeFileSync, statSync } from 'fs';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';
import * as sass from 'sass';
import postcss from 'postcss';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano';
import { glob } from 'glob';
import chokidar from 'chokidar';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Color codes for console output
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

// Helper function for colored output
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

// Helper function to count CSS selectors
function countSelectors(cssContent) {
  // Remove comments first
  const withoutComments = cssContent.replace(/\/\*[\s\S]*?\*\//g, '');
  
  // Count selectors by looking for opening braces
  // This is a simplified approach - counts rule blocks
  const selectorMatches = withoutComments.match(/[^}]*{/g);
  const selectorCount = selectorMatches ? selectorMatches.length : 0;
  
  // Also count @media, @keyframes, @font-face, etc.
  const atRuleMatches = withoutComments.match(/@(media|keyframes|font-face|import|charset|namespace|supports|document|page|viewport|counter-style|font-feature-values|swash|ornaments|annotation|stylistic|styleset|character-variant|font-variant-alternates|font-feature-value)[^{]*{/g);
  const atRuleCount = atRuleMatches ? atRuleMatches.length : 0;
  
  return selectorCount + atRuleCount;
}

// Helper function to count SCSS selectors (simplified - just count all selectors)
function countSCSSSelectors(scssContent) {
  // Remove comments
  const withoutComments = scssContent.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\/\/.*$/gm, '');
  
  // Count all selectors by looking for patterns that end with {
  const selectorMatches = withoutComments.match(/[^}]*{/g);
  const selectorCount = selectorMatches ? selectorMatches.length : 0;
  
  // Count @media, @keyframes, etc.
  const atRuleMatches = withoutComments.match(/@(media|keyframes|font-face|import|charset|namespace|supports|document|page|viewport|counter-style|font-feature-values|swash|ornaments|annotation|stylistic|styleset|character-variant|font-variant-alternates|font-feature-value)[^{]*{/g);
  const atRuleCount = atRuleMatches ? atRuleMatches.length : 0;
  
  return selectorCount + atRuleCount;
}

async function compileSCSS(inputFile, outputFile, minify = true, isWatch = false) {
  try {
    if (isWatch) {
      log(`🔄 [WATCH] Compiling ${inputFile}...`, 'cyan');
    } else {
      log(`🔄 Compiling ${inputFile}...`, 'cyan');
    }
    
    // Get original file size
    const originalFileSize = getFileSize(inputFile);
    log(`  📏 Original file size: ${formatFileSize(originalFileSize)}`, 'white');
    
    // Read SCSS content for selector counting
    const scssContent = readFileSync(inputFile, 'utf8');
    const originalSelectors = countSCSSSelectors(scssContent);
    log(`  🎯 Original selectors: ${originalSelectors}`, 'white');
    
    // 1. Compile SCSS to CSS
    const result = sass.compile(inputFile, {
      style: "expanded",
      sourceMap: true
    });

    let css = result.css;
    log(`  ✅ Sass compilation completed`, 'green');

    // 2. Run PostCSS with autoprefixer and cssnano
    const plugins = [autoprefixer()];
    
    if (minify) plugins.push(cssnano({ preset: 'default' }));

    const postCSSResult = await postcss(plugins).process(css, { from: inputFile });
    css = postCSSResult.css;
    
    log(`  ✅ PostCSS processing completed`, 'green');

    // 3. Save output
    writeFileSync(outputFile, css);
    
    // Get final file size from filesystem
    const finalFileSize = getFileSize(outputFile);
    const reduction = originalFileSize > 0 ? ((originalFileSize - finalFileSize) / originalFileSize * 100).toFixed(1) : 0;
    
    // Count final selectors
    const finalSelectors = countSelectors(css);
    
    log(`  📁 Saved: ${outputFile}`, 'green');
    log(`  📊 Final size: ${formatFileSize(finalFileSize)} (${reduction}% reduction)`, 'yellow');
    log(`  🎯 Final selectors: ${finalSelectors}`, 'yellow');
    
    if (!isWatch) {
      console.log(''); // Empty line for spacing
    }
    
    return { 
      success: true, 
      originalSize: originalFileSize, 
      finalSize: finalFileSize, 
      reduction,
      originalSelectors,
      finalSelectors
    };
    
  } catch (error) {
    log(`❌ Error compiling ${inputFile}:`, 'red');
    log(`   ${error.message}`, 'red');
    if (!isWatch) {
      console.log(''); // Empty line for spacing
    }
    return { success: false, error: error.message };
  }
}

// Watch function for SCSS files
function watchSCSS() {
  log('�� Starting SCSS file watcher...', 'magenta');
  log('📂 Watching pub/static/*.scss files for changes...', 'blue');
  console.log('');
  
  chokidar.watch('pub/static/*.scss').on('change', async (path) => {
    const outputFile = path.replace('.scss', '.min.css');
    await compileSCSS(path, outputFile, true, true);
    log(`✅ [WATCH] Recompiled: ${path}`, 'green');
    console.log('');
  });
  
  log('🎯 Watcher is active. Press Ctrl+C to stop.', 'yellow');
}

// Compile all SCSS files in ./src/scss
async function compileAll() {
  log('🎨 Starting SCSS compilation...', 'magenta');
  console.log('');
  
  try {
    const files = await glob('pub/static/*.scss');
    log(`📂 Found ${files.length} SCSS files to compile:`, 'blue');
    
    // Show original file sizes and selector counts
    files.forEach(file => {
      const size = getFileSize(file);
      const scssContent = readFileSync(file, 'utf8');
      const selectors = countSCSSSelectors(scssContent);
      log(`  📄 ${file} (${formatFileSize(size)}, ${selectors} selectors)`, 'white');
    });
    
    console.log('');
    
    let successCount = 0;
    let errorCount = 0;
    let totalOriginalSize = 0;
    let totalFinalSize = 0;
    let totalOriginalSelectors = 0;
    let totalFinalSelectors = 0;
    
    for (const file of files) {
      const outputFile = file.replace('.scss', '.min.css');
      const result = await compileSCSS(file, outputFile, true, false);
      
      if (result.success) {
        successCount++;
        totalOriginalSize += result.originalSize;
        totalFinalSize += result.finalSize;
        totalOriginalSelectors += result.originalSelectors;
        totalFinalSelectors += result.finalSelectors;
      } else {
        errorCount++;
      }
    }
    
    console.log('');
    log('📋 Compilation Summary:', 'magenta');
    log(`  ✅ Successfully compiled: ${successCount} files`, 'green');
    if (errorCount > 0) {
      log(`  ❌ Errors: ${errorCount} files`, 'red');
    }
    log(`  🎯 Total files processed: ${files.length}`, 'blue');
    
    // Show total size reduction
    if (totalOriginalSize > 0) {
      const totalReduction = ((totalOriginalSize - totalFinalSize) / totalOriginalSize * 100).toFixed(1);
      log(`  📏 Total original size: ${formatFileSize(totalOriginalSize)}`, 'white');
      log(`  📏 Total final size: ${formatFileSize(totalFinalSize)}`, 'white');
      log(`  📊 Total reduction: ${formatFileSize(totalOriginalSize - totalFinalSize)} (${totalReduction}%)`, 'yellow');
    }
    
    // Show total selector information
    if (totalOriginalSelectors > 0) {
      log(`  🎯 Total original selectors: ${totalOriginalSelectors}`, 'white');
      log(`  🎯 Total final selectors: ${totalFinalSelectors}`, 'white');
      const selectorReduction = totalOriginalSelectors > 0 ? ((totalOriginalSelectors - totalFinalSelectors) / totalOriginalSelectors * 100).toFixed(1) : 0;
      log(`  📊 Selector reduction: ${totalOriginalSelectors - totalFinalSelectors} (${selectorReduction}%)`, 'yellow');
    }
    
  } catch (error) {
    log('❌ Error finding SCSS files:', 'red');
    log(`   ${error.message}`, 'red');
  }
}

// Check if watch mode is enabled
if (process.argv.includes('--watch')) {
  watchSCSS();
} else {
  compileAll();
}