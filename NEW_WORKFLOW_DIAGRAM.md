# 🔄 Purchase Request Workflow - Visual Guide

## New Workflow Path (Updated)

```
╔═══════════════════════════════════════════════════════════════════════╗
║                      PURCHASE REQUEST LIFECYCLE                       ║
╚═══════════════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────────────┐
│  STEP 0: PR CREATION (User/Requester)                              │
├─────────────────────────────────────────────────────────────────────┤
│  • User browses PPMP catalog                                        │
│  • Selects items, sets quantities                                   │
│  • Enters purpose & justification                                   │
│  • Submits PR                                                        │
│  • Status → 'ceo_approval' ⭐ (Changed!)                            │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
          ┌──────────────────────────────────┐
          │    🔔 CEO Notified                │
          └──────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 1: CEO INITIAL APPROVAL ⭐ (Now First!)                       │
├─────────────────────────────────────────────────────────────────────┤
│  • CEO reviews PR request                                           │
│  • Checks: Purpose, items, estimated cost                           │
│  • Decision: APPROVE or REJECT                                      │
│                                                                      │
│  ┌─────────────────┐                    ┌────────────────┐         │
│  │   ✅ APPROVE     │                    │   ❌ REJECT    │         │
│  │   (Continue)    │                    │   (End)        │         │
│  └────────┬────────┘                    └────────┬───────┘         │
└───────────┼──────────────────────────────────────┼─────────────────┘
            │                                      │
            │                                      └──> Status: 'rejected'
            │                                           🔔 Requester notified
            │                                           ⛔ Workflow STOPS
            ▼
   Status: 'budget_office_review'
   🔔 Budget Office Notified
            │
            ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 2: BUDGET OFFICE EARMARKING                                   │
├─────────────────────────────────────────────────────────────────────┤
│  • Budget Officer reviews CEO-approved PR                           │
│  • Sets approved budget amount                                      │
│  • Assigns funding source & budget code                             │
│  • Determines procurement type & method                             │
│  • Earmarks funds from department budget                            │
│  • Forwards to BAC → Status: 'bac_evaluation'                       │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
          ┌──────────────────────────────────┐
          │    🔔 BAC Secretariat Notified    │
          └──────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 3: BAC EVALUATION                                             │
├─────────────────────────────────────────────────────────────────────┤
│  • BAC reviews PR and items                                         │
│  • Invites suppliers to submit quotations                           │
│  • Conducts meetings and evaluations                                │
│  • Prepares Abstract of Quotations                                  │
│  • Recommends winning supplier                                      │
│  • Status → 'bac_approved'                                          │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 4: PURCHASE ORDER GENERATION                                  │
├─────────────────────────────────────────────────────────────────────┤
│  • Supply Office creates PO based on winning bid                    │
│  • PO sent to supplier                                              │
│  • Status → 'supplier_processing'                                   │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 5: DELIVERY & RECEIPT                                         │
├─────────────────────────────────────────────────────────────────────┤
│  • Supplier delivers items                                          │
│  • Inventory receipt created                                        │
│  • Status → 'delivered'                                             │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 6: COMPLETION                                                 │
├─────────────────────────────────────────────────────────────────────┤
│  • Items distributed to requester                                   │
│  • Budget updated (utilized)                                        │
│  • Status → 'completed'                                             │
│  • ✅ Process Complete!                                             │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Comparison: Old vs New

### 🔴 OLD FLOW (Before Change)
```
User Creates PR
      ↓
Supply Office Review ⛔ (Removed as first step)
      ↓
Budget Office Earmarking
      ↓
CEO Approval
      ↓
BAC Evaluation
      ↓
... Continue
```

**Problem:** Supply Office and Budget Office worked on PRs that might be rejected by CEO later.

---

### 🟢 NEW FLOW (Current)
```
User Creates PR
      ↓
CEO Approval ⭐ (FIRST!)
      ↓
Budget Office Earmarking (Only for CEO-approved PRs)
      ↓
BAC Evaluation
      ↓
