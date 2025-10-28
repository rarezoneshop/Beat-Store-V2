# Sample WooCommerce Product Setup

## Option 1: Using WordPress Functions.php

Add this code to your theme's `functions.php` file to add custom meta fields to the product edit page:

```php
<?php
// Add custom meta fields to product page
add_action('woocommerce_product_options_general_product_data', 'rarebeats_add_custom_fields');
function rarebeats_add_custom_fields() {
    echo '<div class="options_group">';
    
    // Genre field
    woocommerce_wp_text_input(array(
        'id' => 'genre',
        'label' => __('Genre', 'woocommerce'),
        'placeholder' => 'e.g., Hip Hop, Trap, R&B',
        'desc_tip' => 'true',
        'description' => __('Enter the music genre', 'woocommerce')
    ));
    
    // BPM field
    woocommerce_wp_text_input(array(
        'id' => 'bpm',
        'label' => __('BPM', 'woocommerce'),
        'placeholder' => 'e.g., 140',
        'type' => 'number',
        'desc_tip' => 'true',
        'description' => __('Enter beats per minute', 'woocommerce')
    ));
    
    // Mood field
    woocommerce_wp_text_input(array(
        'id' => 'mood',
        'label' => __('Mood', 'woocommerce'),
        'placeholder' => 'e.g., Dark, Uplifting, Chill',
        'desc_tip' => 'true',
        'description' => __('Enter the mood/vibe', 'woocommerce')
    ));
    
    // Key field
    woocommerce_wp_text_input(array(
        'id' => 'key',
        'label' => __('Musical Key', 'woocommerce'),
        'placeholder' => 'e.g., Am, C, F#',
        'desc_tip' => 'true',
        'description' => __('Enter the musical key', 'woocommerce')
    ));
    
    // Audio URL field
    woocommerce_wp_text_input(array(
        'id' => 'audio_url',
        'label' => __('Audio Preview URL', 'woocommerce'),
        'placeholder' => 'https://your-storage.com/beats/preview.mp3',
        'desc_tip' => 'true',
        'description' => __('Enter the full URL to the audio preview file', 'woocommerce')
    ));
    
    echo '</div>';
}

// Save custom meta fields
add_action('woocommerce_process_product_meta', 'rarebeats_save_custom_fields');
function rarebeats_save_custom_fields($post_id) {
    $product = wc_get_product($post_id);
    
    $genre = isset($_POST['genre']) ? sanitize_text_field($_POST['genre']) : '';
    $product->update_meta_data('genre', $genre);
    
    $bpm = isset($_POST['bpm']) ? sanitize_text_field($_POST['bpm']) : '';
    $product->update_meta_data('bpm', $bpm);
    
    $mood = isset($_POST['mood']) ? sanitize_text_field($_POST['mood']) : '';
    $product->update_meta_data('mood', $mood);
    
    $key = isset($_POST['key']) ? sanitize_text_field($_POST['key']) : '';
    $product->update_meta_data('key', $key);
    
    $audio_url = isset($_POST['audio_url']) ? esc_url($_POST['audio_url']) : '';
    $product->update_meta_data('audio_url', $audio_url);
    
    $product->save();
}
?>
```

## Option 2: Using Advanced Custom Fields (ACF) Plugin

1. Install "Advanced Custom Fields" plugin
2. Create a new Field Group
3. Set Location Rule: Post Type = Product
4. Add these fields:
   - Text field: `genre`
   - Number field: `bpm`
   - Text field: `mood`
   - Text field: `key`
   - URL field: `audio_url`

## Option 3: Using WooCommerce REST API

You can programmatically add products using Python:

