# ADR-003 Realtime Transport

## Decision
Support Laravel WebSocket Gateway with Socket.IO for features that genuinely require realtime server push or bidirectional interaction.

## Rule
REST remains the default for standard CRUD and business transactions. WebSocket is used selectively for notifications, chat/presence, live status and similar realtime needs.

## Security
Socket authentication, room membership and events must obey the same RBAC and tenant/scope authorization model as HTTP APIs.
