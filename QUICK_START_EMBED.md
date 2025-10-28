# Quick Reference: Embed RareBeats Player

## Installation Steps

1. **Download Plugin**
   - Get files from `/app/wordpress-plugin/`
   - Create zip file: `rarebeats-player.zip`

2. **Upload to WordPress**
   - Admin → Plugins → Add New → Upload Plugin
   - Upload zip and activate

3. **Configure**
   - Admin → Settings → RareBeats Player
   - Set player URL: `https://audiomarket-1.preview.emergentagent.com`
   - Save settings

## Shortcode Examples

### Basic
```
[rarebeats_player]
```

### Custom Height
```
[rarebeats_player height="1000px"]
```

### Full Page
```
[rarebeats_player height="100vh" width="100%"]
```

### Filter by Genre
```
[rarebeats_player genre="Trap"]
```

### Filter by Mood
```
[rarebeats_player mood="Dark"]
```

### Multiple Filters
```
[rarebeats_player genre="Hip-Hop" mood="Chill" height="900px"]
```

### Compact Style
```
[rarebeats_player style="compact"]
```

## Common Pages

### Homepage
```
[rarebeats_player height="800px" style="full"]
```

### Beats Shop Page
```
[rarebeats_player height="100vh"]
```

### Genre-Specific Pages
```
[rarebeats_player genre="Trap"]
[rarebeats_player genre="Lo-Fi"]
[rarebeats_player genre="R&B"]
```

## Direct HTML (No Plugin)

```html
<iframe 
    src="https://audiomarket-1.preview.emergentagent.com"
    width="100%"
    height="800px"
    frameborder="0"
    allow="autoplay"
></iframe>
```

## File Structure

```
rarebeats-player/
├── rarebeats-player.php          # Main plugin file
└── assets/
    └── rarebeats-player.css      # Styles
```

## Available Filters

- **Genres:** Trap, Hip-Hop, R&B, Lo-Fi, Pop, Drill, etc.
- **Moods:** Dark, Chill, Uplifting, Aggressive, Smooth, etc.
- **Keys:** Am, C, Dm, F#m, G, etc.
- **BPM:** Use bpm_min and bpm_max in URL parameters

## Support

- Plugin settings: WordPress Admin → Settings → RareBeats Player
- Full docs: `/app/WORDPRESS_INTEGRATION.md`
- WooCommerce setup: `/app/WOOCOMMERCE_SETUP.md`
