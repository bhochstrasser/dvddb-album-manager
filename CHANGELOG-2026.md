# DVDdb 2026 Modernization Changelog

This document records the modernization and feature work performed on
DVDdb in 2026 after the initial PHP 8.x compatibility pass.

The intent of this work was to preserve the character and basic workflow
of the original DVDdb application while making it useful again on a
modern PHP/MySQL host and extending it to manage the physical DVD-R
album collection.

## PHP 8.x compatibility foundation

The project was first brought forward from its legacy PHP implementation
so it could run on PHP 8.x.

Major compatibility work included:

-   Converted legacy short PHP opening tags (`<?`) to `<?php`.
-   Replaced the removed `mysql_*` extension with `mysqli`.
-   Preserved the original `doquery()` database abstraction where
    practical.
-   Added compatibility helpers for functionality removed from modern
    PHP, including the old `mysql_result()` behavior.
-   Fixed unquoted array keys that are fatal under PHP 8.
-   Removed obsolete `magic_quotes_gpc` assumptions.
-   Corrected legacy array initialization and undefined-request-variable
    issues.
-   Performed PHP 8 syntax validation across the application.
-   Preserved the original DVDdb look, navigation, and general workflow
    rather than redesigning the application.

See `PHP8-README.md` for details of the original compatibility pass.

## General application repairs

After the initial PHP 8 conversion, additional runtime testing was
performed against the restored DVDdb database and problems in individual
pages were corrected as they were discovered.

This included fixes to legacy pages, settings/preferences behavior,
themes, and other PHP 8 runtime issues that were not detectable through
syntax checking alone.

## Physical movie-location support

DVDdb was extended to track where physical discs are stored.

A new `movie_location` data structure was added to associate a movie
with its physical starting location:

-   Album
-   Page
-   Sleeve
-   Disc count

This replaces the need to encode physical binder locations indirectly in
movie data and allows location information to be managed independently
of the movie record itself.

The existing collection data was reconciled and imported into the new
location structure.

## Movie-list location display

Physical storage information can now be displayed in the movie list.

Users can choose between:

-   A compact Album/Page/Sleeve location display.
-   Separate Album, Page, and Sleeve columns.

The location columns participate in the existing DVDdb preference system
so users can choose whether they are displayed.

Default column visibility can also be configured by the administrator.

## Album Manager

A new `album.php` Album Manager was added for maintaining the physical
DVD binders.

The Album Manager provides a page-oriented view matching the physical
organization of the collection.

Features include:

-   Direct Album/Page navigation.
-   Previous-page and next-page navigation.
-   Movie selection by title.
-   Movie ID shown with the selected title for unambiguous
    identification.
-   DVD-R-only movie choices by default.
-   An option to include all media types when necessary.
-   Disc-count editing.
-   Save Page and Save & Next Page operations.
-   Progress reporting for cataloged DVD-R titles.

The interface was intentionally kept visually consistent with the
original DVDdb application.

## Move and swap support

Movies can be reorganized directly through Album Manager.

When an already-located movie is selected for a different sleeve, Album
Manager can move it to the new physical location.

If the destination is already occupied, the application can swap the two
movies rather than silently overwriting one of the locations.

Moving a movie into an empty sleeve releases its previous location.

These checks are intended to prevent accidental corruption of the
physical-location map while reorganizing the binders.

## Multi-disc title support

`movie_location` includes a disc count so DVDdb can represent titles
that occupy more than one physical sleeve.

For a multi-disc title:

-   The movie is stored at its starting Album/Page/Sleeve.
-   Subsequent physical sleeves are treated as reserved by that title.
-   Reserved sleeves cannot independently accept another movie.
-   Album Manager identifies the continuation disc and the title that
    owns the space.
-   Multi-disc reservations can continue from Sleeve 4 of one page to
    Sleeve 1 of the next page.
-   Reducing a title's disc count releases the previously reserved
    following sleeve(s).

This makes the logical database location correspond to the actual
physical space occupied in the binder.

