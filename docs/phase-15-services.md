# Phase 15 Services - Administration

`AdministrationService` coordinates user status changes and family-content moderation through repositories. Suspension revokes Sanctum tokens, self-suspension is rejected, and each successful critical action is audited.

`AuditLogService` records actor, subject, before/after values, IP address, and user agent. It also exposes filtered pagination and bounded CSV export data.

`EloquentAdministrationRepository` owns family review and moderated content queries. `EloquentAuditLogRepository` owns audit storage, filters, pagination, and export queries. Controllers remain limited to authorization, validated input, service calls, and API responses.
