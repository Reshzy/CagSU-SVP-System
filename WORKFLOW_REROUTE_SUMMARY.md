# PR Workflow Rerouting - Summary

## Changes Made ✅

The Purchase Request workflow has been successfully rerouted. PRs now go to the CEO **first** for initial approval before going to the Budget Office.

---

## New Workflow

```
┌─────────────────────────────────────────────────────────────┐
│                    UPDATED PR WORKFLOW                      │
└─────────────────────────────────────────────────────────────┘

1. PR CREATION
   ├─ User submits PR with PPMP items
   ├─ Status: 'ceo_approval'
   └─ Notification sent to: CEO

          ↓

2. CEO INITIAL APPROVAL
   ├─ CEO reviews the PR
   ├─ Decision: Approve or Reject
   │
   ├─ If APPROVED → Status: 'budget_office_review'
   │                 Notification sent to: Budget Office
   │
   └─ If REJECTED → Status: 'rejected'
                    Process ends

          ↓

3. BUDGET OFFICE EARMARKING
   ├─ Budget Officer reviews and earmarks funds
   ├─ Sets: funding source, budget code, procurement type
   ├─ Status: 'bac_evaluation'
   └─ Notification sent to: BAC Secretariat

          ↓

4. BAC EVALUATION
   ├─ BAC evaluates quotations
   ├─ Recommends winning supplier
   └─ (Rest of workflow continues as before)

          ↓

5. PO GENERATION → DELIVERY → COMPLETION
```

---

## Old vs New Comparison

### ❌ OLD WORKFLOW
```
PR Created → Supply Office → Budget Office → CEO → BAC → ...
```

### ✅ NEW WORKFLOW
```
PR Created → CEO → Budget Office → BAC → ...
```

---

## Files Modified

### 1. `app/Http/Controllers/PurchaseRequestController.php`
**Changes:**
- PR initial status changed from `'submitted'` to `'ceo_approval'`
- Notification now sent to CEO instead of Supply Officer
- Workflow approval created for `'ceo_initial_approval'` step

**Lines changed:**
```php
// Line 117: Status set to CEO approval
'status' => 'ceo_approval',

// Lines 175-184: Notify CEO and create approval
$ceoUsers = \App\Models\User::role('CEO')->get();
WorkflowRouter::createPendingForRole($purchaseRequest, 'ceo_initial_approval', 'CEO');
```

---

### 2. `app/Http/Controllers/CeoApprovalController.php`
**Changes:**
- After CEO approval, PR now goes to Budget Office instead of BAC
- Status changed from `'bac_evaluation'` to `'budget_office_review'`
- Workflow approval created for Budget Office instead of BAC
- Step order updated to 1 (first step)

**Lines changed:**
```php
// Line 44: New status after CEO approval
$newStatus = $decision === 'approve' ? 'budget_office_review' : 'rejected';

// Line 52: Step order now 1 (was 3)
'step_order' => 1,

// Lines 75-77: Create approval for Budget Office
WorkflowRouter::createPendingForRole($purchaseRequest, 'budget_office_earmarking', 'Budget Office');
```

---

### 3. `app/Http/Controllers/BudgetEarmarkController.php`
**Changes:**
- After Budget Office earmarking, PR now goes to BAC instead of CEO
- Status changed from `'ceo_approval'` to `'bac_evaluation'`
- Workflow approval created for BAC instead of CEO
- Success message updated

**Lines changed:**
```php
// Line 76: New status after budget earmarking
$purchaseRequest->status = 'bac_evaluation';

// Lines 80-81: Create approval for BAC
WorkflowRouter::createPendingForRole($purchaseRequest, 'bac_evaluation', 'BAC Secretariat');

// Line 87: Updated success message
return redirect()->route('budget.purchase-requests.index')->with('status', 'Earmark approved and forwarded to BAC.');
```

---

### 4. `app/Services/WorkflowRouter.php`
**Changes:**
- Step order completely revised to reflect new workflow
- CEO initial approval is now step 1
- Budget Office earmarking is now step 2
- BAC evaluation is now step 3

**New step order:**
```php
$map = [
    'ceo_initial_approval' => 1,        // CEO reviews PR first
    'budget_office_earmarking' => 2,    // Budget Office earmarks funds
    'bac_evaluation' => 3,              // BAC evaluates quotations
    'bac_award_recommendation' => 4,    // BAC recommends award
    'ceo_final_approval' => 5,          // CEO final approval (optional)
    'po_generation' => 6,               // Purchase Order creation
    'po_approval' => 7,                 // PO approval
    'supply_office_review' => 8,        // Legacy/fallback step
];
```

---

## Impact on System

### ✅ Benefits of New Workflow

1. **Early CEO Oversight**
   - CEO sees all PRs immediately after creation
   - Can reject unsuitable requests early, saving time
   - Better strategic control over procurement

