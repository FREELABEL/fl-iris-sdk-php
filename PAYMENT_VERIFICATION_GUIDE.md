# Payment Verification Guide

Quick reference for verifying Stripe payments for leads using the IRIS SDK CLI.

## Quick Check

```bash
# Fastest way to verify payment
./bin/iris payments <lead_id> --summary
```

## Complete Workflow: From Deal to Payment

### 1. Search for the Lead

```bash
./bin/iris sdk:call leads.search search="John Ayala" bloq_id=40
```

**Output:**
```
#110 │ Dr. John F. Ayala, PE, PMP, LSSMBB │ ⚡ Negotiation
```

### 2. Check Payment Status

```bash
./bin/iris payments 110 --summary
```

**Output:**
```
Payment Summary - Dr. John F. Ayala
====================================

 Customer:    John Ayala
 Email:       jayala@aec-hq.com
 Stripe ID:   cus_TcnOtQuBmG7vkE
 
 ✅ Status: PAID
 
 Invoices:    1 total, 1 paid, 0 pending
 Payments:    1 successful
 Total Paid:  $541.25

 [OK] Payment received!
```

### 3. Mark Payment Tasks Complete

```bash
# Mark all payment-related tasks as done
./bin/iris sdk:call leads.tasks.update 110 67 is_completed=true notes="Payment received!"
./bin/iris sdk:call leads.tasks.update 110 118 is_completed=true notes="Payment received!"
./bin/iris sdk:call leads.tasks.update 110 13 is_completed=true notes="Payment received!"
```

### 4. Update Lead Status to Won

```bash
./bin/iris sdk:call leads.update 110 status=Won
```

### 5. Add Payment Confirmation Note

```bash
curl -X POST "https://apiv2.heyiris.io/api/v1/leads/110/notes" \
  -H "Authorization: Bearer $IRIS_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"message": "Payment received! Invoice paid in full. Total: $541.25 via Stripe. Deal closed successfully."}'
```

### 6. Verify Final Status

```bash
./bin/iris sdk:call leads.search search="Ayala" bloq_id=40
```

**Output:**
```
#110 │ Dr. John F. Ayala, PE, PMP, LSSMBB │ ✓ Won
```

## Detailed Payment Information

For complete details including PDF invoices, card information, and transaction history:

```bash
./bin/iris payments 110
```

**Output includes:**
- 📋 Customer Information
- 🧾 Invoice List with PDF download links
- 💰 Payment Transactions with card details
- 📊 Financial Summary

## Automation Examples

### Bash Script: Verify Multiple Payments

```bash
#!/bin/bash
# verify-payments.sh - Check payment status for multiple leads

LEADS=(110 24 53 65 80)

for lead_id in "${LEADS[@]}"; do
  echo "=== Lead #$lead_id ==="
  ./bin/iris payments $lead_id --summary
  echo ""
done
```

### JSON Processing: Extract Key Metrics

```bash
# Get total paid amount
./bin/iris payments 110 --json | jq '.total_paid'
# Output: 541.25

# Check if payment is complete
./bin/iris payments 110 --json | jq '.summary.paid_invoices > 0'
# Output: true

# Get customer email
./bin/iris payments 110 --json | jq -r '.customer.email'
# Output: jayala@aec-hq.com

# Get invoice PDF URL
./bin/iris payments 110 --json | jq -r '.invoices[0].invoice_pdf'
# Output: https://pay.stripe.com/invoice/...
```

### PHP Script: Automated Payment Verification

