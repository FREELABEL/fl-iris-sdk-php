#!/bin/bash

# ╔═══════════════════════════════════════════════════════════════════════════════╗
# ║                                                                               ║
# ║     IRIS AI SDK - Automated Setup Script                                     ║
# ║     For GNiice - Recruiter Tool Testing                                       ║
# ║     Version: 1.0                                                              ║
# ╚═══════════════════════════════════════════════════════════════════════════════╝

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Emoji
ROCKET="🚀"
CHECK="✅"
WARNING="⚠️"
GEAR="⚙️"
TEST="🧪"
PHONE="📱"

echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC} ${GREEN}IRIS AI SDK - Automated Setup for GNiice Recruiter Tools${NC}                     ${CYAN}║${NC}"
echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${PURPLE}═══ $1 ═══${NC}"
}

# Check system requirements
check_requirements() {
    print_header "Checking System Requirements"
    
    # Check if PHP is installed
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed. Please install PHP 8.1+ first."
        echo "Visit: https://www.php.net/downloads.php"
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;")
    print_status "Found PHP version: $PHP_VERSION"
    
    if [[ $(echo "$PHP_VERSION < 8.1" | bc -l 2>/dev/null || echo "0") == "1" ]]; then
        print_error "PHP 8.1+ is required. Found: $PHP_VERSION"
        exit 1
    fi
    
    # Check if Composer is installed
    if ! command -v composer &> /dev/null; then
        print_error "Composer is not installed. Please install Composer first."
        echo "Visit: https://getcomposer.org/download/"
        exit 1
    fi
    
    print_success "✓ PHP $PHP_VERSION"
    print_success "✓ Composer $(composer --version | cut -d' ' -f3)"
    
    # Check if curl is available (for API testing)
    if command -v curl &> /dev/null; then
        print_success "✓ cURL $(curl --version | head -n1 | cut -d' ' -f2)"
    else
        print_warning "cURL not found - some features may not work"
    fi
    
    echo ""
}

# Create project directory
setup_project() {
    print_header "Setting Up Project Directory"
    
    PROJECT_DIR="iris-sdk-gnice"
    
    if [ -d "$PROJECT_DIR" ]; then
        print_warning "Directory $PROJECT_DIR already exists"
        read -p "Do you want to remove it and start fresh? (y/N): " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            rm -rf "$PROJECT_DIR"
            print_status "Removed existing directory"
        else
            print_error "Please remove the directory manually or choose a different location"
            exit 1
        fi
    fi
    
    mkdir -p "$PROJECT_DIR"
    cd "$PROJECT_DIR"
    
    print_success "✓ Created project directory: $(pwd)"
    echo ""
}

# Install the SDK
install_sdk() {
    print_header "Installing IRIS AI SDK"
    
    print_status "Initializing Composer project..."
    composer init --no-interaction --quiet
    
    print_status "Adding IRIS AI SDK package..."
    composer require iris-ai/sdk --no-interaction
    
    print_success "✓ SDK installed successfully"
    echo ""
}

# Setup environment configuration
setup_environment() {
    print_header "Configuring Environment"
    
    # Create .env file
    print_status "Creating environment configuration..."
    
    cat > .env << EOF
# IRIS AI SDK Configuration
# ======================
# Generated for GNiice - Recruiter Tool Testing

# API Authentication (get from app.heyiris.io)
IRIS_API_KEY=your_api_key_here
IRIS_USER_ID=your_user_id_here

# Environment
IRIS_ENV=production

# API URLs (production)
IRIS_API_URL=https://iris-api.freelabel.net
FL_API_URL=https://apiv2.heyiris.io

# Local Development (if needed)
# IRIS_LOCAL_URL=https://local.iris.freelabel.net
# FL_API_LOCAL_URL=https://local.raichu.freelabel.net
EOF
    
    print_success "✓ Created .env configuration file"
    print_warning "⚠️  IMPORTANT: Update IRIS_API_KEY and IRIS_USER_ID in .env file"
    echo ""
    
    # Copy example file
    cp .env .env.example
    print_status "✓ Created .env.example backup"
    echo ""
}

# Make CLI executable
setup_cli() {
    print_header "Setting Up CLI Tool"
    
    print_status "Making CLI executable..."
    chmod +x vendor/bin/iris
    
    # Create a symlink for easier access (optional)
    if [ -w "/usr/local/bin" ]; then
        ln -sf "$(pwd)/vendor/bin/iris" "/usr/local/bin/iris-gnice" 2>/dev/null || true
        if [ -L "/usr/local/bin/iris-gnice" ]; then
            print_success "✓ Created global command: iris-gnice"
        fi
    fi
    
    print_success "✓ CLI tool is ready"
    echo ""
}

