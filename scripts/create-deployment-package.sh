#!/bin/bash

# RareBeats Deployment Package Creator
# This script creates a complete deployment package for self-hosting

set -e

echo "======================================"
echo "RareBeats Deployment Package Creator"
echo "======================================"
echo ""

# Create deployment directory
DEPLOY_DIR="/app/deployment-package"
rm -rf $DEPLOY_DIR
mkdir -p $DEPLOY_DIR

echo "✓ Created deployment directory"

# 1. Package Backend
echo "📦 Packaging backend..."
mkdir -p $DEPLOY_DIR/backend
cp -r /app/backend/* $DEPLOY_DIR/backend/
cd $DEPLOY_DIR/backend

# Create production requirements.txt
pip freeze > requirements.txt

# Create .env.example
cat > .env.example << 'EOF'
# MongoDB Configuration
MONGO_URL="mongodb://localhost:27017"
DB_NAME="rarebeats"
CORS_ORIGINS="https://rarebeats.co.uk,https://www.rarebeats.co.uk"

# WooCommerce API Credentials
WOOCOMMERCE_URL="https://rarebeats.co.uk"
WOOCOMMERCE_CONSUMER_KEY="your_consumer_key"
WOOCOMMERCE_CONSUMER_SECRET="your_consumer_secret"
EOF

# Create Dockerfile for backend
cat > Dockerfile << 'EOF'
FROM python:3.11-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 8001

CMD ["uvicorn", "server:app", "--host", "0.0.0.0", "--port", "8001"]
EOF

# Create docker-compose.yml
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  api:
    build: .
    ports:
      - "8001:8001"
    environment:
      - MONGO_URL=mongodb://mongo:27017
      - DB_NAME=rarebeats
    depends_on:
      - mongo
    restart: unless-stopped

  mongo:
    image: mongo:7
    ports:
      - "27017:27017"
    volumes:
      - mongo_data:/data/db
    restart: unless-stopped

volumes:
  mongo_data:
EOF

echo "✓ Backend packaged"

# 2. Build and Package Frontend
echo "📦 Building and packaging frontend..."
cd /app/frontend

# Create production .env
cat > .env.production << 'EOF'
# Update this with your backend URL after deployment
REACT_APP_BACKEND_URL=https://api.rarebeats.co.uk
EOF

# Build frontend
echo "Building React app..."
yarn build

# Copy build to deployment package
mkdir -p $DEPLOY_DIR/frontend
cp -r build/* $DEPLOY_DIR/frontend/

# Create Nginx config for frontend
cat > $DEPLOY_DIR/frontend/nginx.conf << 'EOF'
server {
    listen 80;
    server_name player.rarebeats.co.uk;
    root /var/www/rarebeats-player;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

echo "✓ Frontend built and packaged"

# 3. Package WordPress Plugin
echo "📦 Packaging WordPress plugin..."
cp -r /app/wordpress-plugin $DEPLOY_DIR/
cd $DEPLOY_DIR
zip -r wordpress-plugin.zip wordpress-plugin/
echo "✓ WordPress plugin packaged"

# 4. Create deployment scripts
echo "📝 Creating deployment scripts..."

# Railway deployment script
cat > $DEPLOY_DIR/deploy-railway.sh << 'EOF'
#!/bin/bash
# Deploy to Railway

echo "Deploying RareBeats to Railway..."

# Install Railway CLI if not present
if ! command -v railway &> /dev/null; then
    echo "Installing Railway CLI..."
    npm i -g @railway/cli
fi

# Backend
echo "Deploying backend..."
cd backend
railway login
railway init
railway up

echo "Don't forget to add environment variables in Railway dashboard!"
echo "Backend URL will be shown after deployment."

cd ..
EOF

# Cloudflare Pages deployment script
cat > $DEPLOY_DIR/deploy-cloudflare.sh << 'EOF'
#!/bin/bash
# Deploy to Cloudflare Pages

echo "Deploying RareBeats frontend to Cloudflare Pages..."

# Install Wrangler if not present
if ! command -v wrangler &> /dev/null; then
    echo "Installing Wrangler..."
    npm i -g wrangler
fi

cd frontend
wrangler login
wrangler pages deploy . --project-name=rarebeats-player

echo "Frontend deployed! Add custom domain in Cloudflare dashboard."
EOF

# VPS deployment script
cat > $DEPLOY_DIR/deploy-vps.sh << 'EOF'
#!/bin/bash
# Deploy to VPS

echo "VPS Deployment Script"
echo "====================="
echo ""
echo "Prerequisites:"
echo "- Ubuntu 22.04+ server"
echo "- Root access"
echo "- Domain pointing to server IP"
echo ""

# Update system
apt update && apt upgrade -y

# Install dependencies
apt install -y python3.11 python3-pip python3-venv nginx mongodb certbot python3-certbot-nginx

# Create directories
mkdir -p /var/www/rarebeats-api
mkdir -p /var/www/rarebeats-player

# Copy files
cp -r backend/* /var/www/rarebeats-api/
cp -r frontend/* /var/www/rarebeats-player/

# Setup backend
cd /var/www/rarebeats-api
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt

# Create systemd service
cat > /etc/systemd/system/rarebeats-api.service << 'SYSTEMD'
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
SYSTEMD

systemctl daemon-reload
systemctl start rarebeats-api
systemctl enable rarebeats-api

# Configure Nginx
cat > /etc/nginx/sites-available/rarebeats-api << 'NGINX'
server {
    listen 80;
    server_name api.rarebeats.co.uk;
    
    location / {
        proxy_pass http://127.0.0.1:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
NGINX

cat > /etc/nginx/sites-available/rarebeats-player << 'NGINX'
server {
    listen 80;
    server_name player.rarebeats.co.uk;
    root /var/www/rarebeats-player;
    index index.html;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
}
NGINX

ln -s /etc/nginx/sites-available/rarebeats-api /etc/nginx/sites-enabled/
ln -s /etc/nginx/sites-available/rarebeats-player /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx

# Install SSL
certbot --nginx -d api.rarebeats.co.uk -d player.rarebeats.co.uk

echo "Deployment complete!"
echo "Update WordPress plugin URL to: https://player.rarebeats.co.uk"
EOF

chmod +x $DEPLOY_DIR/deploy-*.sh

echo "✓ Deployment scripts created"

# 5. Create README
cat > $DEPLOY_DIR/README.md << 'EOF'
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
EOF

echo "✓ README created"

# 6. Create tarball
echo "📦 Creating deployment tarball..."
cd /app
tar -czf rarebeats-deployment-$(date +%Y%m%d).tar.gz deployment-package/

echo ""
echo "======================================"
echo "✅ Deployment Package Created!"
echo "======================================"
echo ""
echo "Package location: /app/rarebeats-deployment-$(date +%Y%m%d).tar.gz"
echo "Extracted to: /app/deployment-package/"
echo ""
echo "Next steps:"
echo "1. Download the deployment package"
echo "2. Choose hosting provider (Railway, VPS, Docker)"
echo "3. Follow deployment scripts in the package"
echo "4. Update WordPress plugin URL"
echo ""
echo "See INDEPENDENCE_DEPLOYMENT.md for detailed guide"
