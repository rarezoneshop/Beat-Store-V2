# RareBeats Player - Self-Contained WordPress Plugin

## Overview

This is a **completely self-contained** WordPress plugin that runs your entire beat store inside WordPress. No external hosting, APIs, or databases needed.

## What's Included

- ✅ React player (embedded)
- ✅ PHP REST API (WordPress native)
- ✅ WordPress database integration (no MongoDB needed)
- ✅ WooCommerce direct integration
- ✅ Shopping cart system
- ✅ All features from the standalone version

## Installation (2 Minutes)

1. **Upload Plugin**
   - Download `rarebeats-self-contained.zip`
   - Go to WordPress Admin → Plugins → Add New → Upload
   - Upload and activate

2. **Add Shortcode**
   - Edit any page
   - Add shortcode: `[rarebeats_player]`
   - Publish

3. **Configure Products**
   - Add custom fields to WooCommerce products:
     - `genre` - Hip-Hop, Trap, R&B, etc.
     - `bpm` - 85, 140, 120, etc.
     - `mood` - Dark, Chill, Uplifting, etc.
     - `key` - Am, C, Dm, etc.
     - `audio_url` - Full URL to audio file

4. **Done!** Player is live on your page

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+
- Pretty permalinks enabled

## Features

### ✅ Everything Works Offline
- No external API calls (except audio files)
- All data from WordPress database
- Fast and reliable

### ✅ Native WordPress Integration
- Uses WordPress REST API
- Custom database table for cart
- WooCommerce products integration
- WordPress admin panel

### ✅ Complete Feature Set
- iTunes-style now playing header
- Row-based playlist layout
- Genre, BPM, mood, key filtering
- Audio player with controls
- License selection
- Shopping cart
- Checkout to WooCommerce

## Shortcode Options

```
[rarebeats_player]
```

Custom height:
```
[rarebeats_player height="1000px"]
```

## How It Works

### Data Flow
```
WordPress Products (WooCommerce)
         ↓
Custom Meta Fields (genre, bpm, etc.)
         ↓
WordPress REST API (/wp-json/rarebeats/v1/)
         ↓
React Player (embedded in page)
         ↓
WooCommerce Cart → Checkout
```

### Database
- Uses WordPress database (no MongoDB)
- Custom table: `wp_rarebeats_cart`
- All cart data in WordPress

### API Endpoints
All internal to WordPress:

- `GET /wp-json/rarebeats/v1/products` - Get products
- `GET /wp-json/rarebeats/v1/products/{id}` - Get single product
- `GET /wp-json/rarebeats/v1/filters` - Get filter options
- `POST /wp-json/rarebeats/v1/cart` - Add to cart
- `GET /wp-json/rarebeats/v1/cart` - Get cart
- `DELETE /wp-json/rarebeats/v1/cart/{id}` - Remove item
- `POST /wp-json/rarebeats/v1/checkout` - Create checkout

## Product Setup

### Using ACF (Advanced Custom Fields)

1. Install ACF plugin
2. Create Field Group for "Products"
3. Add fields:
   - Text: `genre`
   - Number: `bpm`
   - Text: `mood`
   - Text: `key`
   - URL: `audio_url`

### Using Code (functions.php)

```php
// Add meta box
add_action('add_meta_boxes', 'rarebeats_add_meta_box');
function rarebeats_add_meta_box() {
    add_meta_box(
        'rarebeats_meta',
        'Beat Information',
        'rarebeats_meta_box_callback',
        'product',
        'normal',
        'high'
    );
}

// Meta box callback
function rarebeats_meta_box_callback($post) {
    $genre = get_post_meta($post->ID, 'genre', true);
    $bpm = get_post_meta($post->ID, 'bpm', true);
    $mood = get_post_meta($post->ID, 'mood', true);
    $key = get_post_meta($post->ID, 'key', true);
    $audio_url = get_post_meta($post->ID, 'audio_url', true);
    ?>
    <p>
        <label>Genre:</label>
        <input type="text" name="genre" value="<?php echo esc_attr($genre); ?>" />
    </p>
    <p>
        <label>BPM:</label>
        <input type="number" name="bpm" value="<?php echo esc_attr($bpm); ?>" />
    </p>
    <p>
        <label>Mood:</label>
        <input type="text" name="mood" value="<?php echo esc_attr($mood); ?>" />
    </p>
    <p>
        <label>Key:</label>
        <input type="text" name="key" value="<?php echo esc_attr($key); ?>" />
    </p>
    <p>
        <label>Audio URL:</label>
        <input type="url" name="audio_url" value="<?php echo esc_attr($audio_url); ?>" style="width: 100%;" />
    </p>
    <?php
}

// Save meta
add_action('save_post', 'rarebeats_save_meta');
function rarebeats_save_meta($post_id) {
    if (isset($_POST['genre'])) update_post_meta($post_id, 'genre', sanitize_text_field($_POST['genre']));
    if (isset($_POST['bpm'])) update_post_meta($post_id, 'bpm', sanitize_text_field($_POST['bpm']));
    if (isset($_POST['mood'])) update_post_meta($post_id, 'mood', sanitize_text_field($_POST['mood']));
    if (isset($_POST['key'])) update_post_meta($post_id, 'key', sanitize_text_field($_POST['key']));
    if (isset($_POST['audio_url'])) update_post_meta($post_id, 'audio_url', esc_url($_POST['audio_url']));
}
```

## Troubleshooting

### Player Not Loading
- Check if WooCommerce is installed and active
- Verify permalinks are enabled (Settings → Permalinks → Post name)
- Check browser console for errors

### Products Not Showing
- Verify products are published
- Check custom meta fields are set
- Test API: Visit `/wp-json/rarebeats/v1/products`

### Audio Not Playing
- Verify `audio_url` meta field contains valid URL
- Check audio file is accessible
- Ensure HTTPS if site uses SSL

### Checkout Not Working
- Verify WooCommerce is active
- Check WooCommerce cart page exists
- Test adding products manually to cart

## Advantages Over External Hosting

✅ **No Monthly Fees** - Everything runs on your WordPress hosting
✅ **No Dependencies** - Works independently  
✅ **Fast** - No external API calls
✅ **Reliable** - Uses WordPress infrastructure
✅ **Easy Updates** - Standard WordPress plugin updates
✅ **Data Control** - All data in your WordPress database
✅ **Single Login** - Manage everything from WordPress admin

## File Structure

```
rarebeats-player/
├── rarebeats-player.php    # Main plugin file
├── player/                 # React player assets
│   ├── static/
│   │   ├── css/
│   │   │   └── main.css   # Player styles
│   │   └── js/
│   │       └── main.js    # Player JavaScript
│   └── index.html
└── README.md
```

## Support

- **Plugin Settings**: WordPress Admin → RareBeats
- **API Testing**: Visit `/wp-json/rarebeats/v1/products`
- **Database**: Check `wp_rarebeats_cart` table in phpMyAdmin

## Updates

To update the plugin:
1. Download new version
2. Deactivate old version
3. Upload and activate new version
4. Done!

Database table and settings are preserved.

## License

GPL v2 or later

---

**Simple. Self-contained. No external dependencies.**
