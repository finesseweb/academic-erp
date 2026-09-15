# Decision 002 - RBAC Authorization

Status: Approved documentation baseline

The ERP authorization model is RBAC with granular `resource.action` permissions plus explicit tenant/resource scope.

Roles are permission bundles. Laravel enforces permissions and scope; React permission checks are UX only. Sensitive workflows may separate entry/request, verify/approve and publish/refund permissions. Security-sensitive changes and high-impact actions require audit consideration.

Physical database implementation must first be reconciled with the existing Zend/MySQL and Laravel migrations / Eloquent schema.
