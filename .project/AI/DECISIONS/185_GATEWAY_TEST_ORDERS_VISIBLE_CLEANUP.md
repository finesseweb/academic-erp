# ADR185 — Gateway Test Orders Visible Cleanup

## Decision
Gateway QA order/attempt records must have an explicit visible cleanup action in Test Data Cleanup, not only implicit Full Academic Reset coverage.

## Contract
- UI label: **Gateway Test Orders**.
- Deletes `online_payment_transactions` scoped to the University.
- Preserves payment gateway credential profiles, encrypted credentials/secrets, provider setup and Fee Head routing/mappings.
- Uses `test_data_cleanup.manage` authorization and the existing cleanup-enabled safety switch.
- Full Academic Reset continues to remove gateway transaction rows in dependency-safe order.
- Future provider adapters/webhook/checkout transactional tables must be added to this cleanup family when introduced.
