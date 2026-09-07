## PHP 8 compatibility-only edition

If you want to run the original DVDdb v0.6 application on PHP 8 without the Album
Manager features or database schema changes, use the
[DVDdb PHP 8 Compatibility Edition](https://github.com/bhochstrasser/dvddb-php8).

# DVDdb PHP 8.x compatibility fork

This is a compatibility-first modernization of the supplied DVDdb codebase.
The goal of this pass is to preserve the original application, database schema,
UI, and behavior while making the PHP code capable of running on a modern PHP 8.x
stack.

## What changed

- Converted legacy short PHP opening tags (`<?`) to `<?php`.
- Replaced the removed PHP `mysql_*` extension with `mysqli`.
- Preserved the original `doquery()` database abstraction.
- Added a small `db_result()` helper to replace the removed `mysql_result()`.
- Added helpers for insert ID, database errors, error numbers, and server version.
- Fixed legacy unquoted array keys that are fatal on modern PHP.
- Removed the obsolete `magic_quotes_gpc` assumptions.
- Fixed menu arrays that PHP 8 no longer allows to be initialized as strings.
- Added conservative defaults for several request variables to reduce PHP 8
  undefined-key warnings.
- Verified that every PHP file passes syntax checking with PHP 8.4.23.

## Before uploading

Edit `inc/db.php` and replace the `xxx` placeholders with credentials for a TEST
database restored from your SQL backup. Do not point this first-pass fork at the
original production database yet.

The database configuration is near the top of `inc/db.php`:

    "host" => "localhost",
    "username" => "xxx",
    "password" => "xxx",
    "dbname" => "xxx",

## Recommended first test

1. Restore the SQL backup into a new test database.
2. Upload this fork into a separate directory or test subdomain.
3. Configure the test DB credentials in `inc/db.php`.
4. Select a current PHP 8.x version with the `mysqli` extension enabled.
5. Visit `index.php`.
6. If an error appears, save the exact message and the relevant PHP error-log entry.
7. Test login, movie list, search, movie view, add/edit movie, loans, stats, settings,
   and admin pages before using the original database.

## Intentionally NOT modernized yet

This pass does not redesign DVDdb or change its authentication/data model. The old
MD5 password storage, hand-built SQL strings, legacy HTML, and other security/design
issues remain so that compatibility can be established first. Those should be a
second phase after the application runs correctly against the test database.

## Testing performed here

Static PHP syntax validation only; there is no copy of your MySQL database in this
environment, so runtime SQL/schema behavior still needs to be tested on your host.

## 2026 modernization additions

After the initial PHP 8 compatibility pass, the fork gained several functional
updates while retaining the original DVDdb look and administration model.

### Physical DVD album locations

A new `movie_location` table stores the starting physical location of a movie in
an album plus the number of physical discs/sleeves it occupies:

```sql
CREATE TABLE movie_location (
    movieid INT(11) NOT NULL,
    album INT(11) NOT NULL,
    page INT(11) NOT NULL,
    sleeve INT(11) NOT NULL,
    disc_count INT(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (movieid),
    UNIQUE KEY physical_location (album, page, sleeve)
);
```

`album.php` provides an Album Manager for entering and maintaining these
locations. It supports DVD-R-only or all-media selection, direct Album/Page
navigation, previous/next page navigation, move/swap confirmation when a movie
is moved into an occupied starting sleeve, and multi-disc titles that reserve
following physical sleeves. Reservations can roll from the last sleeve of one
page to the first sleeve of the next page.

The movie list can display storage either as a compact `Location (A/P/S)` column
or as separate Album, Page, and Sleeve columns. These choices are available in
both Column admin defaults and individual user Settings.

### Configurable physical album dimensions

Album geometry is no longer assumed by the application. A new `album_config`
table records the number of pages and sleeves per page for each physical album:

```sql
CREATE TABLE album_config (
    album INT(11) NOT NULL,
    pages INT(11) NOT NULL,
    sleeves_per_page INT(11) NOT NULL DEFAULT 4,
    PRIMARY KEY (album)
);
```

Album configuration is maintained from the existing **Table edit** admin page,
alongside Genre, Media, Region, and Country. Album configurations that are in
use by `movie_location` cannot be deleted.

Album Manager uses this table for page size, multi-disc rollover, Previous/Next
navigation, and physical-collision validation. At the last page of the last
configured album, navigation displays **Final configured page** rather than
inventing another album. Adding another album in Table edit automatically makes
rollover to that album available.

If a manually entered album number is not configured, Album Manager retains a
legacy fallback of four sleeves per page and displays a warning that correct
page rollover requires configuration.

### Upgrade behavior

`upgradedb.php` creates both new tables with `CREATE TABLE IF NOT EXISTS`, so the
migration is safe to re-run. It also seeds Album 1 as 32 pages / 4 sleeves per
page only when that row does not already exist; the value is ordinary editable
configuration data and can be changed or deleted from Table edit.

### Album Manager validation and cleanup behavior

The Album Manager validates proposed locations before writing them. Existing
legacy physical collisions are tolerated while unrelated pages are corrected,
rather than blocking every save in the collection. New collisions introduced by
a save are rejected. Changing an incorrect multi-disc count back to one releases
its reserved sleeve immediately, revealing any movie whose stored starting
location occupies that sleeve.

## Current schema additions at a glance

| Table | Purpose | Key fields |
| --- | --- | --- |
| `movie_location` | Starting physical binder location and disc count | `movieid`, `album`, `page`, `sleeve`, `disc_count` |
| `album_config` | Physical dimensions of each binder | `album`, `pages`, `sleeves_per_page` |

## Changelog — September 2026

- Added `movie_location` schema and Album Manager.
- Added multi-disc sleeve reservations, including page-to-page rollover.
- Added move/swap confirmation for occupied destination sleeves.
- Added physical-collision validation and legacy-collision cleanup behavior.
- Added compact Location (A/P/S) and separate Album/Page/Sleeve movie-list columns.
- Added the new location columns to Settings and Column admin.
- Added `album_config` schema for database-driven album dimensions.
- Integrated Album configuration into the existing Table edit administration page.
- Made Previous/Next and Save & next page honor each configured album's actual page count.
- Made the final page of the final configured album stop at **Final configured page** instead of navigating into an unconfigured album.
- Kept upgrade migrations idempotent so `upgradedb.php` can be safely re-run.
