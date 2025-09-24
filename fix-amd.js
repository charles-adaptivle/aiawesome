#!/usr/bin/env node
/**
 * Post-build script to fix AMD module IDs for Moodle compatibility.
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const buildDir = path.join(__dirname, 'amd', 'build');

// Module mapping
const moduleMap = {
  'boot': 'local_aiawesome/boot',
  'simple_app': 'local_aiawesome/simple_app',
  'sse': 'local_aiawesome/sse'
};

console.log('Fixing AMD module IDs for Moodle...');

// Process each file in the build directory
fs.readdirSync(buildDir).forEach(fileName => {
  if (!fileName.endsWith('.js')) return;
  
  const filePath = path.join(buildDir, fileName);
  const baseName = fileName.replace('.js', '');
  const moduleId = moduleMap[baseName];
  
  if (!moduleId) {
    console.warn(`No module mapping found for ${baseName}`);
    return;
  }
  
  // Read the file
  let content = fs.readFileSync(filePath, 'utf8');
  
  // Fix the define call
  if (content.startsWith('define(')) {
    // Find the end of the define parameters
    const defineMatch = content.match(/^define\((.+?),\s*\[/);
    if (defineMatch) {
      // Replace with proper module ID
      content = content.replace(
        /^define\([^,]+,/,
        `define("${moduleId}",`
      );
      
      console.log(`Fixed ${fileName}: ${moduleId}`);
    } else {
      // Simple define without module ID
      content = content.replace(
        /^define\(\[/,
        `define("${moduleId}",[`
      );
      
      console.log(`Fixed ${fileName}: ${moduleId}`);
    }
  }
  
  // Write the fixed content back
  fs.writeFileSync(filePath, content, 'utf8');
});

console.log('AMD module ID fixes complete!');