```python
from woocommerce import API

wcapi = API(
    url="https://rarebeats.co.uk",
    consumer_key="ck_c4ec59638a6e0b5c0434b3260143b688f9c5ebdd",
    consumer_secret="cs_9c711e15726ef49e2eac63c06711e80a2cf00e3a",
    version="wc/v3"
)

# Create a variable product
product_data = {
    "name": "Dark Trap Beat",
    "type": "variable",
    "status": "publish",
    "description": "Hard-hitting trap beat with dark melodies",
    "short_description": "Perfect for rap and trap artists",
    "categories": [{"name": "Beats"}],
    "images": [
        {
            "src": "https://your-storage.com/images/beat-cover.jpg"
        }
    ],
    "meta_data": [
        {"key": "genre", "value": "Trap"},
        {"key": "bpm", "value": "140"},
        {"key": "mood", "value": "Dark"},
        {"key": "key", "value": "Am"},
        {"key": "audio_url", "value": "https://your-storage.com/beats/dark-trap-preview.mp3"}
    ],
    "attributes": [
        {
            "name": "License Type",
            "variation": True,
            "visible": True,
            "options": ["Basic", "Premium", "Exclusive"]
        }
    ]
}

# Create the product
response = wcapi.post("products", product_data)
product_id = response.json()['id']

# Create variations
variations = [
    {
        "regular_price": "29.99",
        "attributes": [
            {"name": "License Type", "option": "Basic"}
        ]
    },
    {
        "regular_price": "79.99",
        "attributes": [
            {"name": "License Type", "option": "Premium"}
        ]
    },
    {
        "regular_price": "299.99",
        "attributes": [
            {"name": "License Type", "option": "Exclusive"}
        ]
    }
]

for variation_data in variations:
    wcapi.post(f"products/{product_id}/variations", variation_data)

print(f"Product created with ID: {product_id}")
```

## Quick Setup Script

Save this as `add_sample_products.py` and run it to quickly add sample products:

```python
from woocommerce import API

wcapi = API(
    url="https://rarebeats.co.uk",
    consumer_key="ck_c4ec59638a6e0b5c0434b3260143b688f9c5ebdd",
    consumer_secret="cs_9c711e15726ef49e2eac63c06711e80a2cf00e3a",
    version="wc/v3"
)

sample_beats = [
    {
        "name": "Dark Trap Beat",
        "genre": "Trap",
        "bpm": "140",
        "mood": "Dark",
        "key": "Am",
        "audio_url": "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3"  # Sample audio
    },
    {
        "name": "Chill Lo-Fi Beat",
        "genre": "Lo-Fi",
        "bpm": "85",
        "mood": "Chill",
        "key": "C",
        "audio_url": "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3"
    },
    {
        "name": "Uplifting Pop Beat",
        "genre": "Pop",
        "bpm": "120",
        "mood": "Uplifting",
        "key": "G",
        "audio_url": "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3"
    }
]

for beat in sample_beats:
    product_data = {
        "name": beat["name"],
        "type": "variable",
        "status": "publish",
        "meta_data": [
            {"key": "genre", "value": beat["genre"]},
            {"key": "bpm", "value": beat["bpm"]},
            {"key": "mood", "value": beat["mood"]},
            {"key": "key", "value": beat["key"]},
            {"key": "audio_url", "value": beat["audio_url"]}
        ],
        "attributes": [
            {
                "name": "License Type",
                "variation": True,
                "visible": True,
                "options": ["Basic", "Premium", "Exclusive"]
            }
        ]
    }
    
    response = wcapi.post("products", product_data)
    product_id = response.json()['id']
    
    # Add variations
    variations = [
        {"regular_price": "29.99", "attributes": [{"name": "License Type", "option": "Basic"}]},
        {"regular_price": "79.99", "attributes": [{"name": "License Type", "option": "Premium"}]},
        {"regular_price": "299.99", "attributes": [{"name": "License Type", "option": "Exclusive"}]}
    ]
    
    for var in variations:
        wcapi.post(f"products/{product_id}/variations", var)
    
    print(f"Created: {beat['name']} (ID: {product_id})")
```

Run with:
```bash
cd /app/backend
python3 add_sample_products.py
```
