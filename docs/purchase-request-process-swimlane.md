# CagSU-SVP Purchase Request Process — Swimlane Diagram

This document describes the current end-to-end Purchase Request (PR) workflow and who is responsible at each stage. The diagram uses Mermaid **flowchart with subgraphs** to represent swimlanes (one per actor).

## Process Overview

| Actor | Responsibility |
|-------|----------------|
| **Requester (Dean/Department)** | Creates PR (draft or submit), receives returned PR for revision |
| **Supply Office** | Initial review, activate/return/reject, create PO, send to supplier, mark delivered/complete |
| **Budget Office** | Earmark budget, set funding source, forward to CEO or reject |
| **CEO (Campus Executive Officer)** | Approve PR for BAC or reject; approves PO |
| **BAC (Bids and Awards Committee)** | Evaluation, RFQ, quotations, AOQ, finalize abstract; can create PO per group; continues BAC tasks for remaining groups during partial PO |
| **Supplier** | Receives PO, delivers items (external; actions recorded by Supply) |

---

## Swimlane Diagram (Mermaid)

```mermaid
flowchart TB
    subgraph Requester ["Requester (Dean / Department)"]
        direction TB
        R1[Create PR - Draft or Submit]
        R2[Submit PR for review]
        R3[Revise and resubmit if returned]
    end

    subgraph Supply ["Supply Office"]
        direction TB
        S1[Receive PR - Start review]
        S2[Activate PR - Forward to Budget]
        S3[Return to Requester / Reject / Cancel]
        S4[Create Purchase Order per group or whole PR]
        S5[Send PO to Supplier]
        S6[Mark Delivered]
        S7[Mark Completed]
    end

    subgraph Budget ["Budget Office"]
        direction TB
        B1[Earmark budget - Set funding, date needed]
        B2[Forward to CEO]
        B3[Reject PR]
    end

    subgraph CEO ["CEO (Campus Executive Officer)"]
        direction TB
        C1[Approve PR for BAC / Reject]
        C2[Approve Purchase Order]
    end

    subgraph BAC ["BAC (Bids and Awards Committee)"]
        direction TB
        BAC1[Receive PR - BAC Evaluation]
        BAC2[Split items into groups optional]
        BAC3[Generate RFQ per group]
        BAC4[Receive quotations from suppliers]
        BAC5[Evaluate - Resolve ties, overrides]
        BAC6[Generate AOQ per group]
        BAC7[Finalize abstract]
        BAC8[Create PO for group with winner]
        BAC9[Continue AOQ for remaining groups if partial PO]
    end

    subgraph Supplier ["Supplier (External)"]
        direction TB
        SUP1[Receive PO]
        SUP2[Deliver items]
    end

    R1 --> R2
    R2 --> S1
    R3 --> R2

    S1 --> S2
    S1 --> S3
    S3 -.->|Return| R3
    S3 -.->|Reject / Cancel| End1[Rejected or Cancelled]

    S2 --> B1
    B1 --> B2
    B1 --> B3
    B3 -.-> End2[Rejected]

    B2 --> C1
    C1 -.->|Reject| End3[Rejected]
    C1 -->|Approve| BAC1

    BAC1 --> BAC2
    BAC2 --> BAC3
    BAC3 -->|RFQ sent to suppliers| BAC4
    BAC4 -->|Quotations submitted| BAC5
    BAC5 --> BAC6
    BAC6 --> BAC7
    BAC7 --> BAC8

    BAC8 -->|All groups have PO| S4
    BAC8 -->|Some groups have PO| Partial[PR: partial_po_generation]
    Partial --> BAC9
    BAC9 --> BAC6
    BAC9 --> BAC7
    BAC9 --> BAC8

    S4 --> C2
    C2 --> S5
    S5 -->|PO sent| SUP1
    SUP1 --> SUP2
    SUP2 --> S6
    S6 --> S7
    S7 --> End4[PR Completed]
```

---

## Status Flow (Linear View)

| Step | PR Status | Handler / Next |
|------|-----------|----------------|
| 1 | `draft` | Requester |
| 2 | `submitted` / `supply_office_review` | Supply Office |
| 3 | `budget_office_review` | Budget Office |
| 4 | `ceo_approval` | CEO |
| 5 | `bac_evaluation` | BAC |
| 6 | `bac_approved` or `partial_po_generation` | BAC (AOQ/PO) + Supply (create PO) |
| 7 | `po_generation` | Supply (create/send PO) |
| 8 | `po_approved` | CEO (approve PO) |
| 9 | `supplier_processing` | Supplier / Supply |
| 10 | `delivered` | Supply (mark delivered) |
| 11 | `completed` | Supply (mark completed) |

Exit statuses: `cancelled`, `rejected`, `returned_by_supply`.

---

## Partial PO / Grouped PR Flow (Current Behavior)

When a PR is split into **item groups**:

1. BAC evaluates and generates AOQ **per group**.
2. Supply/BAC creates a **PO for one group** when that group has a winner and AOQ.
3. PR status becomes **`partial_po_generation`** (some groups have PO, others do not).
4. **BAC can still** for remaining groups:
   - Generate AOQ (for groups without a PO),
   - Finalize abstract,
   - And then create PO for those groups.
5. When **all groups** have at least one PO, PR status becomes **`po_generation`**.

This is reflected in the swimlane by the loop: **BAC8 → Partial → BAC9 → BAC6/BAC7/BAC8**.

---

## How to View the Diagram

1. **VS Code / Cursor:** Use a Mermaid preview extension, or paste the code block into [Mermaid Live Editor](https://mermaid.live).
2. **GitHub/GitLab:** Render the fenced code block as Mermaid automatically in the repo.

File location: `docs/purchase-request-process-swimlane.md`
