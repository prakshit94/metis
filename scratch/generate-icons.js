import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

// Path configuration
const jsonPath = path.join(rootDir, 'node_modules', 'bootstrap-icons', 'font', 'bootstrap-icons.json');
const scssPath = path.join(rootDir, 'resources', 'scss', 'components', '_bootstrap-icons-subset.scss');
const searchDirs = [
  path.join(rootDir, 'resources', 'views'),
  path.join(rootDir, 'resources', 'js')
];

// Load bootstrap icons mapping
if (!fs.existsSync(jsonPath)) {
  console.error(`Error: Could not find bootstrap-icons.json at ${jsonPath}`);
  process.exit(1);
}

const iconsMap = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));

// Regex to find icon class names
// We look for patterns like bi-[name] or bi-[name] inside class attributes or strings
const iconRegex = /\bbi-([a-zA-Z0-9-]+)\b/g;

const foundIcons = new Set();

// Function to recursively scan files
function scanDirectory(dir) {
  if (!fs.existsSync(dir)) return;
  const files = fs.readdirSync(dir);
  for (const file of files) {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    if (stat.isDirectory()) {
      scanDirectory(filePath);
    } else if (stat.isFile() && (file.endsWith('.php') || file.endsWith('.js') || file.endsWith('.html'))) {
      const content = fs.readFileSync(filePath, 'utf8');
      let match;
      while ((match = iconRegex.exec(content)) !== null) {
        const iconName = match[1];
        if (iconsMap[iconName] !== undefined) {
          foundIcons.add(iconName);
        } else {
          // Sometimes it might match standard classes like bi-lg or others that are not icons,
          // but we can check if they are in the iconsMap.
        }
      }
    }
  }
}

// Scan the directories
console.log('Scanning files for icons...');
for (const dir of searchDirs) {
  scanDirectory(dir);
}

// Also add a few standard icon names that might be dynamically constructed or needed
const forcedIcons = ['sun-fill', 'moon-stars-fill', 'arrows-fullscreen', 'search', 'file-text'];
for (const icon of forcedIcons) {
  if (iconsMap[icon] !== undefined) {
    foundIcons.add(icon);
  }
}

console.log(`Found ${foundIcons.size} unique icons.`);

// Sort icons alphabetically
const sortedIcons = Array.from(foundIcons).sort();

// Generate SCSS content
let scssContent = `// ==========================================================================
// Bootstrap Icons subset — only the icons actually referenced in this project.
// Automatically generated via scratch/generate-icons.js.
// ==========================================================================

$bi-font-path: '~bootstrap-icons/font/fonts' !default;

@font-face {
  font-display: block;
  font-family: 'bootstrap-icons';
  src: url('#{$bi-font-path}/bootstrap-icons.woff2') format('woff2'),
       url('#{$bi-font-path}/bootstrap-icons.woff') format('woff');
}

.bi::before,
[class^='bi-']::before,
[class*=' bi-']::before {
  display: inline-block;
  font-family: bootstrap-icons !important;
  font-style: normal;
  font-weight: normal !important;
  font-variant: normal;
  text-transform: none;
  line-height: 1;
  vertical-align: -0.125em;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

`;

for (const icon of sortedIcons) {
  const codeDecimal = iconsMap[icon];
  const codeHex = codeDecimal.toString(16);
  scssContent += `.bi-${icon}::before { content: "\\${codeHex}"; }\n`;
}

fs.writeFileSync(scssPath, scssContent, 'utf8');
console.log(`Successfully generated SCSS subset with ${sortedIcons.length} icons at ${scssPath}`);
