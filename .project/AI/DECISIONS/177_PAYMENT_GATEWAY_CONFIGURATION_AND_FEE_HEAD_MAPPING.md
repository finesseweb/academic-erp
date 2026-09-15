# ADR 177 — Payment Gateway Configuration + Fee Head Product Mapping
Date: 2026-09-10
Status: IMPLEMENTED — OWNER QA REQUIRED

## Decision
Online collection remains gateway-independent. Colleges may configure Razorpay, Cashfree Payments, and PayU. Credentials are stored per College/provider; secrets use Laravel encrypted casts and are never returned to the UI.

Every ACTIVE Fee Head may be mapped independently for each configured gateway to a Product/Item Code plus optional Settlement Code. Product/settlement codes are not globally unique: multiple Fee Heads may intentionally share the same code when the institution/provider settlement contract requires it.

A gateway transaction may carry one total amount, while ERP accounting remains authoritative at Fee Payment Allocation level. The future checkout adapter must derive gateway metadata from the selected allocation's Fee Heads, but verified success must post through the existing FeePaymentService rather than duplicate demand/installment/late-fine accounting.

## Lifecycle
- New gateway configuration starts INACTIVE.
- TEST/LIVE is explicit.
- Key ID + encrypted Secret are required before activation.
- ACTIVE gateway credentials cannot be edited; deactivate first.
- Fee Head mappings have ACTIVE/INACTIVE lifecycle.

## Providers in Phase 1
RAZORPAY, CASHFREE, PAYU.

## Deferred to next ADR
Provider API adapters, checkout/order creation, webhook signature verification, idempotent gateway transaction table, verified-success posting, failure/expiry handling and reconciliation.
