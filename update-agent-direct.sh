#!/bin/bash
# Update Agent via Direct API Call
# Use this when SDK authentication is not available
#
# Usage:
#   ./update-agent-direct.sh 358 "Agent Name" "System prompt..."

set -e

AGENT_ID=${1:-358}
AGENT_NAME=${2:-"Talent Recruiter Agent"}
AGENT_PROMPT=${3:-"You are an AI recruitment assistant"}

# Your API token (from browser or env)
API_TOKEN="${IRIS_API_TOKEN:-your_token_here}"
USER_ID="${IRIS_USER_ID:-193}"
API_BASE="https://apiv2.heyiris.io"

echo "🤖 Updating Agent #$AGENT_ID"
echo "=============================="
echo "Name: $AGENT_NAME"
echo ""

# Full agent configuration
PAYLOAD=$(cat <<EOF
{
  "name": "$AGENT_NAME",
  "type": "content",
  "icon": "fas fa-user-tie",
  "isHuman": false,
  "description": "AI agent for analyzing resumes, scoring candidates, and creating LinkedIn search queries",
  "initial_prompt": "$AGENT_PROMPT",
  "config": {
    "model": "gpt-4o-mini-2024-07-18",
    "temperature": 0.7,
    "maxTokens": 2048,
    "modelName": "gpt-4o-mini-2024-07-18",
    "provider": "openai"
  },
  "settings": {
    "communicationStyle": "professional",
    "responseMode": "balanced",
    "responseLength": "balanced",
    "functionCalling": false,
    "webAccess": false,
    "memoryPersistence": true,
    "useKnowledgeBase": true
  }
}
EOF
)

# Make the API request
RESPONSE=$(curl -s -X PUT \
  "$API_BASE/api/v1/users/$USER_ID/bloqs/agents/$AGENT_ID" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD")

# Check for errors
if echo "$RESPONSE" | grep -q "error\|message"; then
    echo "❌ Error updating agent:"
    echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
    exit 1
fi

# Success
echo "✅ Agent updated successfully!"
echo ""
echo "$RESPONSE" | jq '{id, name, type, model: .config.model}' 2>/dev/null || echo "$RESPONSE"
echo ""
echo "🔗 View agent: https://app.heyiris.io/agent/simple/$AGENT_ID?bloq=208"
