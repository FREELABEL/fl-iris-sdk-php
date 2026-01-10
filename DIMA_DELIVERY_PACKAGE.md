# 🚀 IRIS Platform - Dima POC Delivery Package
**Date:** January 9, 2026  
**Prepared For:** Dima Semyansky  
**Status:** ✅ Ready for Testing This Weekend

---

## 📦 What's Included in This Package

This delivery package contains everything you need to start building intelligent automation agents with the IRIS Platform:

1. **Test Agent with Custom Functions** - Live demo (Agent ID: 441)
2. **Complete Documentation** - Quick start guide, React integration examples
3. **Working Code Examples** - PHP and JavaScript SDKs
4. **API Credentials** - Your production API keys (see below)
5. **Timeline & Next Steps** - Clear path to BA Conference success

---

## 🎯 Your Live Test Agent

We've created a working Phone Number Optimizer agent to demonstrate custom function capabilities:

### Agent Details
- **Agent ID:** 441
- **Name:** Phone Number Optimizer (Dima POC)
- **Model:** gpt-4o-mini (cost-effective)
- **Live URL:** https://app.heyiris.io/agent/simple/441

### Custom Functions Registered (5 Total)
1. **fetchPhoneNumbers** - Get all numbers for a client with usage stats
2. **releasePhoneNumber** - Auto-release unused numbers to save costs
3. **getCallHistory** - Detailed call history analysis
4. **tagPhoneNumber** - Categorize numbers (low-usage, auto-release, keep)
5. **sendAlert** - Send notifications about actions taken

### What This Agent Does
This agent demonstrates your **Phone Number Management** use case:
- Fetches all phone numbers for a client (multi-tenant)
- Checks call history for each number
- Auto-releases numbers with 0 calls in 72+ hours
- Tags low-usage numbers for review
- Sends summary alerts with cost savings

---

## 🔑 API Credentials

**IMPORTANT:** You're currently using our development API keys. For production access with your own isolated environment:

### Current Setup (Development)
- **API Endpoint:** https://heyiris.io/api
- **User Dashboard:** https://app.heyiris.io
- **API Documentation:** https://heyiris.io/api/docs

### To Get Your Production Keys
1. **Email us:** info@heyiris.io
2. **Subject:** "Production API Keys - Dima POC"
3. **Include:**
   - Your email address for the account
   - Company name (if applicable)
   - Expected usage volume

**OR** we can generate them for you - just let us know!

---

## 📚 Documentation Files

All documentation is located in this directory:

### 1. Quick Start Guide
**File:** `/Users/AlexMayo/Sites/freelabel/DIMA_QUICK_START_GUIDE.md`

**What's Inside:**
- API authentication setup
- Creating agents via API
- Custom functions configuration
- Phone number management example
- Fire department scheduling templates
- Complete curl command examples
- Multi-tenant context patterns

**Perfect for:** Getting started quickly, understanding the API

---

### 2. React Integration Guide
**File:** `/Users/AlexMayo/Sites/freelabel/DIMA_REACT_INTEGRATION_GUIDE.md`

**What's Inside:**
- **Phone Number Optimizer Component** (complete React code)
- **Fire Department Shift Replacement Component** (complete React code)
- **Multi-Agent Orchestration Dashboard** (complete React code)
- **Custom React Hook:** `useIRISAgent` (for agent execution)
- Scheduled execution with cron patterns
- Production deployment checklist
- TypeScript service wrapper
- Error handling patterns

**Perfect for:** Building production-ready React applications

---

### 3. Implementation Status Document
**File:** `/Users/AlexMayo/Sites/freelabel/DIMA_POC_IMPLEMENTATION_STATUS.md`

**What's Inside:**
- Current implementation status
- What's complete vs. pending
- Timeline through BA Conference
- Success metrics and KPIs
- Email templates for communication

**Perfect for:** Understanding project status and planning

---

### 4. Lead Management Workflow Guide
**File:** `fl-docker-dev/sdk/php/LEAD_MANAGEMENT_WORKFLOW.md`

**What's Inside:**
- Complete lead management workflows
- Managing notes, tasks, and deliverables
- Agent workflow management (attach/detach workflows)
- Real-world examples and best practices
- CLI commands for all lead operations
- Payment verification via Stripe integration

**Perfect for:** Understanding how to track POC progress, manage client relationships, and configure agent workflows

---

### 5. Technical Documentation (Complete SDK Reference)
**File:** `fl-docker-dev/sdk/php/TECHNICAL.md` (128,993 bytes!)

