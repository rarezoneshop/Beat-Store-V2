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
