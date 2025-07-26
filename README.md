# Qraga - AI-Powered Shopping Assistant

Turn your product pages into AI-guided experiences. Qraga provides intelligent product assistance that helps customers make confident buying decisions, seamlessly integrated into your WooCommerce store.

## Features

- **🤖 AI Product Assistant**: Intelligent shopping guidance that understands your products and customer needs
- **🎯 Smart Recommendations**: Personalized product suggestions based on customer preferences and behavior
- **💬 Interactive Q&A**: Customers can ask questions and get instant, accurate product information
- **🛍️ Confident Decisions**: Reduce cart abandonment by helping customers find exactly what they need
- **📱 Seamless Integration**: Works beautifully on both classic and modern WordPress themes
- **⚡ Easy Setup**: Simple configuration with powerful admin dashboard for monitoring and optimization

## Installation

### Prerequisites

- **WordPress**: 5.8 or higher
- **WooCommerce**: 6.0 or higher  
- **PHP**: 7.4 or higher
- **Node.js**: 16.0 or higher
- **npm**: 8.0 or higher
- **Docker**: For local development with wp-env

### Plugin Installation

1. **Download or Clone**:
   ```bash
   git clone https://github.com/your-org/qraga-woocommerce.git
   cd qraga-woocommerce
   ```

2. **Install Dependencies**:
   ```bash
   npm install
   ```

3. **Build Assets**:
   ```bash
   npm run build
   ```

4. **Upload to WordPress**:
   - Copy the plugin folder to `/wp-content/plugins/qraga-woocommerce/`
   - Or zip the folder and upload via WordPress admin

5. **Activate Plugin**:
   - Go to WordPress Admin → Plugins
   - Find "Qraga" and click Activate

6. **Configure Settings**:
   - Navigate to **Qraga** in your WordPress admin menu
   - Configure your Qraga API credentials and widget settings

### Local Development with wp-env

For local WordPress development, this plugin includes a `wp-env` configuration:

1. **Install wp-env globally**:
   ```bash
   npm install -g @wordpress/env
   ```

2. **Start local WordPress environment**:
   ```bash
   wp-env start
   ```

3. **Access your local site**:
   - WordPress: `http://localhost:8888`
   - Admin: `http://localhost:8888/wp-admin` (admin/password)
   - WooCommerce will be automatically installed and activated

4. **Stop the environment**:
   ```bash
   wp-env stop
   ```

## Development Setup

### Quick Start

```bash
# Install all dependencies
npm install

# Start development servers for both admin dashboard and Gutenberg block
npm run dev

# Build for production
npm run build
```

### Available Scripts

| Command | Description |
|---------|-------------|
| `npm run dev` | Start development servers for both admin and block |
| `npm run dev:admin` | Start React admin dashboard dev server (port 5178) |
| `npm run dev:block` | Start Gutenberg block development |
| `npm run build` | Build both admin dashboard and block for production |
| `npm run build:admin` | Build React admin dashboard only |
| `npm run build:block` | Build Gutenberg block only |
| `npm run watch` | Watch mode for both builds |
| `npm run lint` | Lint all code (TypeScript + JavaScript) |
| `npm run format` | Format block source code |
| `wp-env start` | Start local WordPress environment |
| `wp-env stop` | Stop local WordPress environment |
| `wp-env clean` | Clean/reset local environment |

### Development Workflow

1. **Start Local WordPress Environment**:
   ```bash
   wp-env start
   ```

2. **Start Development Builds**:
   ```bash
   npm run dev
   ```

3. **Admin Dashboard Development**:
   - React admin runs on `http://localhost:5178`
   - Hot reload enabled
   - TypeScript with strict mode
   - Tailwind CSS for styling

4. **Gutenberg Block Development**:
   - Uses WordPress Scripts
   - Live reload in WordPress editor
   - SCSS styling support
   - Test blocks at `http://localhost:8888/wp-admin`

5. **Build for Production**:
   ```bash
   npm run build
   ```

## Build System

### Unified Architecture

The plugin uses a **unified build system** that consolidates both the React admin dashboard and Gutenberg block builds:

- **Single `package.json`** with all dependencies
- **Shared tooling** (ESLint, TypeScript, build tools)
- **Concurrent development** for both admin and block
- **Consistent output paths** for WordPress integration

### Build Outputs

**React Admin Dashboard**:
- Source: `includes/admin/backend/src/`
- Output: `includes/admin/assets/js/main.js`
- Built with Vite + React + TypeScript

