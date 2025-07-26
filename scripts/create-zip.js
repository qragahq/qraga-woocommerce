import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import archiver from 'archiver';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.resolve(__dirname, '..');

// Read package.json to get plugin info
const packageJson = JSON.parse(fs.readFileSync(path.join(rootDir, 'package.json'), 'utf8'));
const pluginName = 'qraga-woocommerce';
const version = packageJson.version;

// Create dist directory if it doesn't exist
const distDir = path.join(rootDir, 'dist');
if (!fs.existsSync(distDir)) {
    fs.mkdirSync(distDir);
}

// Define files/folders to include in the zip
const filesToInclude = [
    'qraga.php',
    'includes/**/*.php',
    'includes/admin/assets/**/*',
    'includes/admin/blocks/qraga-product-widget/build/**/*',
    'includes/admin/blocks/qraga-product-widget/src/block.json',
    'assets/**/*',
    'README.md',
    'LICENSE',
    'readme.txt' // WordPress plugin readme if it exists
];

// Define files/folders to exclude
const filesToExclude = [
    'node_modules/**',
    'src/**',
    'build-configs/**',
    'scripts/**',
    'dist/**',
    '.git/**',
    '.github/**',
    '.vscode/**',
    '.idea/**',
    '*.log',
    '.DS_Store',
    'Thumbs.db',
    '*.map',
    'package.json',
    'package-lock.json',
    'pnpm-lock.yaml',
    'tsconfig*.json',
    '.eslintrc*',
    '.gitignore',
    '.prettierrc*',
    'vite.config.*',
    'webpack.config.*',
    '*.tsbuildinfo',
    'includes/admin/backend/src/**',
    'includes/admin/blocks/qraga-product-widget/src/**/*.js',
    'includes/admin/blocks/qraga-product-widget/src/**/*.scss',
    'includes/admin/blocks/qraga-product-widget/package.json'
];

const zipFilename = `${pluginName}-${version}.zip`;
const zipPath = path.join(distDir, zipFilename);

// Remove existing zip if it exists
if (fs.existsSync(zipPath)) {
    fs.unlinkSync(zipPath);
}

// Create zip archive
const output = fs.createWriteStream(zipPath);
const archive = archiver('zip', {
    zlib: { level: 9 } // Maximum compression
});

output.on('close', () => {
    console.log(`\n✅ Package created successfully!`);
    console.log(`📦 File: ${zipFilename}`);
    console.log(`📁 Location: ${zipPath}`);
    console.log(`📏 Size: ${(archive.pointer() / 1024 / 1024).toFixed(2)} MB`);
    console.log(`\nReady for distribution! 🚀`);
});

archive.on('error', (err) => {
    console.error('❌ Error creating zip:', err);
    process.exit(1);
});

archive.pipe(output);

// Function to check if a file should be excluded
function shouldExclude(filePath) {
    const relativePath = path.relative(rootDir, filePath);
    
    // Check against exclude patterns
    for (const excludePattern of filesToExclude) {
        const pattern = excludePattern.replace(/\*\*/g, '.*').replace(/\*/g, '[^/]*');
        const regex = new RegExp(`^${pattern}$`);
        if (regex.test(relativePath) || regex.test(relativePath + '/')) {
            return true;
        }
    }
    return false;
}

// Function to recursively add files to archive
function addFilesToArchive(dir, baseDir = '') {
    const files = fs.readdirSync(dir);
    
    for (const file of files) {
        const filePath = path.join(dir, file);
        const relativePath = path.join(baseDir, file);
        const archivePath = path.join(pluginName, relativePath);
        
        if (shouldExclude(filePath)) {
            continue;
        }
        
        const stats = fs.statSync(filePath);
        
        if (stats.isDirectory()) {
            addFilesToArchive(filePath, relativePath);
        } else {
            archive.file(filePath, { name: archivePath });
        }
    }
}

console.log(`📦 Creating ${pluginName} v${version} package...`);
console.log(`🔨 Building archive: ${zipFilename}`);

try {
    addFilesToArchive(rootDir);
    archive.finalize();
} catch (error) {
    console.error('❌ Error creating package:', error);
    process.exit(1);
} 