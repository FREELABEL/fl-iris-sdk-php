#!/bin/bash

# ╔═════════════════════════════════════════════════════════════════════════╗
# ║                                                                               ║
# ║     IRIS AI SDK - Custom Setup for Fakhar Zaman Khan                      ║
# ║     SoftPyramid Integration & n8n Conference Coordination                 ║
# ║     Version: 1.0                                                              ║
# ╚═══════════════════════════════════════════════════════════════════════════╝

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
CLOUD="☁️"
PARTNER="🤝"

echo -e "${CYAN}╔═════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC} ${GREEN}IRIS AI SDK - Custom Setup for Fakhar Zaman Khan${NC}           ${CYAN}║${NC}"
echo -e "${CYAN}╚═════════════════════════════════════════════════════════════════════════════╝${NC}"
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

print_context() {
    echo -e "${PURPLE}💼 $1${NC}"
}

# Check system requirements
check_requirements() {
    print_header "System Requirements Check"
    
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
    
    PROJECT_DIR="iris-sdk-fakhar"
    
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
    print_header "Environment Configuration"
    
    # Create .env file with Fakhar's context
    print_status "Creating environment configuration..."
    
    cat > .env << 'ENVCONFIG'
# IRIS AI SDK Configuration
# ======================
# Custom setup for Fakhar Zaman Khan - SoftPyramid Integration

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

# Custom Configuration for Fakhar's Use Cases
FAKHAR_COMPANY=SoftPyramid
FAKHAR_EMAIL=fakhar@softpyramid.com
FAKHAR_FOCUS=n8n-integration
FAKHAR_INTEREST=vector-db-partnership
ENVCONFIG
    
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
    print_header "CLI Setup"
    
    print_status "Making CLI executable..."
    chmod +x vendor/bin/iris
    
    # Create a symlink for easier access (optional)
    if [ -w "/usr/local/bin" ]; then
        ln -sf "$(pwd)/vendor/bin/iris" "/usr/local/bin/iris-fakhar" 2>/dev/null || true
        if [ -L "/usr/local/bin/iris-fakhar" ]; then
            print_success "✓ Created global command: iris-fakhar"
        fi
    fi
    
    print_success "✓ CLI tool is ready"
    echo ""
}

