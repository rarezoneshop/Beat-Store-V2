# RareBeats - Music Player/Store

A professional beat store integrated with WooCommerce, featuring an audio player, advanced filtering, and seamless checkout.

## 🎵 Live Demo

Your beat store is now live at: https://audiomarket-1.preview.emergentagent.com

## ✨ Features

### Core Features
- 🎧 **Audio Player** - Full-featured music player with play/pause, skip, volume control
- 🎨 **Waveform Visualization** - Animated waveform when beats are playing
- 🔍 **Advanced Filters** - Filter by Genre, BPM, Mood, Musical Key
- 📦 **License Selection** - Choose from Basic, Premium, or Exclusive licenses
- 🛒 **Shopping Cart** - Add multiple beats with different licenses
- 💳 **WooCommerce Checkout** - Seamless redirect to your WooCommerce store

### User Experience
- Modern, professional dark theme with blue accents
- Responsive design (works on all devices)
- Real-time search functionality
- Progress bar with seek functionality
- Volume control with slider
- Skip to next/previous track
- Persistent cart with MongoDB storage

## 📋 Setup Completed

### Backend (FastAPI)
- ✅ WooCommerce REST API integration
- ✅ MongoDB for cart management
- ✅ Product filtering endpoints
- ✅ Cart management (add/remove/clear)
- ✅ Checkout URL generation

### Frontend (React)
- ✅ Beautiful UI with Space Grotesk and Inter fonts
- ✅ Shadcn UI components
- ✅ Audio player with HTML5 Audio API
- ✅ Filter system with real-time updates
- ✅ Shopping cart sidebar
- ✅ License selection interface

### Sample Data
- ✅ 5 sample products added to WooCommerce
- ✅ Each with 3 license variations (Basic £29.99, Premium £79.99, Exclusive £299.99)
- ✅ Complete with genre, BPM, mood, and key metadata

## 🚀 How to Use

### For Your Customers

1. **Browse Beats**
   - View all available beats on the homepage
   - See genre, BPM, mood, and key badges for each beat

2. **Filter & Search**
   - Use the search bar to find specific beats
   - Filter by Genre, Mood, Key
   - Set min/max BPM range

3. **Preview Beats**
   - Click the play button on any beat to preview
   - Use the player controls at the bottom (play/pause, skip, volume)
   - Watch the waveform animation

4. **Select License**
   - Check the box next to desired license (Basic/Premium/Exclusive)
   - Click "Add" button when license is selected
   - Toast notification confirms addition to cart

5. **Checkout**
   - Click cart icon (top-right) to view items
   - Review selected beats and licenses
   - Click "Proceed to Checkout"
   - Redirected to WooCommerce for payment

### For You (Store Owner)

#### Adding New Products

**Option 1: Use the Sample Script**
```bash
cd /app
python3 scripts/add_sample_products.py
```

**Option 2: Manual WooCommerce Setup**
See `WOOCOMMERCE_SETUP.md` for detailed instructions on:
- Creating variable products
- Adding custom meta fields (genre, bpm, mood, key, audio_url)
- Setting up license variations
- Uploading audio files

**Option 3: WordPress Functions**
See `WOOCOMMERCE_SETUP.md` for PHP code to add custom fields to product edit page

#### Audio File Storage

You need to store your audio preview files somewhere accessible. Options:

1. **AWS S3** (Recommended)
   - Upload beat previews to S3 bucket
   - Make files publicly accessible
   - Copy URL to product's `audio_url` meta field

2. **WordPress Media Library**
   - Upload to Media → Add New
   - Copy file URL
   - Add to product meta field

3. **External CDN**
   - Cloudflare R2, DigitalOcean Spaces, etc.
   - Upload files
   - Use public URL in product

#### Required Product Meta Fields

For each product in WooCommerce, add these custom fields:

