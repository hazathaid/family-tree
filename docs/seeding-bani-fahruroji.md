# Bani Fahruroji Seeder

`BaniFahrurojiSeeder` imports the names and base relationships transcribed from
`family tree Fahruroji.drawio`. It creates 122 family-member records. Dates,
places, living status, and photos were not imported because the diagram does not
provide reliable structured values for those fields.

Only the supported base relationships are persisted: `father`, `mother`,
`husband`, and `wife`. Derived Indonesian kinship remains the responsibility of
the Relationship Engine.

Five flat branches are created from the children of Fahrurozi and Khodijah:
Didin Muhyiddin, Uwesulkorni, Pipih Sopiah, Amir Sidik, and Dadang Amir Muslim.
Each branch contains its root, the root's spouse, and all known descendants and
their spouses.

The current database assigns at most one branch to a member and does not support
nested branches. For example, Muhammad Najib Al Fatih belongs to `Cabang Pipih
Sopiah`; his descent through Irwan Firmansyah remains represented by the base
parent relationships rather than an additional branch assignment.

The source owner confirmed that `Nuzha Athalla Muadz` is the child of Adam
Ibrahim Alfath and Muna ilfa Salsabila. `Kashimah Adawiyah` is the child of
Yayan Supriatna and Imas Nur Asiah and currently has no spouse.

## Production deployment

Create and verify the intended owner account through the application first.
Then configure its email on the deployment environment:

```dotenv
BANI_FAHRUROJI_OWNER_EMAIL=owner@example.com
```

Refresh cached configuration and run the named seeder:

```bash
php artisan config:clear
php artisan db:seed --class=Database\\Seeders\\BaniFahrurojiSeeder --force
```

The seeder does not create credentials or use a default password. It stops when
the configured owner is missing or inactive. UUIDs are deterministic and
soft-deleted seed records are restored, so the command can safely be run again.

The seeder is intentionally not called from `DatabaseSeeder`; production data is
only imported through the explicit command above.
