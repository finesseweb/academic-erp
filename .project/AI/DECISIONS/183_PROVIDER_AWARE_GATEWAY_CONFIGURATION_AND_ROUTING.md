# ADR 183 — Provider-Aware Payment Gateway Configuration and Routing

## Status
Implemented — QA pending.

## Context
Payment gateways do not expose the same credential or routing contract. A college may receive multiple credential pairs under one provider login/account, while other providers may additionally require a merchant/product identifier. A single generic `Key ID + Secret + mandatory Product Code` contract is therefore incorrect.

## Decision
Payment Gateway configuration is provider-aware and data-driven.

### Credential profiles
A college may create any number of TEST/LIVE credential profiles per provider. Fee Heads independently route to one profile or remain unassigned.

### Provider contracts
- Razorpay: Key ID + Key Secret. Merchant reference optional. Fee Head Product Code optional; selected credentials determine the account route.
- Cashfree: App ID/Client ID + Secret Key/Client Secret. Merchant reference optional. Fee Head Product Code optional; selected credentials determine the account route.
- PayU: Merchant Key + Salt. Merchant reference optional. Fee Head Product Code optional. When blank, transaction `productinfo` is generated from ERP Fee Head/transaction context.
- NTT DATA Payment Services (Atom): Merchant ID + Login + Password. Provider-specific encrypted configuration can hold Default Product ID and encryption/decryption keys. Product ID must resolve either from the credential profile default or a Fee Head override before the Fee Head can be routed.

### Extensibility
`college_payment_gateways.provider_config` is encrypted JSON. It exists so provider-specific credential/config fields can be added without repeatedly redesigning the database. New providers still require an explicit provider definition and payment adapter; the system does not pretend arbitrary unknown gateways work automatically.

### Fee Head routing
`fee_head_gateway_mappings.product_code` is nullable. Product Code is not globally mandatory. Provider capability determines whether it is optional or required. Settlement Code remains optional/internal unless a provider adapter uses it.

### Accounting invariant
Gateway routing must never replace ERP accounting allocation. A successful, verified online transaction will eventually enter the existing Fee Payment + deterministic allocation + receipt engine. Gateway/provider data determines where/how the external transaction is initiated; Fee Payment allocations remain authoritative for which ERP Fee Heads/liabilities were paid.

## Security
Secrets remain encrypted at rest through Eloquent encrypted casts and are never returned to the browser. Provider-specific sensitive configuration is stored inside encrypted `provider_config`.

## QA
Pending provider-aware configuration and routing QA before transaction adapters are implemented.
