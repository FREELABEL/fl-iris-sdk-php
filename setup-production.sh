#!/bin/bash

# IRIS SDK Production Setup
# This script configures the SDK for production use

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║     IRIS SDK - Production Configuration Setup            ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Check if we're in the SDK directory
if [ ! -f "bin/iris" ]; then
    echo "❌ Error: Must run from SDK root directory"
    echo "   cd to the sdk/php directory first"
    exit 1
fi

echo "📋 Collecting production credentials..."
echo ""

# Get API key
read -p "Enter your production API key: " API_KEY
if [ -z "$API_KEY" ]; then
    echo "❌ API key is required"
    exit 1
fi

# Get user ID
read -p "Enter your user ID: " USER_ID
if [ -z "$USER_ID" ]; then
    echo "❌ User ID is required"
    exit 1
fi

# Get OAuth credentials
read -p "Enter production client ID: " CLIENT_ID
if [ -z "$CLIENT_ID" ]; then
    echo "❌ Client ID is required"
    exit 1
fi

read -p "Enter production client secret: " CLIENT_SECRET
if [ -z "$CLIENT_SECRET" ]; then
    echo "❌ Client secret is required"
    exit 1
fi

echo ""
echo "🔧 Configuring SDK for production..."

# Set production URLs
./bin/iris config set iris_url "https://fl-iris-api-v5-mnmol.ondigitalocean.app"
./bin/iris config set base_url "https://apiv2.heyiris.io"

# Set credentials
./bin/iris config set api_key "$API_KEY"
./bin/iris config set user_id "$USER_ID"
./bin/iris config set client_id "$CLIENT_ID"
./bin/iris config set client_secret "$CLIENT_SECRET"

echo ""
echo "✅ Configuration saved to ~/.iris/credentials.json"
echo ""
echo "📊 Configuration status:"
./bin/iris config

echo ""
echo "✨ Testing connection..."
echo ""

# Test the connection
./bin/iris sdk:call leads.aggregation.statistics --json > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Connection test successful!"
    echo ""
    echo "🎉 SDK is ready for production!"
    echo ""
    echo "📖 Next steps:"
    echo "   • Test chat: ./bin/iris chat 349 \"Hello!\""
    echo "   • List leads: ./bin/iris sdk:call leads.search"
    echo "   • Check stats: ./bin/iris sdk:call leads.aggregation.statistics"
else
    echo "⚠️  Connection test failed. Please verify your credentials."
    echo "   Run: ./bin/iris config"
fi

echo ""
