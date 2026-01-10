#!/bin/bash

echo "🔗 AYALA INTEGRATIONS SETUP (Lead 110)"
echo "======================================"
echo "Setting up WordPress, Notion, and Trello integrations"
echo ""

LEAD_ID=110

# Check current integration status
echo "📊 Current Integration Status:"
./bin/iris integrations list --json | jq -r '.[] | select(.type == "wordpress" or .type == "notion" or .type == "trello") | "\(.type): \(.status)"' 2>/dev/null || echo "No existing integrations found"

echo ""
echo "🚀 Setting up integrations..."

# WordPress Integration
echo ""
echo "1️⃣ WORDPRESS INTEGRATION"
echo "-----------------------"
echo "SDK already supports WordPress integration"

# Check if WordPress integration exists
WP_EXISTS=$(./bin/iris integrations list --json 2>/dev/null | jq -r '.[] | select(.type == "wordpress") | .id' 2>/dev/null || echo "")

if [ -z "$WP_EXISTS" ]; then
    echo "WordPress integration not found. Would connect with:"
    echo "./bin/iris integrations connect wordpress"
    echo "# Then enter WordPress credentials when prompted"
else
    echo "WordPress integration exists (ID: $WP_EXISTS)"
    echo "Testing connection..."
    ./bin/iris integrations test $WP_EXISTS 2>&1 || echo "Test failed - may need reconnection"
fi

# Notion Integration
echo ""
echo "2️⃣ NOTION INTEGRATION"
echo "--------------------"
echo "SDK already supports Notion integration for knowledge base RAG"

NOTION_EXISTS=$(./bin/iris integrations list --json 2>/dev/null | jq -r '.[] | select(.type == "notion") | .id' 2>/dev/null || echo "")

if [ -z "$NOTION_EXISTS" ]; then
    echo "Notion integration not found. Would connect with:"
    echo "./bin/iris integrations connect notion"
    echo "# Then enter Notion API key when prompted"
else
    echo "Notion integration exists (ID: $NOTION_EXISTS)"
    echo "Testing connection..."
    ./bin/iris integrations test $NOTION_EXISTS 2>&1 || echo "Test failed - may need reconnection"
fi

# Trello Integration
echo ""
echo "3️⃣ TRELLO INTEGRATION"
echo "--------------------"
echo "SDK already supports Trello integration for project management"

TRELLO_EXISTS=$(./bin/iris integrations list --json 2>/dev/null | jq -r '.[] | select(.type == "trello") | .id' 2>/dev/null || echo "")

if [ -z "$TRELLO_EXISTS" ]; then
    echo "Trello integration not found. Would connect with:"
    echo "./bin/iris integrations connect trello"
    echo "# Then enter Trello API key when prompted"
else
    echo "Trello integration exists (ID: $TRELLO_EXISTS)"
    echo "Testing connection..."
    ./bin/iris integrations test $TRELLO_EXISTS 2>&1 || echo "Test failed - may need reconnection"
fi

echo ""
echo "📋 NEXT STEPS FOR AYALA:"
echo "========================"
echo ""
echo "1. Test RAG ingestion from connected sources:"
echo "   ./bin/iris rag:ingest --source notion --lead 110"
echo "   ./bin/iris rag:ingest --source trello --lead 110"
echo "   ./bin/iris rag:ingest --source wordpress --lead 110"
echo ""
echo "2. Create agent with integration access:"
echo "   ./bin/iris agents:create 'Ayala AEC Assistant' \\"
echo "     --integrations wordpress,notion,trello \\"
echo "     --bloq [ayala-knowledge-bloq] \\"
echo "     --prompt 'You are an assistant for Ayala Engineering & Consulting...'"
echo ""
echo "3. Test agent capabilities:"
echo "   ./bin/iris eval [agent_id] --update-agent"
echo ""
echo "4. Send playground access:"
echo "   ./bin/iris deliver 110 [agent_id]"
echo ""

echo "✅ INTEGRATIONS READY FOR IMPLEMENTATION"
echo "========================================="
echo "All required integrations are supported in the SDK."
echo "Just need to connect them with Ayala's credentials."