## Multi-disc collision protection

Album Manager validates physical occupancy before saving location
changes.

A multi-disc title cannot reserve a following sleeve that is genuinely
occupied by another movie. Potential collisions are reported and the
save is rejected instead of silently overwriting location data.

The collision logic was refined during testing so that existing valid
locations are distinguished from actual physical conflicts.

The physical collection was subsequently checked against the database,
incorrectly marked two-disc titles were corrected, and the multi-disc
collision audit returned zero remaining conflicts.

## Album configuration

Binder dimensions are no longer hard-coded.

An album configuration table was added so each physical album can
define:

-   Album number
-   Number of pages
-   Number of sleeves per page

Album configuration is maintained through the existing `tableedit.php`
administration page alongside DVDdb's other lookup/configuration tables.

This allows binders of different sizes to be represented correctly
without changing PHP source code.

The physically verified 2026 binder configuration is:

    Album   Pages   Sleeves/Page
  ------- ------- --------------
        1      32              4
        2      30              4
        3      32              4
        4      32              4
        5      52              4

## Configuration-aware navigation

Album Manager now uses the album configuration when navigating.

At the final page of an album, **Next page** rolls over to Page 1 of the
next configured album rather than continuing to a nonexistent page.

Previous-page navigation likewise respects album boundaries.

At the final page of the final configured album, the interface displays
**Final configured page** instead of offering navigation into an
unconfigured album.

If an unconfigured album is entered manually, Album Manager warns that
the album is not configured and falls back to the legacy
four-sleeves-per-page behavior.

## Data migration and verification

The restored DVDdb collection contained 1,158 movie records.

Physical-location data was imported into `movie_location`, including
legacy locations that needed to remain represented during migration.

The imported location data was then exercised through Album Manager and
compared with the physical binders.

This process exposed several historical data-entry inconsistencies,
particularly titles marked as two-disc sets that physically occupied
only one sleeve. Correcting those records automatically released the
following sleeve and restored the legitimate movie stored there.

The final multi-disc collision query returned an empty result set after
the physical discrepancies were resolved.

## Database upgrade support

`upgradedb.php` was extended as needed to create/support the new
database structures used by the modernization.

The upgrade process was tested on the restored database and completed
without reported errors.

New installations or upgrades should run the supplied database upgrade
process rather than manually assuming the new tables/columns already
exist.

## Compatibility philosophy

This remains a modernization of DVDdb rather than a rewrite.

Where possible, the project retains:

-   The original visual style.
-   Existing navigation.
-   Existing movie and user data.
-   Existing preferences and administrative concepts.
-   The original application's straightforward server-rendered PHP
    design.

The new album-management functionality was added around the existing
application instead of replacing its core behavior.

## Known legacy considerations

The modernization does not attempt to redesign every aspect of the
original application.

Legacy design choices may remain, including older
authentication/security concepts, hand-built SQL in portions of the
application, and legacy HTML/CSS conventions.

The primary goals of the 2026 work were PHP 8 compatibility,
preservation of the existing DVDdb installation and data, and practical
management of the physical DVD collection.

## Credits

DVDdb is the original work of James Gurney and was distributed under the
GNU General Public License (GPL).

The 2026 work is a compatibility and functionality modernization of that
original project. The original authorship and GPL licensing should be
preserved with any redistribution.

Modernization, restoration, testing, physical catalog verification, and
feature direction were performed by Bryan Hochstrasser with development
assistance from ChatGPT/OpenAI.

## 2026 status

At the completion of this pass:

-   DVDdb is running on the modern PHP 8 environment used for the
    restored site.
-   The original movie database is operational.
-   Physical Album/Page/Sleeve locations are represented in the
    database.
-   Multi-disc titles reserve the correct physical binder space.
-   Album dimensions are configurable rather than hard-coded.
-   Navigation follows the actual configured binder dimensions.
-   The physical binders have been checked against the configuration.
-   Known multi-disc physical collisions have been resolved.

DVDdb lives on.
