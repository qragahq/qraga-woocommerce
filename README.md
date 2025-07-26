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

### Requirements

- **WordPress**: 5.8 or higher
- **WooCommerce**: 6.0 or higher  
- **PHP**: 7.4 or higher

### Install from ZIP

1. **Download** the latest release ZIP file
2. **Upload** via WordPress Admin → Plugins → Add New → Upload Plugin
3. **Activate** the plugin
4. **Configure** your Qraga settings in the admin dashboard

### Manual Installation

1. **Download** or extract the plugin files
2. **Upload** the `qraga-woocommerce` folder to `/wp-content/plugins/`
3. **Activate** via WordPress Admin → Plugins
4. **Configure** your settings

## Configuration

### Initial Setup

1. **Navigate** to **Qraga** in your WordPress admin menu
2. **Enter** your Qraga API credentials:
   - Site ID
   - Widget ID
   - API endpoints
3. **Configure** widget display settings
4. **Save** your settings

5. Initialy sync your products at **Bulk Sync**

### Widget Display

**For Classic Themes**:
- Use the WordPress Customizer to position the widget
- Choose from: After product summary, After single product, or Custom hook

**For Block Themes** (Gutenberg):
- Add the "Qraga Product Widget" block to your product page templates
- Configure block settings in the editor

## Development

### Building from Source

If you're building the plugin from source code:

```bash
# Install dependencies
npm install

# Build for production
npm run build

# Create distribution package
npm run package
```

### Development Commands

- `npm run dev` - Start development servers
- `npm run build` - Build for production  
- `npm run package` - Create ZIP package
- `npm run lint` - Check code quality

### Requirements for Development

- **Node.js**: 16.0 or higher
- **npm**: 8.0 or higher 