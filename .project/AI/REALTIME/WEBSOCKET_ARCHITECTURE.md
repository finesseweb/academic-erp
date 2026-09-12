# WebSocket / Realtime Architecture

## Purpose
Realtime capability is provided through Laravel WebSocket Gateway with Socket.IO. It complements REST; it does not replace REST.

## Good Realtime Candidates
- In-app notifications and targeted alerts.
- Chat and message delivery/read state when a chat module exists.
- Online/offline presence when required.
- Background import/export/report/job completion status.
- Approval/workflow state changes that should appear immediately.
- Justified live dashboard or attendance/session status updates.

## Keep as REST
- Standard create/read/update/delete forms.
- Master-data maintenance.
- Search/filter/pagination.
- Reports and exports initiation.
- Financial/academic transactional commands unless server push is needed after the transaction.

## Security
1. Authenticate the socket during handshake using the approved token/session mechanism.
2. Resolve the user server-side.
3. Resolve effective permissions and scopes server-side.
4. Authorize room membership server-side.
5. Authorize sensitive incoming socket events exactly as equivalent REST commands would be authorized.
6. Never trust client-supplied `collegeId`, `role`, `userId`, room name or permission.
7. Disconnect/reject invalid, expired or disabled sessions according to authentication policy.

## Architecture
Gateway handles socket transport and event DTOs. Realtime services coordinate broadcasts. Domain services own business rules. Repositories own database access.

`Gateway -> Realtime/Domain Service -> Repository -> Laravel migrations / Eloquent -> MySQL`

Do not put Laravel migrations / Eloquent calls or substantial business logic directly in gateways.

## Room Strategy
Use server-authorized domain rooms only where needed, e.g. `college:{id}`, `department:{id}`, `user:{id}`, `course:{id}`. Do not automatically join broad role rooms if scoped permission evaluation is required.

## Event Design
Use stable, namespaced event names such as `notification.created`, `job.completed`, `result.published`, `chat.message.created`. Payloads must be minimal, versionable and must not leak unauthorized data.

## Reliability
Critical business writes must be committed to MySQL first. WebSocket delivery is a notification/update channel, not the sole source of truth. Clients must be able to recover current state through REST after reconnecting.
