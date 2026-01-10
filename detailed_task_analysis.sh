#!/bin/bash

echo "=== DETAILED WON LEADS TASK ANALYSIS ==="
echo ""

# Define WON leads from our earlier query
WON_LEADS=("110" "79" "67" "53" "24" "75" "412" "65")

declare -A LEAD_NAMES=(
    ["110"]="Dr. John F. Ayala, PE, PMP, LSSMBB"
    ["79"]="Richard Delgado"
    ["67"]="Lisa Martinez"  
    ["53"]="@gniice_"
    ["24"]="Rodney Mayo"
    ["75"]="Christiaan Cilliers"
    ["412"]="Tha Juan"
    ["65"]="@nsgbillz"
)

echo "DETAILED TASK BREAKDOWN:"
echo "========================"

TOTAL_TASKS=0
COMPLETED_TASKS=0
INCOMPLETE_TASKS=0

for lead_id in "${WON_LEADS[@]}"; do
    lead_name="${LEAD_NAMES[$lead_id]}"
    
    echo ""
    echo "LEAD $lead_id: $lead_name"
    echo "------------------------------"
    
    # Get all tasks for this lead
    task_data=$(./bin/iris sdk:call leads.tasks.all $lead_id --json 2>&1)
    task_count=$(echo "$task_data" | jq '.tasks | length' 2>/dev/null || echo "0")
    
    if [ "$task_count" -gt 0 ]; then
        echo "Tasks ($task_count total):"
        
        # Show all tasks with status
        echo "$task_data" | jq -r '.tasks[] | "  - [\(.status // "incomplete")] \(.title)"' 2>/dev/null
        
        # Count completed vs incomplete
        completed=$(echo "$task_data" | jq '.tasks[] | select(.status == "completed" or .status == "done") | .id' 2>/dev/null | wc -l)
        incomplete=$(echo "$task_data" | jq '.tasks[] | select(.status != "completed" and .status != "done") | .id' 2>/dev/null | wc -l)
        
        echo "  Status: $completed completed, $incomplete incomplete"
    else
        echo "No tasks found"
        completed=0
        incomplete=0
    fi
    
    TOTAL_TASKS=$((TOTAL_TASKS + task_count))
    COMPLETED_TASKS=$((COMPLETED_TASKS + completed))
    INCOMPLETE_TASKS=$((INCOMPLETE_TASKS + incomplete))
done

echo ""
echo "OVERALL SUMMARY:"
echo "================"
echo "Total Tasks: $TOTAL_TASKS"
echo "Completed: $COMPLETED_TASKS"
echo "Incomplete: $INCOMPLETE_TASKS"
if [ "$TOTAL_TASKS" -gt 0 ]; then
    completion_rate=$((COMPLETED_TASKS * 100 / TOTAL_TASKS))
    echo "Completion Rate: $completion_rate%"
else
    echo "Completion Rate: N/A"
fi

echo ""
echo "WHAT WE CAN KNOCK OUT TODAY:"
echo "============================"

echo "🔥 URGENT PAYMENT TASKS (IMMEDIATE REVENUE):"
echo "Ayala (Lead 110) - Invoice/Payment Tasks:"
echo "  - send invoice and accept payment"
echo "  - Get invoice paid before Christmas"
echo "  - URGENT: Final bump for invoice payment"
echo "  - Process outstanding invoice payment"
echo ""

echo "⚡ QUICK WINS (30-60 minutes each):"
echo "1. Payment follow-ups (calls/emails)"
echo "2. Invoice processing"
echo "3. Basic email follow-ups"
echo ""

echo "🎯 HIGH-VALUE DELIVERY TASKS:"
echo "Tha Juan (Lead 412) - 11 tasks, all incomplete:"
echo "  - Fix UI bugs in AI Receptionist agent interface"
echo "  - Train AI Receptionist on Tha Juan business specifics"
echo "  - Setup Agent Control & Voice Settings"
echo "  - Demo Call Logs & Agent Control Features"
echo ""

echo "INTEGRATION TASKS (ALREADY BUILT):"
echo "Ayala (Lead 110):"
echo "  - Implement WordPress Integration (SDK HAS THIS)"
echo "  - Update Memory UI: External Integration Visibility"
echo "  - Implement Enhanced Memory UI for External Integrations & Webhooks"
echo ""

echo "AGENT DELIVERY PRIORITIES:"
echo "1. Lisa Martinez (3 tasks) - Voice AI Receptionist"
echo "2. Rodney Mayo (4 tasks) - Newsletter/Content AI"
echo "3. Christiaan Cilliers (4 tasks) - Travel AI"
echo "4. @gniice_ (9 tasks) - Recruiter AI"
echo "5. Richard Delgado (12 tasks) - Legal AI"
echo "6. Tha Juan (11 tasks) - Salon AI"
echo "7. Ayala (15 tasks) - Website/Content AI"
echo ""