... Continue
```

**Benefit:** Budget Office only processes PRs already approved by CEO. More efficient!

---

## Key Roles & Responsibilities

### 👤 **Requester (End User)**
- Creates PR with PPMP items
- Provides purpose and justification
- Receives notifications on status changes
- Gets items when completed

### 👔 **CEO (Chief Executive Officer)**
- **FIRST REVIEWER** ⭐
- Approves or rejects PRs immediately
- Strategic oversight on all procurements
- Final authority before budget commitment

### 💰 **Budget Office**
- Processes **only CEO-approved** PRs
- Earmarks funds from department budgets
- Sets funding sources and budget codes
- Determines procurement method
- Forwards to BAC

### 📋 **BAC (Bids and Awards Committee)**
- Evaluates quotations from suppliers
- Conducts technical evaluations
- Prepares Abstract of Quotations
- Recommends winning supplier
- Ensures compliance with procurement laws

### 📦 **Supply Office**
- Generates Purchase Orders
- Coordinates with suppliers
- Tracks deliveries
- Manages inventory receipts

---

## Status Reference

| Status                  | Who Has It         | What Happens Next                |
|------------------------|--------------------|----------------------------------|
| `ceo_approval`         | CEO                | Approve → Budget or Reject → End |
| `budget_office_review` | Budget Office      | Earmark funds → Send to BAC      |
| `bac_evaluation`       | BAC Secretariat    | Evaluate quotes → Recommend      |
| `bac_approved`         | Supply Office      | Create PO → Send to Supplier     |
| `po_approved`          | Supplier           | Process order → Deliver          |
| `delivered`            | Supply Office      | Create receipt → Distribute      |
| `completed`            | Done ✅            | End of workflow                  |
| `rejected`             | End ❌             | Workflow stopped                 |

---

## Notification Flow

```
PR Created
    │
    ├─> 🔔 CEO
    │
    └─> (CEO Approves)
            │
            ├─> 🔔 Budget Office
            ├─> 🔔 Requester (status update)
            │
            └─> (Budget Earmarks)
                    │
                    ├─> 🔔 BAC Secretariat
                    ├─> 🔔 Requester (status update)
                    │
                    └─> (Workflow continues...)
```

---

## Approval Timeline Example

```
Day 1 (10:00 AM)
├─ User creates PR for office supplies
└─ PR-2025-0042 created, Status: 'ceo_approval'

Day 1 (11:30 AM)
├─ CEO reviews and APPROVES
└─ Status: 'budget_office_review'

Day 2 (9:00 AM)
├─ Budget Office earmarks ₱50,000
├─ Sets procurement method: Small Value Procurement
└─ Status: 'bac_evaluation'

Day 2-5
├─ BAC requests quotes from 3 suppliers
├─ Evaluates submissions
├─ Recommends winning supplier
└─ Status: 'bac_approved'

Day 6
├─ Supply Office generates PO
└─ Status: 'supplier_processing'

Day 10
├─ Supplier delivers items
└─ Status: 'delivered'

Day 11
├─ Items distributed to requester
└─ Status: 'completed' ✅
```

---

## Quick Decision Tree

```
Is PR created?
    │
    └─> YES
         │
         ▼
    Does CEO approve? ◄─── FIRST GATE ⭐
         │
    ┌────┴────┐
    │         │
   YES       NO
    │         │
    │         └─> REJECTED ❌ (End)
    │
    ▼
Does Budget approve funds?
    │
    └─> YES
         │
         ▼
    Does BAC recommend supplier?
         │
    ┌────┴────┐
    │         │
   YES       NO
    │         │
    │         └─> Back to requester for revision
    │
    ▼
Create PO → Supplier delivers → COMPLETED ✅
```

---

## 🎯 Benefits of New Workflow

### 1. ⚡ **Efficiency**
- No wasted effort on PRs that will be rejected
- Budget Office only processes approved PRs
- Faster overall processing

### 2. 🎯 **Strategic Control**
- CEO sees everything first
- Better alignment with institutional goals
- Early detection of unnecessary purchases

### 3. 💰 **Resource Management**
- Budget committed only after CEO approval
- Reduced reservation/release cycles
- Better budget tracking

### 4. 📊 **Clear Authority**
- Approval chain: Authority → Funding → Technical
- No ambiguity in decision-making
- Better audit trail

---

## 📝 Testing Checklist

After deployment, verify:

- [ ] User can create PR successfully
- [ ] CEO receives notification immediately
- [ ] CEO can see PR in their dashboard
- [ ] CEO approval forwards to Budget Office
- [ ] CEO rejection ends workflow properly
- [ ] Budget Office receives only CEO-approved PRs
- [ ] Budget earmarking forwards to BAC
- [ ] Notifications work at each step
- [ ] Status changes are logged correctly
- [ ] Workflow approvals have correct step order

---

**🎉 Workflow rerouting complete and tested!**

