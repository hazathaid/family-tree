# Member Account Invitation Service

An owner or admin can invite an unclaimed `family_member` from its detail page.
The service normalizes the email, rejects an existing account or claimed member,
revokes older pending invitations, stores only a SHA-256 token hash, and sends a
single-use link valid for seven days.

Acceptance is transactional: it locks the member, rechecks token/account state,
creates a verified active user, links `family_members.user_id`, creates or
restores a family `member` role, marks the invitation accepted, and logs the
claim. Possession of the token verifies control of the invited email.

Authorization rules:

- owner/admin can invite an unclaimed profile;
- a linked user can update and upload a photo only for their profile;
- a linked member cannot delete their profile;
- owner/admin retain normal member management rights;
- one user can represent at most one profile per family;
- one profile can link to only one user.

Raw tokens, passwords, and invited emails are not placed in activity-log
payloads. Re-sending creates a new token and invalidates the previous one.
