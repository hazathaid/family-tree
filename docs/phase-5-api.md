# Phase 5 API Documentation

Phase 5 covers FT-501 through FT-508. This document is an implementation blueprint; the endpoints are not implemented yet.

Base URL:

```text
/api/v1
```

All endpoints require Sanctum bearer authentication. The authenticated user must be a member of the requested family. Responses use the standard project envelope, except binary export responses.

## Generate Family Tree

```http
GET /tree/generate
```

Query parameters:

| Parameter | Required | Default | Allowed values |
| --- | --- | --- | --- |
| `member_uuid` | Yes | - | Accessible family member UUID |
| `mode` | No | `full` | `ancestor`, `descendant`, `full` |
| `depth` | No | `5` | Integer, 1-20 |
| `layout` | No | `vertical` | `vertical`, `horizontal`, `radial` |

`depth` limits the number of BFS edges from the root. For ancestor and descendant modes it represents the maximum displayed generations. Full mode also uses it as a safety boundary; clients can request another segment from a selected node.

Example response:

```json
{
  "success": true,
  "message": "Tree generated successfully",
  "data": {
    "root_member_uuid": "member-public-uuid",
    "mode": "ancestor",
    "depth": 5,
    "layout": "vertical",
    "cached": false,
    "nodes": [
      {
        "uuid": "member-public-uuid",
        "name": "Ahmad Santoso",
        "nickname": "Ahmad",
        "birth_year": 1980,
        "is_alive": true,
        "profile_photo_url": null,
        "generation": 0,
        "is_root": true,
        "position": {"x": 480, "y": 80}
      }
    ],
    "edges": [
      {
        "source_uuid": "child-public-uuid",
        "target_uuid": "father-public-uuid",
        "relationship": "father"
      }
    ],
    "viewport": {"width": 960, "height": 720},
    "statistics": {
      "members": 1,
      "generations": 1,
      "living": 1,
      "deceased": 0
    }
  }
}
```

The API exposes base relationship labels only. Derived kinship labels remain the responsibility of the Relationship Engine.

## Export PNG

```http
GET /tree/export/png
```

Query parameters:

```text
member_uuid
mode=ancestor|descendant|full
depth=1..20
layout=vertical|horizontal|radial
paper_size=A4|A3|A2
```

Default values are `full`, `5`, `vertical`, and `A4`. The response is an `image/png` attachment rendered at 300 DPI.

## Export PDF

```http
GET /tree/export/pdf
```

Parameters match PNG export. The response is an `application/pdf` attachment suitable for printing. It contains:

* Family name and generation date.
* The same nodes, edges, and layout as the generated tree.
* Member and generation statistics.
* Platform footer.

## Validation and Errors

Invalid parameters return HTTP 422 using the standard error envelope. An inaccessible member returns HTTP 403, and an unknown member returns HTTP 404. Internal rendering and export exceptions must not be exposed.

## Rate Limiting

Tree generation uses the authenticated API limiter. Export endpoints receive a stricter limiter because high-resolution rendering is more expensive.
