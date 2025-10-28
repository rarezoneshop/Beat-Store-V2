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
