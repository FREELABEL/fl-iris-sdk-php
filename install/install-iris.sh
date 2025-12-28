#!/bin/bash

# IRIS SDK Setup Script
# This script automatically sets up the IRIS SDK with the correct PHP version

set -e

echo "🚀 Setting up IRIS SDK..."

# Check if Homebrew is installed
if ! command -v brew &> /dev/null; then
    echo "❌ Homebrew not found. Please install Homebrew first:"
    echo "   /bin/bash -c \"\$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)\""
    exit 1
fi

# Fix Homebrew permissions if needed
echo "🔧 Checking Homebrew permissions..."
if [ ! -w /opt/homebrew ]; then
    echo "🔧 Fixing Homebrew permissions..."
    sudo chown -R $(whoami) /opt/homebrew
    sudo chown -R $(whoami) /opt/homebrew/share/zsh
    sudo chown -R $(whoami) /opt/homebrew/share/zsh/site-functions
    sudo chown -R $(whoami) /opt/homebrew/var/homebrew/locks
    chmod u+w /opt/homebrew /opt/homebrew/share/zsh /opt/homebrew/share/zsh/site-functions /opt/homebrew/var/homebrew/locks
fi

# Check current PHP version
CURRENT_PHP_VERSION=$(php --version 2>/dev/null | head -n 1 | grep -oE '[0-9]+\.[0-9]+' | head -n 1 || echo "none")
echo "📋 Current PHP version: $CURRENT_PHP_VERSION"

# Install PHP 8.4 if not present
if ! brew list php@8.4 &> /dev/null; then
    echo "📦 Installing PHP 8.4..."
    brew install php@8.4
fi

# Switch to PHP 8.4
echo "🔄 Switching to PHP 8.4..."
brew unlink php@8.2 2>/dev/null || true
brew unlink php 2>/dev/null || true
brew link --force --overwrite php@8.4

# Update PATH for current session
export PATH="/opt/homebrew/opt/php@8.4/bin:$PATH"
export PATH="/opt/homebrew/opt/php@8.4/sbin:$PATH"

# Verify PHP version
NEW_PHP_VERSION=$(php --version | head -n 1 | grep -oE '[0-9]+\.[0-9]+' | head -n 1)
echo "✅ PHP version updated to: $NEW_PHP_VERSION"

if [[ "$NEW_PHP_VERSION" < "8.4" ]]; then
    echo "❌ Failed to update PHP version. Please manually run:"
    echo "   export PATH=\"/opt/homebrew/opt/php@8.4/bin:\$PATH\""
    echo "   export PATH=\"/opt/homebrew/opt/php@8.4/sbin:\$PATH\""
    exit 1
fi

# Clone the repository if it doesn't exist
if [ ! -d "fl-iris-sdk-php" ]; then
    echo "📥 Cloning IRIS SDK repository..."
    git clone https://github.com/FREELABEL/fl-iris-sdk-php.git
    cd fl-iris-sdk-php
else
    echo "📁 Using existing fl-iris-sdk-php directory..."
    cd fl-iris-sdk-php
fi

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev

# Run setup
echo "⚙️ Running IRIS setup..."
php bin/iris setup

# Install opencode if needed
if ! command -v opencode &> /dev/null; then
    echo "📦 Installing opencode..."
    brew install opencode
else
    echo "✅ opencode is already installed"
fi

echo "🎉 Setup complete!"
echo ""
echo "📝 To use PHP 8.4 in future terminal sessions, add this to your shell profile:"
echo "   export PATH=\"/opt/homebrew/opt/php@8.4/bin:\$PATH\""
echo "   export PATH=\"/opt/homebrew/opt/php@8.4/sbin:\$PATH\""
echo ""
echo "🚀 Starting opencode..."
opencode