# Test the installation
test_installation() {
    print_header "Testing SDK Installation"
    
    print_status "Running basic CLI test..."
    
    # Test CLI help command
    if ./vendor/bin/iris --help > /dev/null 2>&1; then
        print_success "✓ CLI is working"
    else
        print_error "✗ CLI test failed"
        return 1
    fi
    
    # Test configuration
    print_status "Testing configuration..."
    ./vendor/bin/iris config > /dev/null 2>&1 && print_success "✓ Configuration check passed" || print_warning "⚠️  Configuration needs API keys"
    
    echo ""
}

# Create test scripts for recruiter tools
create_test_scripts() {
    print_header "Creating Recruiter Test Scripts"
    
    # Create main test script
    cat > test-recruiter-tools.sh << 'TESTEOF'
#!/bin/bash

# ╔═══════════════════════════════════════════════════════════════════════════════╗
# ║                                                                               ║
# ║     IRIS Recruiter Tools - Test Script for GNiice                             ║
# ╚═══════════════════════════════════════════════════════════════════════════════╝

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_test() {
    echo -e "${BLUE}[TEST]${NC} $1"
}

print_result() {
    echo -e "${GREEN}[RESULT]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

echo -e "${GREEN}🧪 IRIS Recruiter Tools - Test Suite for GNiice${NC}"
echo ""

# Test 1: Recruitment Query Generation
print_test "1. Testing Recruitment Query Generation..."
./vendor/bin/iris tools recruitment \
    --job-description="Senior Solutions Engineer with 5+ years SaaS implementation experience, client-facing, deployment ownership" \
    --platform=linkedin \
    --location="Austin, TX" \
    --experience=senior

echo ""

# Test 2: Candidate Scoring
print_test "2. Testing Candidate Scoring..."
CANDIDATE_DATA='[
    {
        "name": "John Smith",
        "title": "Solutions Engineer",
        "company": "TechCorp",
        "experience": "6 years",
        "skills": ["SaaS", "API", "Client Management"],
        "location": "Austin, TX",
        "linkedin": "linkedin.com/in/johnsmith"
    },
    {
        "name": "Jane Doe", 
        "title": "Implementation Specialist",
        "company": "SaaSInc",
        "experience": "3 years",
        "skills": ["SaaS", "Support", "Training"],
        "location": "Remote",
        "linkedin": "linkedin.com/in/janedoe"
    }
]'

REQUIREMENTS='{
    "must_have_skills": ["SaaS implementation", "Client-facing", "4+ years"],
    "nice_to_have_skills": ["API integration", "Training"],
    "experience_level": "senior",
    "location_preference": "Austin, TX"
}'

./vendor/bin/iris tools candidate-score \
    --data="$CANDIDATE_DATA" \
    --requirements="$REQUIREMENTS"

echo ""

# Test 3: Lead Search (for testing his own pipeline)
print_test "3. Testing Lead Search..."
./vendor/bin/iris sdk:call leads.search per_page=5 sort=updated_at order=desc

echo ""

# Test 4: Agent Chat (with Recruiter Assistant)
print_test "4. Testing Recruiter Agent Chat..."
./vendor/bin/iris chat 358 "Help me create a Boolean search query for a senior React developer with 5+ years experience in Austin"

echo ""

print_result "✅ All tests completed! Check the results above."
echo ""
print_warning "💡 If any tests failed, make sure your API keys are correctly configured in .env"
TESTEOF
    
    chmod +x test-recruiter-tools.sh
    
    # Create quick demo script
    cat > quick-demo.sh << 'DEMOEOF'
#!/bin/bash

echo "🚀 IRIS SDK Quick Demo for GNiice"
echo ""

# Test basic SDK functionality
echo "📊 Testing lead aggregation..."
./vendor/bin/iris sdk:call leads.aggregation.statistics

echo ""
echo "🤖 Testing agent chat..."
./vendor/bin/iris chat 358 "Hello! I'm testing the recruiter tools."

echo ""
echo "✅ Demo completed!"
DEMOEOF
    
    chmod +x quick-demo.sh
    
    print_success "✓ Created test-recruiter-tools.sh"
    print_success "✓ Created quick-demo.sh"
    echo ""
}

