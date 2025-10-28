# Making RareBeats Fully Independent - Deployment Guide

## Current Dependencies on Emergent

Your app currently relies on Emergent for:

1. **Frontend Hosting**: `https://audiomarket-1.preview.emergentagent.com`
2. **Backend API**: Running on Emergent servers
3. **MongoDB Database**: Hosted on Emergent infrastructure

## What's Independent

✅ **Already Independent:**
- WooCommerce integration (uses your own store)
- All custom code
- No Emergent-specific APIs or keys
- No vendor lock-in in the codebase

## Complete Independence Migration

To make your app fully independent, follow these steps:

---

## Phase 1: Self-Host the Backend API

### Option A: VPS Hosting (Recommended)

**Providers:** DigitalOcean, Linode, AWS EC2, Hetzner

#### Step 1: Set Up VPS

```bash
# SSH into your VPS
ssh root@your-server-ip

# Update system
apt update && apt upgrade -y

# Install Python 3.11+
apt install python3.11 python3-pip python3-venv nginx -y
```

#### Step 2: Deploy Backend

```bash
# Create app directory
mkdir -p /var/www/rarebeats-api
cd /var/www/rarebeats-api

# Upload your backend files
# (Use SCP, FTP, or git clone)

# Create virtual environment
python3 -m venv venv
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Create production .env
cat > .env << 'EOF'
MONGO_URL="mongodb://localhost:27017"
DB_NAME="rarebeats"
CORS_ORIGINS="https://rarebeats.co.uk,https://www.rarebeats.co.uk"

# WooCommerce
WOOCOMMERCE_URL="https://rarebeats.co.uk"
WOOCOMMERCE_CONSUMER_KEY="ck_c4ec59638a6e0b5c0434b3260143b688f9c5ebdd"
WOOCOMMERCE_CONSUMER_SECRET="cs_9c711e15726ef49e2eac63c06711e80a2cf00e3a"
EOF

# Install MongoDB
apt install mongodb -y
systemctl start mongodb
systemctl enable mongodb
```

#### Step 3: Configure Systemd Service

```bash
cat > /etc/systemd/system/rarebeats-api.service << 'EOF'
[Unit]
Description=RareBeats API
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/rarebeats-api
Environment="PATH=/var/www/rarebeats-api/venv/bin"
ExecStart=/var/www/rarebeats-api/venv/bin/uvicorn server:app --host 0.0.0.0 --port 8001
Restart=always

[Install]
WantedBy=multi-user.target
EOF

# Start service
systemctl daemon-reload
systemctl start rarebeats-api
systemctl enable rarebeats-api
```

#### Step 4: Configure Nginx

```bash
cat > /etc/nginx/sites-available/rarebeats-api << 'EOF'
server {
    listen 80;
    server_name api.rarebeats.co.uk;

    location / {
        proxy_pass http://127.0.0.1:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF

# Enable site
ln -s /etc/nginx/sites-available/rarebeats-api /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx

# Install SSL certificate
apt install certbot python3-certbot-nginx -y
certbot --nginx -d api.rarebeats.co.uk
```

### Option B: Platform-as-a-Service (Easiest)

**Providers:** Railway, Render, Heroku, Fly.io

#### Railway Deployment

1. Create account at https://railway.app
2. Create new project
3. Add MongoDB database service
4. Deploy backend:
   ```bash
   railway login
   railway link
   railway up
   ```
5. Add environment variables in Railway dashboard
6. Get your API URL: `https://your-app.railway.app`

---

## Phase 2: Self-Host the Frontend

### Option A: Static Hosting (Fastest)

**Providers:** Cloudflare Pages, Netlify, Vercel, GitHub Pages

#### Step 1: Build Frontend

```bash
cd /app/frontend

# Update .env for production
cat > .env << 'EOF'
REACT_APP_BACKEND_URL=https://api.rarebeats.co.uk
EOF

# Build
yarn build
```

This creates a `build` folder with all static files.

#### Step 2: Deploy to Cloudflare Pages

```bash
# Install Wrangler CLI
npm install -g wrangler

# Login
wrangler login

# Deploy
cd build
wrangler pages deploy . --project-name=rarebeats-player
```

Your app will be at: `https://rarebeats-player.pages.dev`

