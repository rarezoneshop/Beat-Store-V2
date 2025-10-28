# RareBeats Deployment Package

This package contains everything needed to deploy RareBeats independently.

## Contents

- `backend/` - FastAPI backend application
- `frontend/` - Built React application (production-ready)
- `wordpress-plugin/` - WordPress plugin for embedding
- `wordpress-plugin.zip` - Ready-to-install plugin
- `deploy-railway.sh` - Deploy to Railway
- `deploy-cloudflare.sh` - Deploy to Cloudflare Pages
- `deploy-vps.sh` - Deploy to your own VPS

## Quick Start Options

### Option 1: Railway + Cloudflare (Easiest - 15 min)

```bash
# Deploy backend
./deploy-railway.sh

# Deploy frontend (update REACT_APP_BACKEND_URL first)
./deploy-cloudflare.sh

# Install WordPress plugin
Upload wordpress-plugin.zip to WordPress
```

### Option 2: Docker (Fastest - 5 min)

```bash
cd backend
docker-compose up -d
```

### Option 3: VPS (Full Control - 30 min)

```bash
# Upload to your server
scp -r deployment-package/ user@server:/tmp/

# SSH into server
ssh user@server

# Run deployment
cd /tmp/deployment-package
sudo bash deploy-vps.sh
```

## Environment Variables

### Backend (.env)
```
MONGO_URL=mongodb://localhost:27017
DB_NAME=rarebeats
CORS_ORIGINS=https://rarebeats.co.uk
WOOCOMMERCE_URL=https://rarebeats.co.uk
WOOCOMMERCE_CONSUMER_KEY=your_key
WOOCOMMERCE_CONSUMER_SECRET=your_secret
```

### Frontend (.env.production)
```
REACT_APP_BACKEND_URL=https://api.rarebeats.co.uk
```

## Deployment Checklist

- [ ] Choose hosting provider
- [ ] Deploy backend
- [ ] Deploy MongoDB (or use MongoDB Atlas)
- [ ] Update backend .env
- [ ] Build frontend with correct REACT_APP_BACKEND_URL
- [ ] Deploy frontend
- [ ] Configure SSL certificates
- [ ] Update WordPress plugin URL
- [ ] Test complete flow

## Support

See INDEPENDENCE_DEPLOYMENT.md for detailed instructions.
