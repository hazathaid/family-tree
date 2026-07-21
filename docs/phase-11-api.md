# Phase 11 API - Search

All endpoints require a Sanctum bearer token and return the standard API envelope.

## Global and advanced search

`GET /api/v1/search`

Parameters:

| Parameter | Description |
| --- | --- |
| `keyword` | Searches member names/cities, article titles/content, and event titles/descriptions/locations. |
| `family_uuid` | Restricts results to one accessible family. |
| `family_id` | Legacy numeric alternative to `family_uuid`. |
| `name` | Member full-name or nickname filter. |
| `city` | Member birth or death city filter. |
| `status` | `alive` or `deceased`. |
| `generation` | Generation from -100 to 100 relative to `root_member_uuid`. |
| `root_member_uuid` | Required with `generation`; generation 0 is this member. |
| `limit` | Maximum results per type, 1-100; default 15. |

Only families assigned to the authenticated user are searched. Without `keyword`, only member advanced filters return results.

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "members": [],
    "articles": [],
    "events": []
  }
}
```