**What's Inside:**
- **Complete SDK API reference** - Every endpoint documented
- **AI Agents** - Create, configure, chat, manage
- **Knowledge Base (Bloqs)** - RAG implementation, file uploads
- **Workflows** - Multi-step automation, HITL patterns
- **Chat System** - Real-time execution, progress tracking
- **Lead Management** - CRM integration, deliverables
- **Tools** - Recruitment, newsletter generation, legal demand packages
- **PHP & JavaScript SDK examples** - Working code for every feature

**Perfect for:** Deep technical implementation, API reference, advanced features

---

### 6. Original POC Documents (Background)
**Files:**
- `DIMA_POC_SUMMARY.md` - Original POC overview
- `DIMA_POC_GUIDE.md` - Technical implementation details
- `DIMA_MEETING_NOTES_2026-01-09.md` - Latest meeting notes
- `DIMA_ANALYSIS_RECOMMENDATIONS_2026-01-09.md` - Strategic analysis

**Perfect for:** Understanding the full context and business case

---

## 🧪 Test Your Agent Right Now

### Option 1: Test via Web Interface
1. Go to: https://app.heyiris.io/agent/simple/441
2. Click "Start Chat"
3. Enter: "Check for unused phone numbers and auto-release them"
4. Watch the agent work!

### Option 2: Test via API (curl)
```bash
curl -X POST "https://heyiris.io/api/chat/execute" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "X-User-ID: $IRIS_USER_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "agentId": 441,
    "query": "Check for unused phone numbers and auto-release them",
    "context": {
      "client_id": "client_123",
      "current_date": "2026-01-09T12:00:00Z"
    }
  }'
```

### Option 3: Test via PHP SDK
```php
<?php
require 'vendor/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([
    'api_key' => 'your_api_key',
    'user_id' => 123,
]);

// Execute agent
$result = $iris->chat->executeAgent(441, [
    'query' => 'Check for unused phone numbers and auto-release them',
    'context' => [
        'client_id' => 'client_123',
        'current_date' => date('c'),
    ],
]);

echo "Agent Response:\n";
echo $result['message'] ?? 'No response';
```

### Option 4: Test via JavaScript SDK
```javascript
import IRIS from './iris-sdk.js'

const iris = new IRIS({
  apiKey: 'your_api_key',
  userId: 123,
})

// Execute agent
const result = await iris.chat.executeAgent(441, {
  query: 'Check for unused phone numbers and auto-release them',
  context: {
    client_id: 'client_123',
    current_date: new Date().toISOString(),
  },
})

console.log('Agent Response:', result.message)
```

---

## 🛠️ SDK Files

### PHP SDK
**Location:** `/Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/php/`

**Key Files:**
- `src/IRIS.php` - Main SDK class
- `src/Resources/Agents/AgentsResource.php` - Agent management
- `src/Resources/Chat/ChatResource.php` - Chat execution
- `composer.json` - Dependencies

**Install:**
```bash
cd fl-docker-dev/sdk/php
composer install
```

### JavaScript SDK
**Location:** `/Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/php/javascript/iris-sdk.js`

**Features:**
- Node.js and browser support
- Promise-based API
- Complete agent management
- Chat execution with streaming
- Error handling

**Install:**
```bash
npm install axios
# Then import the SDK file directly
```

---

## 📅 Timeline to BA Conference

### This Weekend (Jan 11-12, 2026)
- [x] ✅ API keys delivered
- [x] ✅ Test agent created
- [x] ✅ Documentation delivered
- [ ] ⏳ Test API connection
- [ ] ⏳ Create first custom agent
- [ ] ⏳ Test with sample data

### Next Week (Jan 13-17, 2026)
- [ ] Monday/Tuesday: Technical review call
- [ ] Fire department requirements gathering
- [ ] Build fire department POC agent
- [ ] Test with real scheduling data
- [ ] Refine based on feedback

### Week of Jan 20-24, 2026
- [ ] Polish fire department demo
- [ ] Create presentation materials
- [ ] Practice demo flow
- [ ] Prepare ROI calculator
- [ ] Test on multiple devices

### Week of Jan 27-31, 2026
- [ ] Final testing and refinements
- [ ] Backup demo environment
- [ ] Print marketing materials
- [ ] Prepare follow-up process

### Week of Feb 3-7, 2026
- [ ] Travel to conference
- [ ] Setup booth/demo station
- [ ] Run live demos
- [ ] Collect qualified leads

