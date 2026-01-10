#!/bin/bash

echo "🎯 TODAY'S EXECUTION COMMANDS"
echo "============================"
echo "Ready-to-run commands for Rodney + Lisa delivery"
echo ""

echo "📋 RODNEY MAYO (Lead 24) COMMANDS:"
echo "=================================="

echo "# 1. Check Rodney's existing agents"
echo "./bin/iris sdk:call agents.list --json | jq '.data[] | select(.name | contains(\"newsletter\") or contains(\"entropy\") or contains(\"content\")) | \"Agent ID: \(.id) - Name: \(.name)\"'"
echo ""

echo "# 2. Send Entropy AI deliverables package"
echo "./bin/iris deliver 24 entropy-ai-package"
echo "# This should include: newsletter API, templates, documentation, playground access"
echo ""

echo "# 3. Check CloudFiles integration status"
echo "./bin/iris integrations status cloudfiles"
echo ""

echo "# 4. Test RAG quality for Rodney"
echo "./bin/iris rag:query \"newsletter topics\" --lead 24"
echo ""

echo "# 5. Update task status after completion"
echo "# (Manual: Mark tasks as completed in CRM)"
echo ""

echo ""
echo "🎤 LISA MARTINEZ (Lead 67) COMMANDS:"
echo "==================================="

echo "# 1. Check VAPI integration status"
echo "./bin/iris integrations status vapi"
echo ""

echo "# 2. Connect VAPI if needed"
echo "./bin/iris integrations connect vapi"
echo "# Enter VAPI API key when prompted"
echo ""

echo "# 3. Create Voice AI Receptionist agent"
echo "./bin/iris agents:create \"ATX Beauty Lab Receptionist\" \\"
echo "  --prompt \"You are a professional receptionist for ATX Beauty Lab salon. Help clients book appointments, answer questions about services, and provide information about the salon. Be friendly, professional, and helpful.\" \\"
echo "  --integrations vapi \\"
echo "  --model gpt-4o-mini"
echo ""

echo "# 4. Test the agent (this will be the agent ID from above)"
echo "./bin/iris eval [AGENT_ID] --update-agent"
echo ""

echo "# 5. Get playground URL for Lisa"
echo "./bin/iris agents:get-url [AGENT_ID]"
echo ""

echo "# 6. Deliver to Lisa"
echo "./bin/iris deliver 67 [AGENT_ID]"
echo ""

echo ""
echo "🔗 AYALA INTEGRATIONS (Lead 110) COMMANDS:"
echo "=========================================="

echo "# 1. Connect WordPress integration"
echo "./bin/iris integrations connect wordpress"
echo "# Enter WordPress site URL, username, password when prompted"
echo ""

echo "# 2. Connect Notion integration"
echo "./bin/iris integrations connect notion"
echo "# Enter Notion API key when prompted"
echo ""

echo "# 3. Connect Trello integration"
echo "./bin/iris integrations connect trello"
echo "# Enter Trello API key and token when prompted"
echo ""

echo "# 4. Test all integrations"
echo "./bin/iris integrations list"
echo ""

echo "# 5. Test RAG ingestion (after integrations are connected)"
echo "./bin/iris rag:ingest --source notion --lead 110"
echo "./bin/iris rag:ingest --source trello --lead 110"
echo "./bin/iris rag:ingest --source wordpress --lead 110"
echo ""

echo ""
echo "📊 VERIFICATION COMMANDS:"
echo "========================"

echo "# Check task completion progress"
echo "./bin/iris sdk:call leads.tasks.all 24 --json | jq '.tasks | length'  # Rodney tasks"
echo "./bin/iris sdk:call leads.tasks.all 67 --json | jq '.tasks | length'  # Lisa tasks"
echo ""

echo "# Check integration status"
echo "./bin/iris integrations list"
echo ""

echo "# Check agent evaluation scores"
echo "./bin/iris sdk:call agents.list --json | jq '.data[] | select(.name | contains(\"Receptionist\") or contains(\"newsletter\")) | \"\(.name): Check settings.evaluation\"'"
echo ""

echo ""
echo "⏰ TIMELINE REMINDER:"
echo "===================="
echo "9:00 AM - 12:00 PM: Rodney deliverables"
echo "1:00 PM - 3:30 PM: Lisa Voice AI + Ayala integrations"
echo "4:00 PM: Status update and celebration!"
echo ""

echo "🎯 SUCCESS CHECKLIST:"
echo "===================="
echo "✅ Rodney: 4/4 tasks completed"
echo "✅ Lisa: 3/3 tasks completed"  
echo "✅ Ayala: 3/3 integrations connected"
echo "✅ Total: 10 tasks completed today"
echo ""

echo "🚀 READY TO EXECUTE!"