#### Step 3: Custom Domain

1. Go to Cloudflare Pages dashboard
2. Custom domains → Add custom domain
3. Enter: `player.rarebeats.co.uk`
4. Follow DNS instructions

### Option B: WordPress Hosting

Upload the `build` folder contents to:
```
/wp-content/rarebeats-player/
```

Access at: `https://rarebeats.co.uk/wp-content/rarebeats-player/`

---

## Phase 3: Migrate MongoDB Data

### Export from Emergent

```bash
# If you have access to Emergent MongoDB
mongodump --uri="mongodb://localhost:27017" --db=test_database --out=/tmp/backup

# Download the backup
scp -r user@emergent:/tmp/backup ./mongodb-backup
```

### Import to Your Server

```bash
# On your new server
mongorestore --uri="mongodb://localhost:27017" --db=rarebeats ./mongodb-backup/test_database
```

**Note:** Cart data can be recreated fresh, so migration is optional.

---

## Phase 4: Update WordPress Plugin

After deploying frontend and backend:

1. Go to WordPress Admin → Settings → RareBeats Player
2. Update player URL to your new URL:
   - `https://player.rarebeats.co.uk`
   - Or `https://rarebeats.co.uk/wp-content/rarebeats-player/`
3. Save changes

---

## Complete Independence Checklist

- [ ] Backend deployed on your infrastructure
- [ ] MongoDB running on your server or MongoDB Atlas
- [ ] Frontend built and deployed
- [ ] Custom domain configured
- [ ] SSL certificates installed
- [ ] CORS configured for your domain
- [ ] Environment variables updated
- [ ] WordPress plugin URL updated
- [ ] Test all functionality
- [ ] Backup strategy in place

---

## Recommended Architecture for Full Independence

```
┌─────────────────────────────────────────┐
│         rarebeats.co.uk                 │
│         (WordPress/WooCommerce)         │
│  [rarebeats_player shortcode embedded]  │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│      player.rarebeats.co.uk             │
│      (React Frontend - Static Files)    │
│      Hosting: Cloudflare Pages          │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│      api.rarebeats.co.uk                │
│      (FastAPI Backend)                  │
│      Hosting: VPS or Railway            │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│         MongoDB Database                │
│      (Self-hosted or MongoDB Atlas)     │
└─────────────────────────────────────────┘
```

---

## Simplified Quick Deploy (Railway + Cloudflare)

**Total time: ~15 minutes**

### Backend (Railway)

```bash
# Install Railway CLI
npm i -g @railway/cli

# Login and deploy
railway login
cd /app/backend
railway init
railway up

# Add environment variables in Railway dashboard
# Get your backend URL
```

### Frontend (Cloudflare Pages)

```bash
cd /app/frontend

# Update .env with Railway backend URL
echo "REACT_APP_BACKEND_URL=https://your-app.railway.app" > .env

# Build
yarn build

# Deploy
npx wrangler pages deploy build --project-name=rarebeats
```

### Update Plugin

WordPress Admin → Settings → RareBeats Player
- Update URL to Cloudflare Pages URL

**Done! Fully independent.**

---

## Cost Estimates

### Free Tier (Good for starting)
- **Cloudflare Pages**: Free (unlimited bandwidth)
- **Railway**: $5/month (includes MongoDB)
- **Total**: $5/month

### Professional Tier
- **DigitalOcean VPS**: $12/month (2GB RAM)
- **MongoDB Atlas**: Free tier (512MB)
- **Cloudflare**: Free
- **Total**: $12/month

### Enterprise Tier
- **AWS/GCP**: $50-100/month (scalable)
- **MongoDB Atlas**: $57/month (dedicated)
- **CDN**: Included
- **Total**: $100-150/month

---

## No Migration Option

If you don't want to migrate now, you can keep using Emergent hosting. However, for production and long-term stability, self-hosting is recommended.

The app code is 100% portable and has no Emergent-specific dependencies.

---

## Need Help?

Files to download for deployment:
- `/app/backend/` - Backend code
- `/app/frontend/build/` - Built frontend (after running `yarn build`)
- `/app/wordpress-plugin/` - WordPress plugin

All configuration is in `.env` files with no hard-coded Emergent URLs.