### BA Conference (Mid-February 2026)
- **Goal:** 20+ qualified leads
- **Target Pipeline:** $500k+
- **Demo Ready:** Fire department + Phone number management

---

## 🎯 Success Metrics

### This Weekend
- ✅ Successful API connection test
- ✅ Create at least 1 custom agent
- ✅ Execute agent with custom functions
- ✅ Understand multi-tenant context

### Before BA Conference
- ✅ Fire department POC fully functional
- ✅ Demo runs smoothly (< 30 seconds)
- ✅ ROI calculator shows clear savings
- ✅ Presentation materials prepared
- ✅ Follow-up process defined

### At BA Conference
- 🎯 20+ qualified leads collected
- 🎯 $500k+ pipeline created
- 🎯 3+ demo bookings scheduled
- 🎯 At least 1 LOI (Letter of Intent)

---

## 🚀 Next Steps

### Immediate Actions (This Weekend)
1. **Review Documentation**
   - Read `DIMA_QUICK_START_GUIDE.md` (15 minutes)
   - Skim `DIMA_REACT_INTEGRATION_GUIDE.md` (10 minutes)

2. **Test Agent Execution**
   - Visit https://app.heyiris.io/agent/simple/441
   - Run test query: "Check for unused phone numbers"
   - Verify custom functions work

3. **Test API Connection**
   - Use curl command above
   - Or run PHP test script
   - Verify authentication works

4. **Schedule Review Call**
   - Email us to schedule Monday/Tuesday call
   - Bring questions and feedback
   - Discuss fire department requirements

### Monday/Tuesday Call Agenda
1. Review POC test results
2. Fire department requirements deep-dive
3. Technical architecture discussion
4. Timeline confirmation
5. Next steps and deliverables

---

## 📞 Support & Contact

### Questions During Testing?
- **Email:** info@heyiris.io
- **Subject:** "Dima POC - [Your Question]"
- **Response Time:** Within 4 hours (weekdays), 8 hours (weekends)

### Urgent Issues?
- **Emergency Contact:** [To be provided]
- **Slack Channel:** [To be created if needed]

### Documentation Updates?
All docs are in this repository and will be updated in real-time as we progress.

---

## 🎁 Bonus: What Makes IRIS Different

### 1. Custom Functions (Your Secret Weapon)
- ✅ Connect to ANY API (your existing systems)
- ✅ No code changes to your backend
- ✅ Full authentication support (Bearer, API Key, OAuth)
- ✅ Template variables for multi-tenant scenarios

### 2. Multi-Tenant Ready
- ✅ Pass `client_id` in context
- ✅ Agents automatically use it in API calls
- ✅ Secure credential management
- ✅ Perfect for BA Network (many small businesses)

### 3. Cost-Effective Models
- ✅ GPT-4o-mini: Fast and cheap ($0.15 per 1M input tokens)
- ✅ GPT-4o: Premium quality when needed
- ✅ Automatic model selection based on task
- ✅ Usage tracking and analytics

### 4. Production-Ready
- ✅ RESTful API with comprehensive docs
- ✅ PHP and JavaScript SDKs
- ✅ React components ready to use
- ✅ Scheduled execution (cron patterns)
- ✅ Webhooks for async workflows
- ✅ Error handling and retries

---

## 💡 Three Enterprise Use Cases

### 1. Fire Department Scheduling (Highest Priority)
**Market:** Tens of thousands of departments nationwide  
**Problem:** Manual shift replacement (phone calls, spreadsheets)  
**Solution:** AI agent that reads schedule, finds available replacements, sends notifications  
**ROI:** 5-10 hours saved per week per department

**Agent Tasks:**
- Read current shift schedule
- Identify open shifts (sick calls, vacations)
- Search qualified replacement pool (certifications, availability)
- Send automated notifications
- Handle responses and confirmations
- Update schedule automatically

### 2. Phone Number Management (Ready to Test)
**Market:** MSPs managing thousands of numbers  
**Problem:** Unused numbers still billing ($1-3/month each)  
**Solution:** AI agent that monitors usage and auto-releases unused numbers  
**ROI:** 20-40% cost reduction on number inventory

**Agent Tasks:**
- Monitor call history across all numbers
- Identify unused numbers (configurable threshold)
- Auto-release numbers meeting criteria
- Send alerts before releasing (safety check)
- Track cost savings over time

### 3. BA Network (Long-term Play)
**Market:** Small businesses needing automation  
**Problem:** Can't afford custom development  
**Solution:** Pre-built agent templates for common workflows  
**ROI:** $50-200/month per business

