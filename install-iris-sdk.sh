#!/bin/bash

# ╔═══════════════════════════════════════════════════════════════════════════════╗
# ║                                                                               ║
# ║     IRIS AI SDK - One-Click Installer for GNiice                             ║
# ║     Quick setup for recruiter tool testing                                   ║
# ╚═══════════════════════════════════════════════════════════════════════════════╝

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC} ${GREEN}IRIS AI SDK - One-Click Installer for GNiice${NC}                           ${CYAN}║${NC}"
echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Download and run the full setup
echo -e "${BLUE}[INFO]${NC} Downloading setup script..."
curl -fsSL https://raw.githubusercontent.com/your-repo/iris-sdk/main/setup-iris-sdk-gnice.sh -o setup-iris-sdk-gnice.sh

echo -e "${BLUE}[INFO]${NC} Making script executable..."
chmod +x setup-iris-sdk-gnice.sh

echo -e "${BLUE}[INFO]${NC} Running setup..."
./setup-iris-sdk-gnice.sh

echo ""
echo -e "${GREEN}✅ Installation complete!${NC}"
echo -e "${YELLOW}💡 Next steps:${NC}"
echo "   1. Edit iris-sdk-gnice/.env with your API keys"
echo "   2. cd iris-sdk-gnice && ./test-recruiter-tools.sh"