2. **Efficient Resource Use**
   - Budget Office only works on CEO-approved PRs
   - No wasted effort earmarking rejected requests
   - Faster overall processing

3. **Clear Authority Chain**
   - CEO approval comes first (authority)
   - Budget Office follows (funding)
   - BAC handles technical evaluation (expertise)

4. **Reduced Rework**
   - CEO can catch issues before budget earmarking
   - Less back-and-forth between departments

---

## Status Flow Chart

```
┌─────────────────┐
│ PR Created      │
│ Status:         │
│ 'ceo_approval'  │
└────────┬────────┘
         │
         ▼
    ┌────────┐
    │  CEO   │
    │Reviews │
    └───┬────┘
        │
    ┌───┴───┐
    │       │
 Approve  Reject
    │       │
    │       └──────────> 'rejected' (END)
    │
    ▼
┌───────────────────┐
│ Budget Office     │
│ Status:           │
│'budget_office_    │
│     review'       │
└────────┬──────────┘
         │
         ▼ (Earmarks funds)
┌─────────────────┐
│ BAC Evaluation  │
│ Status:         │
│'bac_evaluation' │
└────────┬────────┘
         │
         ▼
    (Continue workflow...)
```

---

## Database Records

### WorkflowApproval Step Order

When a PR flows through the system, workflow approvals are created with these step orders:

| Step Name                    | Step Order | Handler           |
|------------------------------|------------|-------------------|
| ceo_initial_approval         | 1          | CEO               |
| budget_office_earmarking     | 2          | Budget Office     |
| bac_evaluation              | 3          | BAC Secretariat   |
| bac_award_recommendation    | 4          | BAC               |
| ceo_final_approval          | 5          | CEO (optional)    |
| po_generation               | 6          | Supply Office     |
| po_approval                 | 7          | CEO/Authorized    |

---

## Testing the New Workflow

### Test Scenario 1: Happy Path
1. ✅ Create a new PR
2. ✅ Verify CEO receives notification
3. ✅ CEO approves the PR
4. ✅ Verify Budget Office receives notification
5. ✅ Budget Office earmarks funds
6. ✅ Verify BAC receives notification
7. ✅ Continue through workflow

### Test Scenario 2: CEO Rejection
1. ✅ Create a new PR
2. ✅ CEO rejects the PR
3. ✅ Verify PR status = 'rejected'
4. ✅ Verify requester receives rejection notification
5. ✅ Verify workflow stops (no Budget Office notification)

### Test Queries

Check PR status flow:
```sql
SELECT 
    pr_number,
    status,
    created_at,
    status_updated_at
FROM purchase_requests
WHERE created_at > NOW() - INTERVAL 1 DAY
ORDER BY created_at DESC;
```

Check workflow approvals:
```sql
SELECT 
    pr.pr_number,
    wa.step_name,
    wa.step_order,
    wa.status,
    wa.responded_at,
    u.name as approver_name
FROM workflow_approvals wa
JOIN purchase_requests pr ON wa.purchase_request_id = pr.id
JOIN users u ON wa.approver_id = u.id
WHERE pr.created_at > NOW() - INTERVAL 1 DAY
ORDER BY pr.pr_number, wa.step_order;
```

---

## Notifications Affected

### Updated Notification Flow

| Event                | Who Gets Notified | Notification Type              |
|---------------------|-------------------|--------------------------------|
| PR Created          | CEO               | PurchaseRequestSubmitted       |
| CEO Approved        | Budget Office     | PurchaseRequestActionRequired  |
| CEO Rejected        | Requester         | PurchaseRequestStatusUpdated   |
| Budget Earmarked    | BAC Secretariat   | PurchaseRequestActionRequired  |
| Budget → BAC        | Requester         | PurchaseRequestStatusUpdated   |

---

## Rollback Instructions

If you need to revert to the old workflow:

1. **PurchaseRequestController.php**
   - Change status back to `'submitted'`
   - Change notification to Supply Officer
   - Change workflow step to `'supply_office_review'`

2. **CeoApprovalController.php**
   - Change status back to `'bac_evaluation'`
   - Change workflow to BAC Secretariat
   - Change step order back to 3

3. **BudgetEarmarkController.php**
   - Change status back to `'ceo_approval'`
   - Change workflow to Executive Officer
   - Update success message

4. **WorkflowRouter.php**
   - Restore old step order (Supply=1, Budget=2, CEO=3)

---

## Summary

✅ **Completed:**
- Workflow successfully rerouted
- CEO now receives PRs first
- Budget Office receives CEO-approved PRs
- BAC receives budget-earmarked PRs
- All notifications updated
- Step ordering corrected
- Code comments updated

🎯 **Result:**
- More efficient approval process
- Early CEO oversight
- Reduced wasted effort
- Clearer authority chain

📝 **No Additional Changes Needed:**
- Database structure remains the same
- No migrations required
- Existing PRs not affected
- Views continue to work

---

**Status**: Production Ready ✨

