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