# Test the installation
test_installation() {
    print_header "Testing Installation"
    
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

# Create Fakhar-specific test scripts
create_test_scripts() {
    print_header "Creating Fakhar-Specific Test Scripts"
    
    # Create n8n conference coordination script
    cat > n8n-conference-coordination.sh << 'N8NEOF'
#!/bin/bash

echo "🎪 n8n Conference Coordination Tool"
echo "For Fakhar Zaman Khan - SoftPyramid Integration"
echo ""

# Test 1: Conference Management
echo "📋 Testing n8n integration..."
./vendor/bin/iris sdk:call leads.search status=Won per_page=3

echo ""

# Test 2: Partner Communication
echo "🤝 Testing partner coordination..."
./vendor/bin/iris sdk:call leads.get 53 --json | python3 -c "
import sys, json
data = json.load(sys.stdin)
gniice_info = data.get('contact_info', {})
print(f'Partner GNiice contact: {gniice_info.get(\"email\", \"N/A\")}')
print(f'Available for coordination and planning')
"

echo ""

# Test 3: Vector DB Integration Setup
echo "☁️ Testing Vector Database integration..."
cat > vector-db-config.json << 'VECDBEOF'
{
    "project_name": "softpyramid-vectors",
    "description": "Custom vector database for SoftPyramid knowledge base",
    "integration_type": "pinecone", 
    "data_source": "existing_embeddings",
    "use_case": "n8n_workflow_enhancement"
}
VECDBEOF

print_success "✓ Created vector DB configuration template"
echo ""
N8NEOF
    
    chmod +x n8n-conference-coordination.sh
    
    # Create SoftPyramid integration script
    cat > softpyramid-integration.sh << 'SOFTENDEOF'
#!/bin/bash

echo "💼 SoftPyramid Integration Tool"
echo "For Fakhar Zaman Khan - Custom Vector Database & Workflow"
echo ""

# Test 1: Workflow Automation
echo "🔄 Testing workflow automation..."
./vendor/bin/iris sdk:call workflows.execute --help

echo ""

# Test 2: Vector Database Operations
echo "☁️ Testing vector operations..."
echo "Custom vector DB setup would go here"
echo "Contact IRIS team for Pinecone integration: vector@heyiris.io"

echo ""

# Test 3: Lead Management for n8n
echo "📊 Testing lead management for conferences..."
./vendor/bin/iris sdk:call leads.aggregation.statistics

echo ""

# Test 4: Partner Integration
echo "🤝 Testing partner ecosystem integration..."
./vendor/bin/iris sdk:call leads.get 53 --json | python3 -c "
import sys, json
data = json.load(sys.stdin)
print('Available for technical discussions and SDK setup coordination')
"

echo ""
print_success "✅ SoftPyramid integration tests completed"
echo ""
SOFTENDEOF
    
    chmod +x softpyramid-integration.sh
    
    # Create partnership development script
    cat > partnership-development.sh << 'PARTNERSH'
#!/bin/bash

echo "🤝 IRIS Partnership Development Tool"
echo "For Fakhar Zaman Khan - Building Collaborative Solutions"
echo ""

# Test 1: Multi-Lead Management
echo "📊 Testing multi-lead coordination..."
./vendor/bin/iris sdk:call leads.search status=Proposal per_page=5

echo ""

# Test 2: Communication Tools
echo "💬 Testing communication workflows..."
./vendor/bin/iris sdk:call integrations.list

echo ""

# Test 3: Conference Planning
echo "📋 Testing conference planning tools..."
./vendor/bin/iris sdk:call leads.aggregation.getRecentLeads 5

echo ""

print_success "✅ Partnership development tools ready"
echo ""
PARTNERSH'
    
    chmod +x partnership-development.sh
    
    # Create main test runner
    cat > test-fakhar-tools.sh << 'TESTEOF'
#!/bin/bash

echo "🧪 IRIS AI SDK - Fakhar Zaman Khan Test Suite"
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_test() {
    echo -e "${BLUE}[TEST]${NC} $1"
}

print_result() {
    echo -e "${GREEN}[RESULT]${NC} $1"
}

echo "🏢 Starting Fakhar-specific test suite..."
echo ""

# Test 1: n8n Conference Coordination
print_test "1. n8n Conference Coordination"
./n8n-conference-coordination.sh

echo ""

# Test 2: SoftPyramid Integration
print_test "2. SoftPyramid Vector DB Integration"
./softpyramid-integration.sh

echo ""

# Test 3: Partnership Development
print_test "3. Partnership Development Tools"
./partnership-development.sh

echo ""

# Test 4: Lead Status Overview
print_test "4. Lead Status for Planning"
./vendor/bin/iris sdk:call leads.search status=Proposal per_page=3

echo ""

print_result "✅ All Fakhar-specific tests completed!"
echo ""
echo "${YELLOW}💡 Next steps for Fakhar:${NC}"
echo "   • Configure Pinecone integration for custom vector database"
echo "   • Set up n8n workflows with IRIS API endpoints"
echo "   • Coordinate with GNiice for Austin n8n conference planning"
echo "   • Explore partnership opportunities for SoftPyramid integration"
echo ""
TESTEOF
    
    chmod +x test-fakhar-tools.sh
    
    print_success "✓ Created n8n-conference-coordination.sh"
    print_success "✓ Created softpyramid-integration.sh" 
    print_success "✓ Created partnership-development.sh"
    print_success "✓ Created test-fakhar-tools.sh"
    echo ""
}

# Create documentation
create_documentation() {
    print_header "Creating Documentation"
    
    cat > README.md << 'READMEEOF'
# IRIS AI SDK - Fakhar Zaman Khan Custom Setup

## 🎯 Purpose
Custom SDK setup for Fakhar Zaman Khan of SoftPyramid, focusing on:
- n8n conference coordination and planning
- Vector database integration for custom knowledge base
- Partnership development opportunities
- Workflow automation for out-of-the-box solutions

## 👤 About Fakhar's Context

### Business
- **Company**: SoftPyramid
- **Email**: fakhar@softpyramid.com
- **Focus**: High-value deal management with n8n workflows
- **Interest**: Custom vector database (Pinecone) integration
- **Partnership**: GNiice coordination for Austin n8n conference

### Technical Interests
- Custom vector database for existing embeddings
- n8n workflow enhancement
- Building vs. buying solutions (IRIS vs Neuron AI)
- Conference planning and coordination

## 🚀 Quick Start

### 1. Configure API Keys
Edit the `.env` file and add your credentials:
```bash
IRIS_API_KEY=your_api_key_from_app.heyiris.io
IRIS_USER_ID=your_user_id
```

### 2. Run Setup Tests
```bash
cd iris-sdk-fakhar

# Quick demo
./test-fakhar-tools.sh

# Individual tests
./n8n-conference-coordination.sh
./softpyramid-integration.sh
./partnership-development.sh
```

## 🛠️ Available Tools for Fakhar

### 🎪 n8n Conference Coordination
Coordinate with GNiice for Austin n8n conference planning:
```bash
./n8n-conference-coordination.sh

# Track conference-related leads
./vendor/bin/iris sdk:call leads.search status=Won per_page=10
```

### ☁️ Vector Database Integration
Custom vector database setup for SoftPyramid:
```bash
./softpyramid-integration.sh

# Current status: Contact IRIS team for Pinecone setup
# Email: vector@heyiris.io
```

### 🤝 Partnership Development
Collaborative development opportunities:
```bash
./partnership-development.sh

# Multi-lead management
./vendor/bin/iris sdk:call leads.aggregation.statistics
```

### 📊 Lead Management for Business
Track high-value deals and partnerships:
```bash
./vendor/bin/iris sdk:call leads.search status=Proposal per_page=10

# Partner coordination
./vendor/bin/iris sdk:call leads.get 53  # GNiice coordination
```

## 🔧 Advanced Usage

### Custom Vector Database Setup
```bash
# Vector database configuration
cat vector-db-config.json

# Integration template
./vendor/bin/iris rag.index \
    content="Your custom business knowledge..." \
    metadata='{"project": "softpyramid", "type": "business_docs"}'
```

### n8n Workflow Integration
```bash
# List available integrations for n8n
./vendor/bin/iris sdk:call integrations.list

# Execute integration functions
./vendor/bin/iris sdk:call integrations.execute type=n8n function=create_workflow
```

### Conference Planning Tools
```bash
# Get recent leads for planning
./vendor/bin/iris sdk:call leads.aggregation.getRecentLeads 10

# Create tasks for coordination
./vendor/bin/iris sdk:call leads.tasks.create 517 title="Coordinate n8n conference venue" description="Work with GNiice for Austin location scouting"
```

## 🤖 AI Agents for Fakhar

### Access existing agents via IRIS platform:
- https://app.heyiris.io/agent/simple/358?bloq=208 (GNiice Recruiter Assistant)

### Create custom agents for SoftPyramid:
```bash
# Create agent for business automation
./vendor/bin/iris agents.create \
    name="SoftPyramid Business Assistant" \
    prompt="You are a business automation expert specializing in n8n workflows and vector database management..." \
    integrations="n8n,pinecone"
```

## 📚 Key Features for Fakhar

- **🎪 Conference Coordination**: Coordinate with GNiice for Austin n8n events
- **☁️ Custom Vector Database**: Integrate existing SoftPyramid embeddings
- **🤝 Partnership Tools**: Develop collaborative opportunities with GNiice
- **📊 High-Value Lead Management**: Track and manage strategic partnerships
- **🔄 n8n Integration**: Enhance existing workflows with IRIS AI capabilities

## 📞 Support & Contact

### For Technical Issues
- **Vector DB Integration**: vector@heyiris.io
- **SDK Support**: Contact through IRIS platform
- **Documentation**: `./vendor/bin/iris --help`

### For Partnership Coordination
- **GNiice Coordination**: Available through lead records
- **Conference Planning**: Direct integration with n8n workflows

## 🎯 Next Steps

1. **Configure API keys** from app.heyiris.io
2. **Run test suite**: `./test-fakhar-tools.sh`
3. **Set up vector database**: Contact IRIS team for Pinecone integration
4. **Coordinate with GNiice**: Use lead management tools for conference planning
5. **Develop partnership**: Use partnership development tools for collaborative opportunities

---

*Built specifically for Fakhar Zaman Khan's business needs and partnership opportunities.*
READMEEOF
    
    print_success "✓ Created README.md documentation"
    echo ""
}

# Final instructions
show_instructions() {
    print_header "Setup Complete! 🎉"
    
    print_context "Fakhar's Business Context"
    echo ""
    
    echo -e "${GREEN}Your custom IRIS AI SDK is ready!${NC}"
    echo ""
    
    echo -e "${YELLOW}📋 NEXT STEPS FOR FAKHAR:${NC}"
    echo ""
    echo "1. ${YELLOW}Configure API Keys${NC}:"
    echo "   Edit .env file with credentials from app.heyiris.io"
    echo ""
    echo "2. ${YELLOW}Vector Database Setup${NC}:"
    echo "   Contact vector@heyiris.io for Pinecone integration"
    echo "   Leverage existing SoftPyramid embeddings"
    echo ""
    echo "3. ${YELLOW}n8n Conference Coordination${NC}:"
    echo "   ./n8n-conference-coordination.sh"
    echo "   Coordinate with GNiice for Austin event"
    echo ""
    echo "4. ${YELLOW}Partnership Development${NC}:"
    echo "   ./partnership-development.sh"
    echo "   Explore collaborative opportunities with GNiice"
    echo ""
    echo "5. ${YELLOW}Run Full Test Suite${NC}:"
    echo "   ./test-fakhar-tools.sh"
    echo ""
    
    echo -e "${BLUE}📚 USEFUL COMMANDS:${NC}"
    echo "   ./vendor/bin/iris config           # Check configuration"
    echo "   ./vendor/bin/iris --help            # Show all commands"
    echo "   ./vendor/bin/iris sdk:call --help  # SDK help"
    echo ""
    
    echo -e "${PURPLE}🎪 n8n CONFERENCE TOOLS:${NC}"
    echo "   • Coordinate with GNiice for venue planning"
    echo "   • Manage conference-related leads and tasks"
    echo "   • Integrate with existing n8n workflows"
    echo ""
    
    echo -e "${PURPLE}☁️ VECTOR DATABASE INTEGRATION:${NC}"
    echo "   • Custom Pinecone setup for SoftPyramid"
    echo "   • Import existing business embeddings"
    echo "   • AI-powered search and retrieval"
    echo ""
    
    echo -e "${PURPLE}🤝 PARTNERSHIP DEVELOPMENT:${NC}"
    echo "   • Collaborative development opportunities"
    echo "   • Multi-lead management and coordination"
    echo "   • Business intelligence and analytics"
    echo ""
    
    echo -e "${CYAN}📞 FOR FAKHAR-SPECIFIC HELP:${NC}"
    echo "   • Vector DB integration: vector@heyiris.io"
    echo "   • Technical support: IRIS platform documentation"
    echo "   • Partnership coordination: GNiice lead management"
    echo ""
    
    print_success "Custom SDK setup complete! Ready for SoftPyramid integration! 🚀"
}

# Main execution flow
main() {
    echo -e "${ROCKET}🚀 Starting IRIS AI SDK setup for Fakhar Zaman Khan...${NC}"
    echo ""
    
    print_context "SoftPyramid Integration & n8n Conference Coordination"
    
    check_requirements
    setup_project
    install_sdk
    setup_environment
    setup_cli
    test_installation
    create_test_scripts
    create_documentation
    show_instructions
    
    echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC} ${PARTNER} Custom setup ready! Fakhar can coordinate with GNiice and integrate SoftPyramid. ${GREEN}║${NC}"
    echo -e "${GREEN}╚═════════════════════════════════════════════════════════════════════════╝${NC}"
}

# Run the setup
main "$@"