# WordPress Integration Guide - RareBeats Player

This guide explains how to embed your RareBeats player on your WordPress/WooCommerce website.

## 📦 Installation Options

### Option 1: WordPress Plugin (Recommended)

**Best for:** Easy integration with shortcodes on any page

#### Step 1: Install the Plugin

1. Download the plugin folder from `/app/wordpress-plugin/`
2. Zip the entire folder as `rarebeats-player.zip`
3. Go to WordPress Admin → Plugins → Add New → Upload Plugin
4. Upload `rarebeats-player.zip` and activate

#### Step 2: Configure Settings

1. Go to WordPress Admin → Settings → RareBeats Player
2. Set your player URL (currently: `https://audiomarket-1.preview.emergentagent.com`)
3. Set default height (optional)
4. Save settings

#### Step 3: Use Shortcodes

Add the shortcode to any page, post, or widget:

**Basic usage:**
```
[rarebeats_player]
```

**With custom height:**
```
[rarebeats_player height="1000px"]
```

**Filter by genre:**
```
[rarebeats_player genre="Trap"]
```

**Multiple options:**
```
[rarebeats_player height="900px" genre="Hip-Hop" mood="Dark"]
```

### Shortcode Attributes

| Attribute | Description | Default | Example |
|-----------|-------------|---------|---------|
| `height` | Player height | 800px | `height="1000px"` |
| `width` | Player width | 100% | `width="90%"` |
| `genre` | Filter by genre | - | `genre="Trap"` |
| `mood` | Filter by mood | - | `mood="Dark"` |
| `style` | Display style | full | `style="compact"` |

**Style Options:**
- `full` - Full width container
- `compact` - Max width 1200px
- `minimal` - Max width 900px, centered

---

## Option 2: Direct iFrame Embed

**Best for:** Quick embedding without a plugin

Add this HTML code directly to any page using the HTML block:

```html
<div style="width: 100%; height: 800px; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
    <iframe 
        src="https://audiomarket-1.preview.emergentagent.com"
        width="100%"
        height="100%"
        frameborder="0"
        allow="autoplay"
        title="RareBeats Music Player"
    ></iframe>
</div>
```

**With genre filter:**
```html
<iframe 
    src="https://audiomarket-1.preview.emergentagent.com?genre=Trap"
    width="100%"
    height="800px"
></iframe>
```

---

## Option 3: Build and Host on Your Domain

**Best for:** Complete control and custom domain

### Step 1: Build the React App

```bash
cd /app/frontend
yarn build
```

This creates a `build` folder with optimized static files.

### Step 2: Upload to WordPress

1. Upload the `build` folder contents to `/wp-content/rarebeats/`
2. Access via: `https://rarebeats.co.uk/wp-content/rarebeats/`

### Step 3: Update Plugin URL

1. Go to Settings → RareBeats Player
2. Change URL to: `https://rarebeats.co.uk/wp-content/rarebeats/`
3. Save

---

## 🎯 Usage Examples

### Homepage Integration

**Using Gutenberg:**
1. Edit your homepage
2. Add a "Shortcode" block
3. Enter: `[rarebeats_player height="900px"]`
4. Publish

**Using Page Builder (Elementor, etc.):**
1. Add a "Shortcode" widget
2. Enter: `[rarebeats_player]`
3. Customize height/width in widget settings

### Dedicated Beats Page

Create a new page called "Beats" or "Shop Beats":

```
[rarebeats_player style="full" height="100vh"]
```

This creates a full-page player experience.

### Genre-Specific Pages

**Trap Beats Page:**
```
[rarebeats_player genre="Trap"]
```

**Chill Beats Page:**
```
[rarebeats_player mood="Chill"]
```

### Sidebar Widget

1. Go to Appearance → Widgets
2. Add "Custom HTML" widget
3. Enter shortcode:
```
[rarebeats_player height="600px" style="compact"]
```

---

## 🚀 Deployment: Moving to Production

Currently, your player is hosted at `audiomarket-1.preview.emergentagent.com`. For production:

### Option A: Keep Current Hosting
- No changes needed
- Update plugin URL if domain changes
- Ensure CORS is enabled for rarebeats.co.uk

### Option B: Self-Host on Your Server

1. **Build the app:**
   ```bash
   cd /app/frontend
   yarn build
   ```

2. **Upload to your server:**
   - Upload `build` folder to your hosting
   - Place in a subdomain: `player.rarebeats.co.uk`
   - Or subfolder: `rarebeats.co.uk/player/`

3. **Update backend API:**
   - Ensure backend is accessible
   - Update `REACT_APP_BACKEND_URL` in `.env`
   - Rebuild frontend

4. **Update plugin settings:**
   - Change URL to your new hosting location

### Option C: Use CDN (Cloudflare, etc.)

1. Build the app
2. Upload to Cloudflare Pages, Netlify, or Vercel
3. Point custom domain
4. Update plugin URL

---

## 🔧 Advanced Customization

### Custom CSS Styling

Add to your theme's `style.css` or Customizer:

```css
/* Make player full width on mobile */
@media (max-width: 768px) {
    .rarebeats-player-container {
        height: 600px !important;
    }
}

/* Custom border */
.rarebeats-player-container {
    border: 2px solid #3b82f6;
}

/* Remove shadow */
.rarebeats-player-container {
    box-shadow: none;
}
```

### PHP Template Integration

Add directly to theme template files:

```php
<?php
// In page.php, single.php, or custom template
echo do_shortcode('[rarebeats_player height="900px"]');
?>
```

### Conditional Display

Show player only on specific pages:

```php
<?php
if (is_page('beats') || is_page('home')) {
    echo do_shortcode('[rarebeats_player]');
}
?>
```

---

## 📱 Responsive Considerations

The player is fully responsive, but you may want to adjust height for mobile:

```
[rarebeats_player height="600px"]
```

Or use CSS:

```css
@media (max-width: 768px) {
    .rarebeats-player-iframe {
        height: 500px !important;
    }
}
```

---

## 🔐 Security & Performance

### Enable CORS

If hosting separately, add to your backend `.env`:

```
CORS_ORIGINS="https://rarebeats.co.uk,https://www.rarebeats.co.uk"
```

### Caching

- Enable browser caching for static assets
- Use CDN for faster loading
- Consider lazy loading if player is below the fold

### SSL Certificate

Ensure both your WordPress site and player use HTTPS to avoid mixed content warnings.

---

## 🐛 Troubleshooting

### Player Not Loading

1. Check browser console for errors
2. Verify player URL is accessible
3. Check CORS settings if cross-domain
4. Ensure SSL certificate is valid

### Audio Not Playing

1. User must interact with page first (browser autoplay policy)
2. Check audio URLs in WooCommerce products
3. Verify `audio_url` meta field exists

### Styling Issues

1. Check for CSS conflicts with theme
2. Add `!important` to override theme styles
3. Use browser dev tools to inspect elements

---

## 📞 Support

For issues related to:
- **Plugin:** Check WordPress Admin → Settings → RareBeats Player
- **Player functionality:** Check browser console for errors
- **WooCommerce integration:** Verify API credentials in backend `.env`
- **Audio files:** Ensure URLs in product meta fields are accessible

---

## 🎉 Quick Start Summary

1. **Install plugin** from `/app/wordpress-plugin/`
2. **Activate** in WordPress Admin
3. **Configure** URL in Settings → RareBeats Player
4. **Add shortcode** `[rarebeats_player]` to any page
5. **Publish** and enjoy!

The easiest way to get started is with the WordPress plugin + shortcode approach. You can always move to self-hosting later if needed.
