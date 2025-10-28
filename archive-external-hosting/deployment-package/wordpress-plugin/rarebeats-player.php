<?php
/**
 * Plugin Name: RareBeats Player
 * Plugin URI: https://rarebeats.co.uk
 * Description: Embed the RareBeats music player/store on any page using shortcode [rarebeats_player]
 * Version: 1.0.0
 * Author: RareBeats
 * Author URI: https://rarebeats.co.uk
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class RareBeats_Player {
    
    private $player_url;
    
    public function __construct() {
        // Set your player URL here
        $this->player_url = get_option('rarebeats_player_url', 'https://audiomarket-1.preview.emergentagent.com');
        
        // Register shortcode
        add_shortcode('rarebeats_player', array($this, 'render_player'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Enqueue styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Render the player via shortcode
     * Usage: [rarebeats_player height="800px" genre="Trap" style="full"]
     */
    public function render_player($atts) {
        $atts = shortcode_atts(array(
            'height' => '800px',
            'width' => '100%',
            'genre' => '',
            'mood' => '',
            'style' => 'full', // full, compact, minimal
        ), $atts);
        
        // Build player URL with filters
        $url = $this->player_url;
        $params = array();
        
        if (!empty($atts['genre'])) {
            $params['genre'] = $atts['genre'];
        }
        if (!empty($atts['mood'])) {
            $params['mood'] = $atts['mood'];
        }
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // Render iframe
        ob_start();
        ?>
        <div class="rarebeats-player-container" data-style="<?php echo esc_attr($atts['style']); ?>">
            <iframe 
                src="<?php echo esc_url($url); ?>"
                width="<?php echo esc_attr($atts['width']); ?>"
                height="<?php echo esc_attr($atts['height']); ?>"
                frameborder="0"
                allow="autoplay; fullscreen"
                class="rarebeats-player-iframe"
                title="RareBeats Music Player"
            ></iframe>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue custom styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'rarebeats-player-styles',
            plugin_dir_url(__FILE__) . 'assets/rarebeats-player.css',
            array(),
            '1.0.0'
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'RareBeats Player Settings',
            'RareBeats Player',
            'manage_options',
            'rarebeats-player',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('rarebeats_player_settings', 'rarebeats_player_url');
        register_setting('rarebeats_player_settings', 'rarebeats_player_default_height');
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>RareBeats Player Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('rarebeats_player_settings');
                do_settings_sections('rarebeats_player_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Player URL</th>
                        <td>
                            <input 
                                type="url" 
                                name="rarebeats_player_url" 
                                value="<?php echo esc_attr(get_option('rarebeats_player_url', 'https://audiomarket-1.preview.emergentagent.com')); ?>" 
                                class="regular-text"
                            />
                            <p class="description">The URL where your RareBeats player is hosted</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Default Height</th>
                        <td>
                            <input 
                                type="text" 
                                name="rarebeats_player_default_height" 
                                value="<?php echo esc_attr(get_option('rarebeats_player_default_height', '800px')); ?>" 
                                class="regular-text"
                            />
                            <p class="description">Default height for the player (e.g., 800px, 100vh)</p>
                        </td>
                    </tr>
                </table>
                
                <h2>Shortcode Usage</h2>
                <div class="card">
                    <h3>Basic Usage</h3>
                    <code>[rarebeats_player]</code>
                    
                    <h3>With Custom Height</h3>
                    <code>[rarebeats_player height="1000px"]</code>
                    
                    <h3>Filter by Genre</h3>
                    <code>[rarebeats_player genre="Trap"]</code>
                    
                    <h3>Filter by Mood</h3>
                    <code>[rarebeats_player mood="Dark"]</code>
                    
                    <h3>Multiple Filters</h3>
                    <code>[rarebeats_player genre="Trap" mood="Dark" height="900px"]</code>
                    
                    <h3>Available Attributes</h3>
                    <ul>
                        <li><strong>height</strong> - Player height (default: 800px)</li>
                        <li><strong>width</strong> - Player width (default: 100%)</li>
                        <li><strong>genre</strong> - Filter by genre (Trap, Hip-Hop, R&B, etc.)</li>
                        <li><strong>mood</strong> - Filter by mood (Dark, Chill, Uplifting, etc.)</li>
                        <li><strong>style</strong> - Display style: full, compact, minimal (default: full)</li>
                    </ul>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize plugin
new RareBeats_Player();