**Agent Templates:**
- Lead qualification and routing
- Appointment scheduling
- Invoice reminders
- Customer support triage
- Social media monitoring

---

## 📈 Revenue Potential

### Conservative Estimates

**Fire Department Market:**
- 30,000 departments in US
- 1% adoption = 300 customers
- $500/month average = **$150k MRR = $1.8M ARR**

**Phone Number Management:**
- 50 MSP customers
- $200/month average = **$10k MRR = $120k ARR**

**BA Network:**
- 500 small businesses
- $100/month average = **$50k MRR = $600k ARR**

**Total Potential:** $210k MRR = **$2.52M ARR**

**Your Deal (25% equity + advisor):**
- Year 1: $630k in value
- Year 3: $2M+ in value (assuming 3x growth)

---

## ✅ Checklist for This Weekend

Copy this checklist and check off as you complete:

- [ ] Read DIMA_QUICK_START_GUIDE.md
- [ ] Review DIMA_REACT_INTEGRATION_GUIDE.md
- [ ] Test Agent 441 via web interface
- [ ] Test API connection with curl
- [ ] Create your own test agent (phone or fire dept)
- [ ] Execute agent with custom context
- [ ] Review React component examples
- [ ] List questions for Monday/Tuesday call
- [ ] Confirm call time via email
- [ ] Start thinking about fire department requirements

---

## 🎓 Learning Resources

### IRIS Platform Docs
- API Reference: https://heyiris.io/api/docs
- User Guide: https://app.heyiris.io/help
- Video Tutorials: [Coming soon]

### Custom Functions Deep Dive
- See: `CUSTOM_FUNCTIONS_IMPLEMENTATION.md` in this directory
- Template variables guide
- Authentication patterns
- Multi-tenant examples

### PHP SDK Examples
- See: `SETUP_EXAMPLES.md` in this directory
- Complete working examples
- Common patterns
- Best practices

---

## 🏁 Final Notes

**This is just the beginning!** The IRIS Platform is designed to grow with your business:

- ✅ Start simple (phone number management)
- ✅ Scale to enterprise (fire department scheduling)
- ✅ Expand to network (BA small business templates)

**Your advantage:** You understand the business problems deeply. IRIS provides the AI automation layer. Together, we can build something massive.

**Remember:** The BA Conference is just 5 weeks away. Let's make it count!

---

## 📬 We're Here to Help

Building this POC is a partnership. We're committed to your success:

- Quick response times (< 4 hours)
- Technical support whenever you need it
- Architecture guidance
- Code reviews
- Demo preparation help
- Conference booth support (if needed)

**Let's build something amazing together! 🚀**

---

**Document Version:** 1.0  
**Last Updated:** January 9, 2026, 10:00 PM  
**Next Update:** After weekend testing (Jan 12, 2026)

---

## Appendix: File Locations

All files mentioned in this document:

```
/Users/AlexMayo/Sites/freelabel/
├── DIMA_QUICK_START_GUIDE.md
├── DIMA_REACT_INTEGRATION_GUIDE.md
├── DIMA_POC_IMPLEMENTATION_STATUS.md
└── fl-docker-dev/sdk/php/
    ├── DIMA_DELIVERY_PACKAGE.md (this file)
    ├── DIMA_TEST_AGENT.txt
    ├── DIMA_POC_SUMMARY.md
    ├── DIMA_POC_GUIDE.md
    ├── DIMA_MEETING_NOTES_2026-01-09.md
    ├── DIMA_ANALYSIS_RECOMMENDATIONS_2026-01-09.md
    ├── LEAD_MANAGEMENT_WORKFLOW.md (comprehensive workflow guide)
    ├── TECHNICAL.md (complete SDK reference - 128KB!)
    ├── CUSTOM_FUNCTIONS_IMPLEMENTATION.md
    ├── create-dima-test-agent.php
    └── javascript/iris-sdk.js
```

### Key Technical References for Development

When building your POC, these two files are your primary technical resources:

1. **`LEAD_MANAGEMENT_WORKFLOW.md`** (1,439 lines)
   - Complete workflow examples
   - Lead/agent/task management patterns
   - Real-world use cases
   - CLI commands for every operation
   - Agent workflow attachment and configuration

2. **`TECHNICAL.md`** (128,993 bytes)
   - Full SDK API reference
   - Every endpoint documented with examples
   - PHP and JavaScript code samples
   - Advanced features (RAG, HITL, multi-agent orchestration)
   - Production deployment patterns

**Happy Building! 🎉**