```php
<?php
require 'vendor/autoload.php';

use IRIS\SDK\IRIS;

$iris = new IRIS([
    'api_key' => getenv('IRIS_API_KEY'),
    'user_id' => 193,
]);

// Check payment status
$payments = $iris->leads->stripePayments(110);

if ($payments['summary']['paid_invoices'] > 0) {
    echo "✅ Payment confirmed: \${$payments['total_paid']}\n";
    
    // Auto-complete payment tasks
    $tasks = $iris->leads->tasks(110)->all();
    foreach ($tasks as $task) {
        if (str_contains(strtolower($task['title']), 'invoice') ||
            str_contains(strtolower($task['title']), 'payment')) {
            
            $iris->leads->tasks(110)->update($task['id'], [
                'is_completed' => true,
                'notes' => 'Auto-completed: Payment verified via Stripe'
            ]);
            echo "  ✓ Marked task #{$task['id']} as complete\n";
        }
    }
    
    // Update lead status
    $iris->leads->update(110, ['status' => 'Won']);
    echo "  ✓ Lead status updated to Won\n";
    
} else {
    echo "⏳ Payment pending\n";
}
```

## Command Reference

| Command | Description | Use Case |
|---------|-------------|----------|
| `payments <id>` | Full payment details | Deep investigation |
| `payments <id> --summary` | Quick status check | Daily verification |
| `payments <id> --json` | Raw JSON data | Automation/scripting |
| `sdk:call leads.stripePayments <id>` | Alternative method | Programmatic access |

## Visual Status Indicators

- **✅ PAID** - Invoice fully paid
- **⏳ PENDING** - Payment in progress  
- **📝 DRAFT** - Invoice not yet sent
- **❌ VOID** - Invoice cancelled
- **✅ SUCCEEDED** - Payment transaction successful
- **⏳ PENDING** - Payment processing
- **❌ FAILED** - Payment failed

## Troubleshooting

### "No Stripe customer found"

**Possible causes:**
- Lead email doesn't match Stripe customer email
- Customer doesn't exist in your Stripe account
- Wrong lead ID

**Solution:**
```bash
# Verify lead email
./bin/iris sdk:call leads.get 110 --json | jq -r '.email'

# Search in Stripe dashboard: https://dashboard.stripe.com/customers
```

### Payment shows but total_paid is 0

**Cause:** Payment may have failed or been refunded

**Solution:**
```bash
# Check payment status
./bin/iris payments 110 --json | jq '.payments[] | {id, status, amount}'
```

### Invoice PDF link doesn't work

**Cause:** Link may have expired (Stripe links expire after certain time)

**Solution:** Generate new link from Stripe dashboard or resend invoice

## Integration with Lead Management

### Recommended Workflow

1. **Client Notification** → "I just paid the invoice"
2. **Verify Payment** → `./bin/iris payments <lead_id> --summary`
3. **If Paid:**
   - Mark payment tasks complete
   - Update lead status to "Won"
   - Add confirmation note
   - Send thank you email
4. **If Pending:**
   - Add note about follow-up needed
   - Create reminder task
   - Wait 24-48 hours and check again

### Priority Score Impact

After marking lead as "Won" with payment verified:
- Base score: +80 points (Won status)
- Recent activity: +10 points
- Notes added: +3 points
- **Total: ~93+ priority score**

This ensures paid clients show up at the top of your priority list!

## Related Documentation

- [LEAD_MANAGEMENT_WORKFLOW.md](LEAD_MANAGEMENT_WORKFLOW.md) - Complete lead management guide
- [TECHNICAL.md](TECHNICAL.md) - Full SDK documentation  
- [README.md](README.md) - Getting started guide

## Quick Copy-Paste Commands

```bash
# Complete payment verification workflow (one-liner)
./bin/iris payments 110 --summary && \
./bin/iris sdk:call leads.update 110 status=Won && \
echo "✅ Payment verified and lead marked as Won!"

# Check payments for all Won leads
./bin/iris sdk:call leads.search status=Won bloq_id=40 --json | \
jq -r '.data[].id' | \
while read lead_id; do
  echo "=== Lead #$lead_id ==="
  ./bin/iris payments $lead_id --summary
  echo ""
done
```

---

**Pro Tip:** Bookmark this command for daily checks:
```bash
alias check-payment='./bin/iris payments'

# Then simply:
check-payment 110 --summary
```
