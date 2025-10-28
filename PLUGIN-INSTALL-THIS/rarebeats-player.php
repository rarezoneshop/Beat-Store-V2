<?php
/**
 * Plugin Name: RareBeats Player - Self Contained
 * Plugin URI: https://rarebeats.co.uk
 * Description: Complete beat player/store running entirely within WordPress. No external hosting required.
 * Version: 2.0.2
 * Author: RareBeats
 * Author URI: https://rarebeats.co.uk
 * License: GPL2
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class RareBeats_Self_Contained {
    
    private $plugin_url;
    private $plugin_path;
    
    public function __construct() {
        $this->plugin_url = plugin_dir_url(__FILE__);
        $this->plugin_path = plugin_dir_path(__FILE__);
        
        // Register shortcode
        add_shortcode('rarebeats_player', array($this, 'render_player'));
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_api_routes'));
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Create cart table on activation
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    /**
     * Create database table for cart on activation
     */
    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rarebeats_cart';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id varchar(36) NOT NULL,
            product_id bigint(20) NOT NULL,
            variation_id bigint(20),
            name varchar(255) NOT NULL,
            license_type varchar(100) NOT NULL,
            price decimal(10,2) NOT NULL,
            audio_url text,
            image_url text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Register REST API routes
     */
    public function register_api_routes() {
        $namespace = 'rarebeats/v1';
        
        // Get products with filters
        register_rest_route($namespace, '/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_products'),
            'permission_callback' => '__return_true'
        ));
        
        // Get single product
        register_rest_route($namespace, '/products/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_product'),
            'permission_callback' => '__return_true'
        ));
        
        // Get available filters
        register_rest_route($namespace, '/filters', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_filters'),
            'permission_callback' => '__return_true'
        ));
        
        // Cart endpoints
        register_rest_route($namespace, '/cart', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_cart'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($namespace, '/cart', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_add_to_cart'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($namespace, '/cart/(?P<id>[a-zA-Z0-9-]+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'api_remove_from_cart'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($namespace, '/cart', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'api_clear_cart'),
            'permission_callback' => '__return_true'
        ));
        
        // Checkout
        register_rest_route($namespace, '/checkout', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_create_checkout'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * API: Get products with filters
     */
    public function api_get_products($request) {
        $genre = $request->get_param('genre');
        $mood = $request->get_param('mood');
        $key = $request->get_param('key');
        $bpm_min = $request->get_param('bpm_min');
        $bpm_max = $request->get_param('bpm_max');
        $per_page = $request->get_param('per_page') ?: 50;
        $page = $request->get_param('page') ?: 1;
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish'
        );
        
        // Meta query for filters
        $meta_query = array('relation' => 'AND');
        
        if ($genre) {
            $meta_query[] = array(
                'key' => 'genre',
                'value' => $genre,
                'compare' => 'LIKE'
            );
        }
        
        if ($mood) {
            $meta_query[] = array(
                'key' => 'mood',
                'value' => $mood,
                'compare' => 'LIKE'
            );
        }
        
        if ($key) {
            $meta_query[] = array(
                'key' => 'key',
                'value' => $key,
                'compare' => 'LIKE'
            );
        }
        
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }
        
        $query = new WP_Query($args);
        $products = array();
        
        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            
            // Get meta data
            $genre_val = get_post_meta($post->ID, 'genre', true);
            $bpm_val = get_post_meta($post->ID, 'bpm', true);
            $mood_val = get_post_meta($post->ID, 'mood', true);
            $key_val = get_post_meta($post->ID, 'key', true);
            $audio_url = get_post_meta($post->ID, 'audio_url', true);
            
            // BPM filtering
            if ($bpm_min && $bpm_val && intval($bpm_val) < intval($bpm_min)) continue;
            if ($bpm_max && $bpm_val && intval($bpm_val) > intval($bpm_max)) continue;
            
            // Get product images
            $image_id = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
            
            // Get variations
            $variations = array();
            if ($product->is_type('variable')) {
                $variations = $product->get_children();
            }
            
            $products[] = array(
                'id' => $post->ID,
                'name' => $post->post_title,
                'slug' => $post->post_name,
                'price' => $product->get_price(),
                'type' => $product->get_type(),
                'status' => $post->post_status,
                'genre' => $genre_val,
                'bpm' => $bpm_val,
                'mood' => $mood_val,
                'music_key' => $key_val,
                'audio_url' => $audio_url,
                'images' => array(
                    array('src' => $image_url)
                ),
                'variations' => $variations
            );
        }
        
        return rest_ensure_response(array(
            'products' => $products,
            'total' => count($products),
            'page' => $page
        ));
    }
    
    /**
     * API: Get single product with variations
     */
    public function api_get_product($request) {
        $product_id = $request->get_param('id');
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new WP_Error('not_found', 'Product not found', array('status' => 404));
        }
        
        $genre = get_post_meta($product_id, 'genre', true);
        $bpm = get_post_meta($product_id, 'bpm', true);
        $mood = get_post_meta($product_id, 'mood', true);
        $key = get_post_meta($product_id, 'key', true);
        $audio_url = get_post_meta($product_id, 'audio_url', true);
        
        $image_id = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
        
        // Get variations data
        $variations_data = array();
        if ($product->is_type('variable')) {
            $variations = $product->get_children();
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                $attributes = $variation->get_attributes();
                
                $variations_data[] = array(
                    'id' => $variation_id,
                    'price' => $variation->get_price(),
                    'regular_price' => $variation->get_regular_price(),
                    'attributes' => array_map(function($key, $value) {
                        return array(
                            'name' => ucwords(str_replace('pa_', '', $key)),
                            'option' => $value
                        );
                    }, array_keys($attributes), $attributes)
                );
            }
        }
        
        return rest_ensure_response(array(
            'id' => $product_id,
            'name' => $product->get_name(),
            'genre' => $genre,
            'bpm' => $bpm,
            'mood' => $mood,
            'music_key' => $key,
            'audio_url' => $audio_url,
            'images' => array(
                array('src' => $image_url)
            ),
            'variations' => $product->get_children(),
            'variations_data' => $variations_data
        ));
    }
    
    /**
     * API: Get available filters
     */
    public function api_get_filters() {
        global $wpdb;
        
        $genres = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'genre' AND meta_value != ''
        ");
        
        $moods = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'mood' AND meta_value != ''
        ");
        
        $keys = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'key' AND meta_value != ''
        ");
        
        $bpms = $wpdb->get_col("
            SELECT DISTINCT CAST(meta_value AS UNSIGNED) as bpm
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'bpm' AND meta_value != ''
            ORDER BY bpm
        ");
        
        $bpm_range = array(
            'min' => !empty($bpms) ? intval(min($bpms)) : 60,
            'max' => !empty($bpms) ? intval(max($bpms)) : 200
        );
        
        return rest_ensure_response(array(
            'genres' => array_values($genres),
            'moods' => array_values($moods),
            'keys' => array_values($keys),
            'bpm_range' => $bpm_range
        ));
    }
    
    /**
     * API: Add to cart
     */
    public function api_add_to_cart($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rarebeats_cart';
        
        $data = $request->get_json_params();
        $id = wp_generate_uuid4();
        
        $wpdb->insert(
            $table_name,
            array(
                'id' => $id,
                'product_id' => $data['product_id'],
                'variation_id' => $data['variation_id'] ?? null,
                'name' => $data['name'],
                'license_type' => $data['license_type'],
                'price' => $data['price'],
                'audio_url' => $data['audio_url'] ?? '',
                'image_url' => $data['image_url'] ?? ''
            )
        );
        
        return rest_ensure_response(array_merge($data, array('id' => $id)));
    }
    
    /**
     * API: Get cart
     */
    public function api_get_cart() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rarebeats_cart';
        
        $items = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        $total = array_sum(array_column($items, 'price'));
        
        return rest_ensure_response(array(
            'items' => $items,
            'total' => floatval($total)
        ));
    }
    
    /**
     * API: Remove from cart
     */
    public function api_remove_from_cart($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rarebeats_cart';
        $item_id = $request->get_param('id');
        
        $deleted = $wpdb->delete($table_name, array('id' => $item_id));
        
        if (!$deleted) {
            return new WP_Error('not_found', 'Item not found', array('status' => 404));
        }
        
        return rest_ensure_response(array('message' => 'Item removed from cart'));
    }
    
    /**
     * API: Clear cart
     */
    public function api_clear_cart() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rarebeats_cart';
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        return rest_ensure_response(array('message' => 'Cart cleared'));
    }
    
    /**
     * API: Create checkout URL
     */
    public function api_create_checkout() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rarebeats_cart';
        
        $items = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        
        if (empty($items)) {
            return new WP_Error('empty_cart', 'Cart is empty', array('status' => 400));
        }
        
        // Add items to WooCommerce cart
        WC()->cart->empty_cart();
        
        foreach ($items as $item) {
            if ($item['variation_id']) {
                WC()->cart->add_to_cart($item['product_id'], 1, $item['variation_id']);
            } else {
                WC()->cart->add_to_cart($item['product_id'], 1);
            }
        }
        
        $checkout_url = wc_get_cart_url();
        
        return rest_ensure_response(array(
            'checkout_url' => $checkout_url,
            'total_items' => count($items)
        ));
    }
    
    /**
     * Enqueue player assets
     */
    public function enqueue_assets() {
        // Only load on pages with shortcode
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'rarebeats_player')) {
            return;
        }
        
        // Find the actual CSS and JS files (hashed filenames)
        $plugin_dir = plugin_dir_path(__FILE__);
        $css_files = glob($plugin_dir . 'player/static/css/main.*.css');
        $js_files = glob($plugin_dir . 'player/static/js/main.*.js');
        
        // Enqueue CSS if found
        if (!empty($css_files)) {
            $css_file = basename($css_files[0]);
            wp_enqueue_style(
                'rarebeats-player-css',
                $this->plugin_url . 'player/static/css/' . $css_file,
                array(),
                '2.0.2'
            );
        }
        
        // Enqueue JS if found
        if (!empty($js_files)) {
            $js_file = basename($js_files[0]);
            wp_enqueue_script(
                'rarebeats-player-js',
                $this->plugin_url . 'player/static/js/' . $js_file,
                array(),
                '2.0.2',
                true
            );
            
            // Pass API URL to React
            wp_localize_script('rarebeats-player-js', 'rarebeatsConfig', array(
                'apiUrl' => rest_url('rarebeats/v1'),
                'nonce' => wp_create_nonce('wp_rest')
            ));
        }
    }
    
    /**
     * Render player shortcode
     */
    public function render_player($atts) {
        $atts = shortcode_atts(array(
            'height' => '800px',
        ), $atts);
        
        // Enqueue assets immediately when shortcode is called
        $this->enqueue_player_assets();
        
        ob_start();
        ?>
        <div id="rarebeats-player-root" class="rarebeats-container" style="height: <?php echo esc_attr($atts['height']); ?>; width: 100%; position: relative; background: #0a0a0f;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #666; text-align: center;">
                <p>Loading RareBeats Player...</p>
            </div>
        </div>
        <script type="text/javascript">
            window.REACT_APP_BACKEND_URL = '<?php echo rest_url('rarebeats/v1'); ?>';
            // Ensure React mounts after DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('RareBeats: DOM ready, root element:', document.getElementById('rarebeats-player-root'));
                });
            } else {
                console.log('RareBeats: Root element exists:', document.getElementById('rarebeats-player-root'));
            }
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue player assets for shortcode
     */
    private function enqueue_player_assets() {
        static $assets_enqueued = false;
        
        if ($assets_enqueued) {
            return;
        }
        
        $assets_enqueued = true;
        
        // Find the actual CSS and JS files (hashed filenames)
        $plugin_dir = plugin_dir_path(__FILE__);
        $css_files = glob($plugin_dir . 'player/static/css/main.*.css');
        $js_files = glob($plugin_dir . 'player/static/js/main.*.js');
        
        // Enqueue CSS if found
        if (!empty($css_files)) {
            $css_file = basename($css_files[0]);
            wp_enqueue_style(
                'rarebeats-player-css',
                $this->plugin_url . 'player/static/css/' . $css_file,
                array(),
                '2.0.2'
            );
        }
        
        // Enqueue JS if found
        if (!empty($js_files)) {
            $js_file = basename($js_files[0]);
            wp_enqueue_script(
                'rarebeats-player-js',
                $this->plugin_url . 'player/static/js/' . $js_file,
                array(),
                '2.0.2',
                true  // Load in footer
            );
            
            // Pass API URL to React
            wp_localize_script('rarebeats-player-js', 'rarebeatsConfig', array(
                'apiUrl' => rest_url('rarebeats/v1'),
                'nonce' => wp_create_nonce('wp_rest')
            ));
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'RareBeats Player',
            'RareBeats',
            'manage_options',
            'rarebeats-player',
            array($this, 'render_admin_page'),
            'dashicons-controls-play',
            30
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>RareBeats Player - Self Contained</h1>
            
            <div class="card">
                <h2>Setup Instructions</h2>
                <ol>
                    <li>Add custom fields to your products: <code>genre</code>, <code>bpm</code>, <code>mood</code>, <code>key</code>, <code>audio_url</code></li>
                    <li>Create variable products with license variations (Basic, Premium, Exclusive)</li>
                    <li>Use shortcode <code>[rarebeats_player]</code> on any page</li>
                </ol>
            </div>
            
            <div class="card">
                <h2>Shortcode Usage</h2>
                <p><code>[rarebeats_player]</code> - Default height (800px)</p>
                <p><code>[rarebeats_player height="1000px"]</code> - Custom height</p>
            </div>
            
            <div class="card">
                <h2>API Endpoints</h2>
                <p>Base URL: <code><?php echo rest_url('rarebeats/v1'); ?></code></p>
                <ul>
                    <li>GET /products - Get all products</li>
                    <li>GET /products/{id} - Get single product</li>
                    <li>GET /filters - Get filter options</li>
                    <li>GET /cart - Get cart items</li>
                    <li>POST /cart - Add to cart</li>
                    <li>DELETE /cart/{id} - Remove from cart</li>
                    <li>POST /checkout - Create checkout URL</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>Status</h2>
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'rarebeats_cart';
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
                ?>
                <p>Database Table: <?php echo $table_exists ? '✅ Created' : '❌ Not found'; ?></p>
                <p>WooCommerce: <?php echo class_exists('WooCommerce') ? '✅ Active' : '❌ Not installed'; ?></p>
                <p>REST API: <?php echo rest_url('rarebeats/v1'); ?></p>
            </div>
        </div>
        <?php
    }
}

// Initialize plugin
new RareBeats_Self_Contained();