| Field | Example | Description |
|-------|---------|-------------|
| `genre` | "Trap" | Music genre |
| `bpm` | "140" | Beats per minute |
| `mood` | "Dark" | Mood/vibe |
| `key` | "Am" | Musical key |
| `audio_url` | "https://..." | Full URL to audio preview |

## 🔧 Configuration

### WooCommerce API Credentials

Already configured in `/app/backend/.env`:
```
WOOCOMMERCE_URL="https://rarebeats.co.uk"
WOOCOMMERCE_CONSUMER_KEY="ck_c4ec59638a6e0b5c0434b3260143b688f9c5ebdd"
WOOCOMMERCE_CONSUMER_SECRET="cs_9c711e15726ef49e2eac63c06711e80a2cf00e3a"
```

### API Endpoints

- `GET /api/products` - Fetch products with filters
- `GET /api/products/:id` - Get single product with variations
- `GET /api/filters` - Get available filter options
- `POST /api/cart` - Add item to cart
- `GET /api/cart` - Get cart items
- `DELETE /api/cart/:id` - Remove item
- `POST /api/checkout` - Generate checkout URL

## 📖 Documentation

- `SETUP_GUIDE.md` - Complete setup instructions
- `WOOCOMMERCE_SETUP.md` - WooCommerce product configuration
- `scripts/add_sample_products.py` - Script to add sample products

## 🎨 Design

- **Color Scheme**: Dark theme with blue accents (#3b82f6)
- **Fonts**: 
  - Headings: Space Grotesk
  - Body: Inter
- **Effects**: Glassmorphism, backdrop blur, smooth animations
- **Layout**: Grid-based with responsive breakpoints

## ✅ Tested Features

- ✅ WooCommerce API connection
- ✅ Product fetching with filters
- ✅ Audio playback
- ✅ License selection
- ✅ Add to cart
- ✅ Cart management
- ✅ Checkout URL generation
- ✅ Filter by genre, BPM, mood, key
- ✅ Search functionality
- ✅ Responsive design

## 🔜 Next Steps

1. **Add Your Real Products**
   - Replace sample products with your actual beats
   - Upload your audio previews to AWS S3 or CDN
   - Update product meta fields with correct information

2. **Customize Branding**
   - Update colors in `/app/frontend/src/App.css`
   - Change logo/brand name in header
   - Customize license names/prices

3. **Configure WooCommerce**
   - Set up payment gateways (Stripe, PayPal, etc.)
   - Configure shipping settings (digital products)
   - Set up email notifications
   - Test checkout flow

4. **Add More Features** (Optional)
   - Favorites/Wishlist
   - User accounts
   - Beat packs/bundles
   - Download delivery after purchase
   - Beat tagging system

## 🎯 Sample Products Added

1. **Dark Trap Beat** (ID: 22)
   - Trap | 140 BPM | Am | Dark
   
2. **Chill Lo-Fi Beat** (ID: 26)
   - Lo-Fi | 85 BPM | C | Chill
   
3. **Uplifting Pop Beat** (ID: 30)
   - Pop | 120 BPM | G | Uplifting
   
4. **Aggressive Drill Beat** (ID: 34)
   - Drill | 145 BPM | F#m | Aggressive
   
5. **Smooth R&B Beat** (ID: 38)
   - R&B | 90 BPM | Dm | Smooth

Each with 3 license variations:
- Basic: £29.99
- Premium: £79.99
- Exclusive: £299.99

## 🛠️ Technical Stack

- **Frontend**: React, TailwindCSS, Shadcn UI, Lucide Icons
- **Backend**: FastAPI, Python
- **Database**: MongoDB
- **Integration**: WooCommerce REST API
- **Audio**: HTML5 Audio API

## 📞 Support

For questions about:
- WooCommerce setup → WooCommerce documentation
- AWS S3 → AWS S3 documentation
- Custom development → Review the code in `/app/backend/server.py` and `/app/frontend/src/pages/BeatStore.jsx`

---

**Your beat store is ready! 🎉**

Start by adding your real products and audio files, then customize the design to match your brand