# Create documentation
create_documentation() {
    print_header "Creating Documentation"
    
    cat > README.md << 'DOCEOF'
# IRIS AI SDK - GNiice Recruiter Tools Setup

## 🎯 Purpose
This setup provides GNiice with the IRIS AI SDK for testing recruiter automation tools.

## 🚀 Quick Start

### 1. Configure API Keys
Edit the `.env` file and add your credentials:
```bash
IRIS_API_KEY=your_api_key_from_app.heyiris.io
IRIS_USER_ID=your_user_id
```

### 2. Run Tests
```bash
# Quick demo
./quick-demo.sh

# Full recruiter tools test suite
./test-recruiter-tools.sh
```

## 🛠️ Available Tools

### Recruitment Query Generation
Generate optimized search queries for LinkedIn, GitHub, and Twitter:
```bash
./vendor/bin/iris tools recruitment \
    --job-description="Senior React Developer" \
    --platform=linkedin \
    --location="Austin, TX"
```

### Candidate Scoring
Automatically score and rank candidates:
```bash
./vendor/bin/iris tools candidate-score \
    --data='[{"name":"John","title":"Developer"}]' \
    --requirements='{"must_have_skills":["React"]}'
```

### Lead Management
Search and manage your recruiting pipeline:
```bash
./vendor/bin/iris sdk:call leads.search status=Won per_page=10
```

### Agent Chat
Chat with the AI Recruiter Assistant (Agent #358):
```bash
./vendor/bin/iris chat 358 "Help me write interview questions for a senior developer"
```

## 📚 Key Features

- **Automated Sourcing**: Generate 50-100+ candidate leads quickly
- **Intelligent Scoring**: Rank candidates by skills, experience, and fit
- **Pipeline Management**: Track candidates through your recruitment process
- **AI Assistant**: Get help with job descriptions, interview questions, and more

## 🤖 Recruiter AI Agent

Access your custom AI Recruiter Assistant at:
https://app.heyiris.io/agent/simple/358?bloq=208

## 📞 Support

- **Documentation**: `vendor/bin/iris --help`
- **Configuration**: `./vendor/bin/iris config`
- **Issues**: Contact the IRIS team

## 🔧 Advanced Usage

### Custom Search Queries
```bash
# Generate Boolean queries
./vendor/bin/iris tools recruitment --job-description="..." --format=boolean

# Browser extraction script
./vendor/bin/iris tools recruitment --job-description="..." --format=script
```

### Bulk Candidate Processing
```bash
# Score multiple candidates at once
./vendor/bin/iris sdk:call tools.scoreCandidates \
    candidate_data='[...]' \
    requirements='...'
```

### Pipeline Analytics
```bash
# Get recruitment statistics
./vendor/bin/iris sdk:call leads.aggregation.statistics

# High-priority leads
./vendor/bin/iris sdk:call leads.aggregation.list has_incomplete_tasks=1
```
DOCEOF
    
    print_success "✓ Created README.md documentation"
    echo ""
}

# Final instructions
show_instructions() {
    print_header "Setup Complete! 🎉"
    
    echo -e "${GREEN}Your IRIS AI SDK is ready for recruiter tool testing!${NC}"
    echo ""
    
    echo -e "${YELLOW}📋 NEXT STEPS:${NC}"
    echo ""
    echo "1. ${YELLOW}Configure API Keys${NC}:"
    echo "   Edit .env file with your credentials from app.heyiris.io"
    echo ""
    echo "2. ${YELLOW}Test the Setup${NC}:"
    echo "   ./quick-demo.sh                    # Quick functionality test"
    echo "   ./test-recruiter-tools.sh          # Full recruiter tools test"
    echo ""
    echo "3. ${YELLOW}Start Using the Tools${NC}:"
    echo "   ./vendor/bin/iris tools recruitment --help"
    echo "   ./vendor/bin/iris chat 358 \"Help me source candidates\""
    echo ""
    echo "4. ${YELLOW}Access Your Recruiter Agent${NC}:"
    echo "   https://app.heyiris.io/agent/simple/358?bloq=208"
    echo ""
    
    echo -e "${BLUE}📚 USEFUL COMMANDS:${NC}"
    echo "   ./vendor/bin/iris config           # Check configuration"
    echo "   ./vendor/bin/iris --help            # Show all commands"
    echo "   ./vendor/bin/iris sdk:call --help  # SDK help"
    echo ""
    
    echo -e "${PURPLE}🤖 RECRUITER-SPECIFIC TOOLS:${NC}"
    echo "   • Generate LinkedIn search queries"
    echo "   • Score and rank candidates automatically" 
    echo "   • Create Boolean search strings"
    echo "   • Extract candidate data with browser scripts"
    echo ""
    
    echo -e "${GREEN}🎯 FOR GNiice'S KAIZEN RECRUITMENT:${NC}"
    echo "   • Automate high-volume candidate sourcing"
    echo "   • Improve placement rates with AI scoring"
    echo "   • Reduce time-to-hire with intelligent matching"
    echo "   • Track performance metrics and ROI"
    echo ""
    
    echo -e "${CYAN}📞 Need Help?${NC}"
    echo "   • Check the README.md file in this directory"
    echo "   • Use ./vendor/bin/iris --help for command reference"
    echo "   • Contact IRIS support for technical issues"
    echo ""
    
    print_success "Happy recruiting! 🚀"
}

# Main execution flow
main() {
    echo -e "${ROCKET} Starting IRIS AI SDK setup for GNiice...${NC}"
    echo ""
    
    check_requirements
    setup_project
    install_sdk
    setup_environment
    setup_cli
    test_installation
    create_test_scripts
    create_documentation
    show_instructions
    
    echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC} ${CHECK} Setup completed successfully! GNiice can now test recruiter tools. ${NC}        ${GREEN}║${NC}"
    echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════════════════════╝${NC}"
}

# Run the setup
main "$@"
DOCEOF

chmod +x setup-iris-sdk-gnice.sh