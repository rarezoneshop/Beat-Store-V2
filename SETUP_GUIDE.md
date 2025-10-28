# RareBeats - Setup Guide

## Overview
RareBeats is a music player/store that integrates with your WooCommerce website to sell beats and instrumentals with different license types.

## Features
- 🎵 Audio player with playback controls
- 🔍 Advanced filtering (Genre, BPM, Mood, Key)
- 🛒 Shopping cart with license selection
- 🎹 Waveform visualization during playback
- ✅ License selection per beat (using WooCommerce product variations)
- 💳 Seamless checkout redirect to WooCommerce

## WooCommerce Setup

### 1. Product Configuration

#### Setting Up a Beat Product

1. **Create a Variable Product** in WooCommerce:
   - Go to Products → Add New
   - Product Type: **Variable Product**
   - Add product name (e.g., "Dark Trap Beat")

2. **Add Product Meta Fields** (for filters):
   You need to add custom meta fields to your products. Use a plugin like "Advanced Custom Fields" or add them directly via code.
   
   Required custom fields:
   - `genre` - e.g., "Hip Hop", "Trap", "R&B", "Pop"
   - `bpm` - e.g., "140", "85", "120"
   - `mood` - e.g., "Dark", "Uplifting", "Chill", "Aggressive"
   - `key` - e.g., "Am", "C", "Dm", "F#"
   - `audio_url` - **IMPORTANT**: Full URL to the audio preview file

3. **Audio File Storage**:
   
   **Recommended Options:**
   
   a. **AWS S3** (Recommended for scalability):
      - Upload your beat previews to S3
      - Make them publicly accessible or use signed URLs
      - Copy the full URL (e.g., `https://your-bucket.s3.amazonaws.com/beats/beat-name.mp3`)
      - Add this URL to the `audio_url` custom field
   
   b. **WordPress Media Library**:
      - Upload audio file to Media Library
      - Copy the file URL
      - Add to `audio_url` custom field
   
   c. **External CDN** (Cloudflare R2, etc.):
      - Upload to your CDN
      - Get the public URL
      - Add to `audio_url` custom field

4. **Create License Variations**:
   - Go to Product Data → Variations
   - Create variations for different license types
   - Example variations:
     * **Basic License** - £29.99
       - Attributes: License Type: Basic
     * **Premium License** - £79.99
       - Attributes: License Type: Premium
     * **Exclusive Rights** - £299.99
       - Attributes: License Type: Exclusive

5. **Publish the Product**:
   - Click "Publish" to make it available in the store

### 2. Example Product Structure

```json
{
  "name": "Dark Trap Beat",
  "type": "variable",
  "status": "publish",
  "meta_data": [
    {"key": "genre", "value": "Trap"},
    {"key": "bpm", "value": "140"},
    {"key": "mood", "value": "Dark"},
    {"key": "key", "value": "Am"},
    {"key": "audio_url", "value": "https://your-storage.com/beats/dark-trap.mp3"}
  ],
  "variations": [
    {
      "attributes": [{"name": "License Type", "option": "Basic"}],
      "price": "29.99"
    },
    {
      "attributes": [{"name": "License Type", "option": "Premium"}],
      "price": "79.99"
    }
  ]
}
```

## API Configuration

The following WooCommerce credentials are configured in `/app/backend/.env`:

```
WOOCOMMERCE_URL="https://rarebeats.co.uk"
WOOCOMMERCE_CONSUMER_KEY="ck_c4ec59638a6e0b5c0434b3260143b688f9c5ebdd"
WOOCOMMERCE_CONSUMER_SECRET="cs_9c711e15726ef49e2eac63c06711e80a2cf00e3a"
```

## How It Works

1. **Browse Beats**: Users can browse all beats with filters for genre, BPM, mood, and key
2. **Preview Audio**: Click play button to listen to beat previews
3. **Select License**: Check the license type checkbox for each beat
4. **Add to Cart**: Click "Add" button when license is selected
5. **Checkout**: Click "Proceed to Checkout" to be redirected to WooCommerce cart

## User Flow

```
Browse Beats → Apply Filters → Play Preview → Select License → Add to Cart → Checkout on WooCommerce
```

## Technical Details

### API Endpoints

- `GET /api/products` - Fetch all products with filters
  - Query params: genre, bpm_min, bpm_max, mood, key, page, per_page
- `GET /api/products/:id` - Get single product with variations
- `GET /api/filters` - Get available filter options
- `POST /api/cart` - Add item to cart
- `GET /api/cart` - Get cart items
- `DELETE /api/cart/:id` - Remove item from cart
- `POST /api/checkout` - Generate WooCommerce checkout URL

### Frontend Features

- **Audio Player**: HTML5 audio with custom controls
- **Waveform Animation**: Visual feedback during playback
- **Skip Controls**: Next/Previous track navigation
- **Volume Control**: Adjustable volume slider
- **Progress Bar**: Seekable playback position
- **Responsive Design**: Works on all screen sizes

## Notes

- Products must be **published** in WooCommerce to appear (or set to "any" status in development)
- Audio URLs must be publicly accessible
- Cart items are stored in MongoDB temporarily
- Checkout redirects to WooCommerce for payment processing
- Supports both guest and account checkout through WooCommerce

## Next Steps

1. Add more products to WooCommerce with proper meta fields
2. Upload audio preview files to your preferred storage
3. Configure license variations for each product
4. Customize the design/colors to match your brand
5. Test the complete checkout flow

## Support

For issues or questions about:
- WooCommerce setup: Check WooCommerce documentation
- Audio storage: AWS S3, Cloudflare R2, or WordPress Media Library docs
- Custom fields: Use ACF (Advanced Custom Fields) plugin