**Gutenberg Block**:
- Source: `includes/admin/blocks/qraga-product-widget/src/`
- Output: `includes/admin/blocks/qraga-product-widget/build/`
- Built with WordPress Scripts (Webpack)

### Configuration Files

```
build-configs/
├── vite.admin.config.ts      # Vite config for React admin
├── webpack.block.config.cjs  # Webpack config for Gutenberg block
└── tsconfig.json             # TypeScript for build configs
```

## Project Structure

```
qraga-woocommerce/
├── qraga.php                     # Main plugin file
├── package.json                  # Unified dependencies & scripts
├── README.md                     # This file
├── build-configs/                # Build configurations
├── includes/
│   ├── class-qraga-plugin.php    # Main plugin class
│   ├── class-qraga-widget-display.php
│   ├── admin/
│   │   ├── class-qraga-*.php     # Admin PHP classes
│   │   ├── assets/js/            # Built admin dashboard
│   │   ├── backend/src/          # React admin source
│   │   └── blocks/               # Gutenberg block
│   └── widgets/                  # WordPress widgets
└── assets/js/frontend/           # Frontend JavaScript
```

## What Qraga Does For Your Customers

### AI Shopping Assistant
- **Smart Guidance**: Helps customers understand products and make better choices
- **Instant Answers**: Responds to customer questions about products in real-time
- **Personalized Experience**: Adapts recommendations based on customer behavior

### Seamless Integration
- **Works Everywhere**: Automatically appears on product pages across all themes
- **Mobile Optimized**: Perfect experience on desktop, tablet, and mobile devices
- **Fast Loading**: Optimized performance that doesn't slow down your store

### Store Owner Benefits
- **Easy Management**: Simple admin dashboard to monitor performance and settings
- **Increased Sales**: Helps customers make confident purchase decisions
- **Reduced Support**: AI handles common product questions automatically

## API Integration

### Qraga Service Integration

The plugin communicates with Qraga services through:

1. **Automatic Product Sync**: Triggered on product save/update/delete
2. **Bulk Export**: Background processing for large product catalogs
3. **Widget Loading**: Dynamic widget initialization with product context
4. **Variant Updates**: Real-time variant selection handling

### WordPress Integration

- **WooCommerce Hooks**: Product lifecycle events
- **WordPress Customizer**: Widget positioning settings
- **Block Editor**: Native Gutenberg block support
- **REST API**: Custom endpoints for admin dashboard

## Configuration

### Required Settings

1. **Qraga API Credentials**:
   - Site ID
   - Widget ID
   - API endpoints

2. **Widget Settings**:
   - Display position (classic themes)
   - Block placement (block themes)
   - Styling options

### Environment Variables

For development, you can set:

```bash
# WordPress environment
WP_ENV=development

# Debug mode
WP_DEBUG=true
```

## Troubleshooting

### Build Issues

**Clean Installation**:
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

**Clear Build Outputs**:
```bash
rm -rf includes/admin/assets/js/*
rm -rf includes/admin/blocks/qraga-product-widget/build/*
npm run build
```

### Development Issues

**Port 5178 in use**:
- Stop other Vite processes
- Or modify port in `build-configs/vite.admin.config.ts`

**TypeScript Errors**:
- Run `npm run lint` to see all issues
- Check `tsconfig.json` configurations

**WordPress Integration**:
- Ensure WooCommerce is active
- Check PHP error logs
- Verify file permissions

**wp-env Issues**:
- Ensure Docker is running
- Use `wp-env clean` to reset environment
- Check port 8888 is available

### Common Issues

1. **Widget not displaying**: Check widget ID and site configuration
2. **Admin dashboard blank**: Verify build output and PHP compatibility
3. **Block not available**: Ensure block build completed successfully
4. **Sync failures**: Check API credentials and connectivity

## Contributing

### Development Standards

- **PHP**: WordPress Coding Standards
- **JavaScript/TypeScript**: ESLint + Prettier
- **React**: Functional components with hooks
- **CSS**: Tailwind CSS utility classes

### Pull Request Process

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and linting
5. Submit pull request with description

## Support

### Documentation
- WordPress Codex
- WooCommerce Developer Documentation
- React Documentation

### Issues
- GitHub Issues for bug reports
- WordPress.org support forums for general questions

## License

This plugin is licensed under the GPL v3.0 or later.

## Changelog

### Version 0.2.0
- ✅ Unified build system
- ✅ React admin dashboard
- ✅ Gutenberg block support
- ✅ TypeScript integration
- ✅ Modern development workflow

### Version 0.1.0
- Initial release
- AI-powered product assistance
- Smart customer guidance features 