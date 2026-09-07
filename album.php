<?php
/*
 * DVDdb Album Manager - Pass 2.0
 * PHP 8.x bolt-on for tracking physical DVD binder locations.
 *
 * Requires the movie_location table:
 *   movieid INT PRIMARY KEY
 *   album INT NOT NULL
 *   page INT NOT NULL
 *   sleeve INT NOT NULL
 *   disc_count INT NOT NULL DEFAULT 1
 *   UNIQUE KEY physical_location (album, page, sleeve)
 *
 * Physical album dimensions are stored in album_config. A multi-disc title
 * stores only its first sleeve; following occupied sleeves are inferred.
 */
require("inc/menu.php");
require("inc/controls.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();
$output="";

// Physical location data is global to this DVDdb installation, so only an
// administrator may alter it.
if ($admin==FALSE) {
    header("Location: index.php");
    die();
}

function album_load_config()
{
    $configs=array();
    $result=doquery("select album, pages, sleeves_per_page from album_config order by album");
    if ($result===FALSE) return FALSE;
    while ($row=mysqli_fetch_assoc($result)) {
        $album=intval($row["album"]);
        $configs[$album]=array(
            "album"=>$album,
            "pages"=>max(1,intval($row["pages"])),
            "sleeves_per_page"=>max(1,intval($row["sleeves_per_page"]))
        );
    }
    return $configs;
}

function album_config_for($album, $configs)
{
    $album=intval($album);
    if (isset($configs[$album])) return $configs[$album];
    // Legacy fallback keeps existing databases usable until the album is added
    // to Album config. Navigation does not roll to another album without a
    // configured page count.
    return array("album"=>$album,"pages"=>0,"sleeves_per_page"=>4);
}

function album_sleeves_per_page($album, $configs)
{
    $cfg=album_config_for($album,$configs);
    return max(1,intval($cfg["sleeves_per_page"]));
}

function album_next_location($album, $page, $configs)
{
    $cfg=album_config_for($album,$configs);
    if ($cfg["pages"]>0 && $page >= $cfg["pages"]) {
        $albums=array_keys($configs);
        sort($albums,SORT_NUMERIC);
        foreach ($albums as $candidate) {
            if ($candidate>$album) return array("album"=>$candidate,"page"=>1);
        }
        // This is the last page of the last configured physical album.
        // Do not invent a new album; adding it to album_config will
        // automatically enable rollover to that album.
        return NULL;
    }
    return array("album"=>$album,"page"=>$page+1);
}

function album_previous_location($album, $page, $configs)
{
    if ($page>1) return array("album"=>$album,"page"=>$page-1);
    $albums=array_keys($configs);
    rsort($albums,SORT_NUMERIC);
    foreach ($albums as $candidate) {
        if ($candidate<$album && intval($configs[$candidate]["pages"])>0) {
            return array("album"=>$candidate,"page"=>intval($configs[$candidate]["pages"]));
        }
    }
    return NULL;
}

function album_slot_index($album, $page, $sleeve, $configs)
{
    $spp=album_sleeves_per_page($album,$configs);
    return (($page-1)*$spp)+($sleeve-1);
}

function album_index_location($album, $index, $configs)
{
    $spp=album_sleeves_per_page($album,$configs);
    return array(
        "page"=>intdiv($index,$spp)+1,
        "sleeve"=>($index%$spp)+1
    );
}

function album_movie_label($row)
{
    $year = intval($row["reldate"]);
    $label = intval($row["id"])." | ".$row["title"];
    if ($year > 0) $label .= " (".$year.")";
    return $label;
}

function album_parse_movieid($value)
{
    $value = trim((string)$value);
    if ($value === "") return 0;
    if (preg_match('/^([0-9]+)\s*\|/', $value, $matches)) return intval($matches[1]);
    if (ctype_digit($value)) return intval($value);
    return -1;
}

function album_load_locations()
{
    $locations=array();
    $result=doquery(
        "select ml.movieid, ml.album, ml.page, ml.sleeve, ml.disc_count, m.title, m.reldate " .
        "from movie_location ml inner join movie m on m.id=ml.movieid " .
        "order by ml.album, ml.page, ml.sleeve"
    );
    if ($result===FALSE) return FALSE;
    while ($row=mysqli_fetch_assoc($result)) {
        $row["movieid"]=intval($row["movieid"]);
        $row["album"]=intval($row["album"]);
        $row["page"]=intval($row["page"]);
        $row["sleeve"]=intval($row["sleeve"]);
        $row["disc_count"]=max(1,intval($row["disc_count"]));
        $locations[]=$row;
    }
    return $locations;
}

function album_build_occupancy($locations, $configs)
{
    $occupied=array();
    foreach ($locations as $loc) {
        $album=$loc["album"];
        $start=album_slot_index($album,$loc["page"],$loc["sleeve"],$configs);
        for ($disc=0;$disc<$loc["disc_count"];$disc++) {
            $index=$start+$disc;
            if (!isset($occupied[$album])) $occupied[$album]=array();
            if (!isset($occupied[$album][$index])) $occupied[$album][$index]=array();
            $entry=$loc;
            $entry["disc_number"]=$disc+1;
            $entry["is_start"]=($disc===0);
            $occupied[$album][$index][]=$entry;
        }
    }
    return $occupied;
}

function album_collision_keys($locations, $configs)
{
    $keys=array();
    $slot_seen=array();

    foreach ($locations as $loc) {
        $movieid=intval($loc["movieid"]);
        $album=intval($loc["album"]);
        $page=intval($loc["page"]);
        $sleeve=intval($loc["sleeve"]);
        $disc_count=max(1,intval($loc["disc_count"]));

        if ($album<1 || $page<1 || $sleeve<1 || $sleeve>album_sleeves_per_page($album,$configs)) continue;

        $start=album_slot_index($album,$page,$sleeve,$configs);
        for ($disc=0;$disc<$disc_count;$disc++) {
            $index=$start+$disc;
            $slotkey=$album.":".$index;
            if (isset($slot_seen[$slotkey])) {
                foreach ($slot_seen[$slotkey] as $other) {
                    $a=min($movieid,$other);
                    $b=max($movieid,$other);
                    $keys[$album.":".$index.":".$a.":".$b]=TRUE;
                }
                $slot_seen[$slotkey][]=$movieid;
            } else {
                $slot_seen[$slotkey]=array($movieid);
            }
        }
    }

    return $keys;
}

function album_validate_locations($locations, $movies, $configs, $allowed_collisions=array())
{
    $errors=array();
    $movie_seen=array();
    $slot_seen=array();

    foreach ($locations as $loc) {
        $movieid=intval($loc["movieid"]);
        $album=intval($loc["album"]);
        $page=intval($loc["page"]);
        $sleeve=intval($loc["sleeve"]);
        $disc_count=intval($loc["disc_count"]);

        if (!isset($movies[$movieid])) {
            $errors[]="Movie ID ".$movieid." does not exist.";
            continue;
        }
        if ($album<1 || $page<1 || $sleeve<1 || $sleeve>album_sleeves_per_page($album,$configs)) {
            $errors[]="Invalid physical location for ".htmlspecialchars($movies[$movieid]["title"]).".";
            continue;
        }
        if ($disc_count<1 || $disc_count>20) {
            $errors[]="Disc count for ".htmlspecialchars($movies[$movieid]["title"])." must be between 1 and 20.";
            continue;
        }
        if (isset($movie_seen[$movieid])) {
            $errors[]="Movie ID ".$movieid." is assigned more than once.";
            continue;
        }
        $movie_seen[$movieid]=TRUE;

        $start=album_slot_index($album,$page,$sleeve,$configs);
        for ($disc=0;$disc<$disc_count;$disc++) {
            $index=$start+$disc;
            $key=$album.":".$index;
            if (isset($slot_seen[$key])) {
                $other=$slot_seen[$key];
                $a=min($movieid,$other);
                $b=max($movieid,$other);
                $collision_key=$album.":".$index.":".$a.":".$b;
                if (!isset($allowed_collisions[$collision_key])) {
                    $where=album_index_location($album,$index,$configs);
                    $errors[]="Physical collision in Album ".$album.", Page ".$where["page"].", Sleeve ".$where["sleeve"].
                        " between ".htmlspecialchars($movies[$other]["title"])." and ".htmlspecialchars($movies[$movieid]["title"]).".";
                }
            } else {
                $slot_seen[$key]=$movieid;
            }
        }
    }
    return $errors;
}

$album_configs=album_load_config();
if ($album_configs===FALSE) {
    $output.="<DIV CLASS=\"error\"><B>Album Manager cannot read album_config.</B><BR />".
        "Run upgradedb.php, then add your binders under Album config.<BR />".
        htmlspecialchars(db_error())."</DIV>";
    $content["body"] =& $output;
    dopage($content);
    die();
}

$album=isset($_REQUEST["album"])?max(1,intval($_REQUEST["album"])):1;
$page=isset($_REQUEST["page"])?max(1,intval($_REQUEST["page"])):1;
$source_album=isset($_POST["source_album"])?max(1,intval($_POST["source_album"])):$album;
$source_page=isset($_POST["source_page"])?max(1,intval($_POST["source_page"])):$page;
$media_scope=isset($_REQUEST["media_scope"])?strtolower(trim((string)$_REQUEST["media_scope"])):"dvdr";
if ($media_scope!=="all") $media_scope="dvdr";
$sleeves_per_page=album_sleeves_per_page($album,$album_configs);
$message="";
$errors=array();

// Load every movie for validation, but build the title picker from the selected
// media scope. DVD-R is the default because those are the titles most likely
// to be stored in the physical albums.
$movies=array();
$movie_rows=array();
$result=doquery(
    "select m.id, m.title, m.reldate, m.mediaid, media.name as medianame " .
    "from movie m inner join media on media.id=m.mediaid order by m.title, m.reldate, m.id"
);
while ($row=mysqli_fetch_assoc($result)) {
    $row["id"]=intval($row["id"]);
    $movies[$row["id"]]=$row;
    if ($media_scope==="all" || strcasecmp(trim((string)$row["medianame"]),"DVD-R")===0) {
        $movie_rows[]=$row;
    }
}

$locations=album_load_locations();
if ($locations===FALSE) {
    $output.="<DIV CLASS=\"error\"><B>Album Manager cannot read movie_location.</B><BR />".
        "Make sure the movie_location table has been created in this database.<BR />".
        htmlspecialchars(db_error())."</DIV>";
    $content["body"] =& $output;
    dopage($content);
    die();
}

$occupancy=album_build_occupancy($locations,$album_configs);
$existing_collision_keys=album_collision_keys($locations,$album_configs);

if (isset($_POST["action"]) && $_POST["action"]==="save") {
    $album=$source_album;
    $page=$source_page;
    $sleeves_per_page=album_sleeves_per_page($album,$album_configs);
    // Save always applies to the page that was actually loaded. Album/Page in the
    // navigation form are only for Jump navigation; editing them does not silently
    // move the current page. This prevents accidental page moves during data entry.
    //
    // Individual movie moves are also supported: if a selected movie already has
    // a location elsewhere, its old location is freed automatically. If the target
    // sleeve already held another movie, that displaced movie is moved into the
    // incoming movie's old location (a true swap) unless the displaced movie is
    // explicitly assigned somewhere else on this same page.
    $proposed=array();
    foreach ($locations as $loc) {
        if ($loc["album"]===$source_album && $loc["page"]===$source_page) continue;
        $proposed[]=$loc;
    }

    $source_starts=array();
    foreach ($locations as $loc) {
        if ($loc["album"]===$source_album && $loc["page"]===$source_page) {
            $source_starts[intval($loc["sleeve"])]=$loc;
        }
    }

    $existing_by_movie=array();
    foreach ($locations as $loc) $existing_by_movie[intval($loc["movieid"])]=$loc;

    $submitted=array();
    $submitted_by_sleeve=array();
    $submitted_ids=array();
    $same_page_notes=array();
    for ($sleeve=1;$sleeve<=$sleeves_per_page;$sleeve++) {
        $index=album_slot_index($source_album,$source_page,$sleeve,$album_configs);
        $cell=(isset($occupancy[$source_album][$index]))?$occupancy[$source_album][$index]:array();
        $continuation=FALSE;
        foreach ($cell as $entry) {
            if (!$entry["is_start"] && $entry["page"]!=$source_page) {
                $continuation=TRUE;
                break;
            }
        }
        if ($continuation) continue;

        $movie_value=$_POST["slots"][$sleeve]["movie"] ?? "";
        $movieid=album_parse_movieid($movie_value);
        $disc_count=max(1,intval($_POST["slots"][$sleeve]["disc_count"] ?? 1));

        if ($movieid===-1) {
            $errors[]="Sleeve ".$sleeve.": select a movie from the title suggestions (the value must begin with its movie ID).";
            continue;
        }
        if ($movieid===0) continue;
        if (!isset($movies[$movieid])) {
            $errors[]="Sleeve ".$sleeve.": movie ID ".$movieid." was not found.";
            continue;
        }

        $newloc=array(
            "movieid"=>$movieid,
            "album"=>$album,
            "page"=>$page,
            "sleeve"=>$sleeve,
            "disc_count"=>$disc_count,
            "title"=>$movies[$movieid]["title"],
            "reldate"=>$movies[$movieid]["reldate"]
        );
        $submitted_by_sleeve[$sleeve]=$newloc;
    }

    // A very common physical reorganization is swapping two sleeves on the same
    // page. The browser leaves the movie in its old row when the user selects it
    // in another row, so the raw POST temporarily contains the same movie twice.
    // Interpret that specific pattern as a move/swap instead of rejecting it as
    // a duplicate assignment.
    $movie_occurrences=array();
    foreach ($submitted_by_sleeve as $slot=>$loc) {
        $mid=intval($loc["movieid"]);
        if (!isset($movie_occurrences[$mid])) $movie_occurrences[$mid]=array();
        $movie_occurrences[$mid][]=$slot;
    }
    foreach ($movie_occurrences as $mid=>$slots) {
        if (count($slots)!==2 || !isset($existing_by_movie[$mid])) continue;
        $old=$existing_by_movie[$mid];
        if ($old["album"]!==$source_album || $old["page"]!==$source_page) continue;
        $old_sleeve=intval($old["sleeve"]);
        if (!in_array($old_sleeve,$slots,TRUE)) continue;
        $target_sleeve=($slots[0]===$old_sleeve)?$slots[1]:$slots[0];

        if (isset($source_starts[$target_sleeve])) {
            $displaced=$source_starts[$target_sleeve];
            $displaced_id=intval($displaced["movieid"]);
            if ($displaced_id!==$mid) {
                $swaploc=$displaced;
                $swaploc["album"]=$source_album;
                $swaploc["page"]=$source_page;
                $swaploc["sleeve"]=$old_sleeve;
                $submitted_by_sleeve[$old_sleeve]=$swaploc;
                $same_page_notes[]="Swapped ".htmlspecialchars($movies[$mid]["title"])." with ".htmlspecialchars($movies[$displaced_id]["title"]).".";
            }
        } else {
            // Moving to an empty sleeve: free the movie's previous sleeve.
            unset($submitted_by_sleeve[$old_sleeve]);
            $same_page_notes[]="Moved ".htmlspecialchars($movies[$mid]["title"])." from Sleeve ".$old_sleeve." to Sleeve ".$target_sleeve.".";
        }
    }

    ksort($submitted_by_sleeve);
    $submitted=array_values($submitted_by_sleeve);
    foreach ($submitted as $loc) $submitted_ids[intval($loc["movieid"])]=TRUE;

    // Remove old locations for movies that are being explicitly placed here.
    // This converts selecting an already-located title into a move instead of a
    // duplicate-location error.
    if (count($errors)===0) {
        foreach ($submitted as $newloc) {
            $movieid=intval($newloc["movieid"]);
            $filtered=array();
            foreach ($proposed as $loc) {
                if (intval($loc["movieid"])!==$movieid) $filtered[]=$loc;
            }
            $proposed=$filtered;
        }
    }

    // Build automatic swaps. Example: A1/P5/S3 contains Movie A and the user
    // selects Movie B, which currently lives at A2/P8/S1. Movie B moves to the
    // displayed sleeve and Movie A moves into B's old location. A blank target
    // simply moves B and leaves its old sleeve empty.
    $swapped=array();
    $move_notes=$same_page_notes;
    if (count($errors)===0) {
        foreach ($submitted as $newloc) {
            $movieid=intval($newloc["movieid"]);
            $sleeve=intval($newloc["sleeve"]);
            if (!isset($existing_by_movie[$movieid])) continue;
            $old=$existing_by_movie[$movieid];
            $old_is_source=($old["album"]===$source_album && $old["page"]===$source_page);
            if ($old_is_source) continue;

            $move_notes[]="Moved ".htmlspecialchars($movies[$movieid]["title"])." from A".$old["album"]."/P".$old["page"]."/S".$old["sleeve"].".";

            if (isset($source_starts[$sleeve])) {
                $displaced=$source_starts[$sleeve];
                $displaced_id=intval($displaced["movieid"]);
                if ($displaced_id!==$movieid && !isset($submitted_ids[$displaced_id])) {
                    $swaploc=$displaced;
                    $swaploc["album"]=intval($old["album"]);
                    $swaploc["page"]=intval($old["page"]);
                    $swaploc["sleeve"]=intval($old["sleeve"]);
                    $swapped[]=$swaploc;
                    $proposed[]=$swaploc;
                    $move_notes[]="Swapped ".htmlspecialchars($movies[$displaced_id]["title"])." into A".$old["album"]."/P".$old["page"]."/S".$old["sleeve"].".";
                }
            }
        }
    }

    foreach ($submitted as $newloc) $proposed[]=$newloc;

    if (count($errors)===0) {
        $errors=album_validate_locations($proposed,$movies,$album_configs,$existing_collision_keys);
    }

    if (count($errors)===0) {
        global $sql_vars;
        mysqli_begin_transaction($sql_vars["db"]);
        $ok=TRUE;

        // Remove all starts from the page that was loaded; the submitted rows
        // recreate what should remain there (or at the move destination).
        $ok=$ok && (doquery("delete from movie_location where album=".$source_album." and page=".$source_page)!==FALSE);

        // If an explicitly selected movie already lived elsewhere, remove its old
        // start before inserting the new one. movieid is the table primary key.
        if ($ok) {
            foreach ($submitted as $loc) {
                if (doquery("delete from movie_location where movieid=".intval($loc["movieid"]))===FALSE) {
                    $ok=FALSE;
                    $errors[]="Database error while moving a movie: ".htmlspecialchars(db_error());
                    break;
                }
            }
        }

        // Insert any automatic swap destinations first, then the displayed page.
        if ($ok) {
            foreach (array_merge($swapped,$submitted) as $loc) {
                $query="insert into movie_location (movieid, album, page, sleeve, disc_count) values (".
                    intval($loc["movieid"]).", ".intval($loc["album"]).", ".intval($loc["page"]).", ".
                    intval($loc["sleeve"]).", ".intval($loc["disc_count"]).")";
                if (doquery($query)===FALSE) {
                    $ok=FALSE;
                    $errors[]="Database error while saving: ".htmlspecialchars(db_error());
                    break;
                }
            }
        } else if (count($errors)===0) {
            $errors[]="Database error while preparing this page: ".htmlspecialchars(db_error());
        }

        if ($ok) {
            mysqli_commit($sql_vars["db"]);
            $message="Album ".$album.", Page ".$page." saved.";
            if (count($move_notes)>0) $message.=" ".implode(" ",$move_notes);
            if (isset($_POST["save_next"])) {
                $next=album_next_location($album,$page,$album_configs);
                if ($next!==NULL) {
                    header("Location: album.php?album=".$next["album"]."&page=".$next["page"]."&media_scope=".urlencode($media_scope)."&saved=1");
                    die();
                }
                $message.=" This is the final page of the last configured album.";
            }
            $locations=album_load_locations();
            $occupancy=album_build_occupancy($locations,$album_configs);
        } else {
            mysqli_rollback($sql_vars["db"]);
        }
    }
}

if (isset($_GET["saved"])) $message="Previous page saved.";

// Count progress within the current media scope.
$scope_movie_ids=array();
foreach ($movie_rows as $movie) $scope_movie_ids[intval($movie["id"])]=TRUE;
$located_count=0;
foreach ($locations as $loc) {
    if (isset($scope_movie_ids[intval($loc["movieid"])])) $located_count++;
}
$total_movies=count($movie_rows);
$remaining=max(0,$total_movies-$located_count);
$scope_label=($media_scope==="all")?"all media":"DVD-R";

// Browser-side location metadata is used only to warn before an occupied sleeve
// is replaced. The server remains authoritative and performs the actual move/swap.
$js_location_map=array();
$js_original_slots=array();
foreach ($locations as $loc) {
    $mid=intval($loc["movieid"]);
    $js_location_map[$mid]=array(
        "album"=>intval($loc["album"]),
        "page"=>intval($loc["page"]),
        "sleeve"=>intval($loc["sleeve"]),
        "title"=>(string)$loc["title"]
    );
    if (intval($loc["album"])===$album && intval($loc["page"])===$page) {
        $js_original_slots[intval($loc["sleeve"])]=array(
            "movieid"=>$mid,
            "title"=>(string)$loc["title"]
        );
    }
}

$content["head"]="<STYLE TYPE=\"text/css\">\n".
".albumwrap{max-width:1050px;margin:0 auto;font-family:Arial,Helvetica,sans-serif;}\n".
".albumnav{padding:10px;border:1px solid #888;margin-bottom:12px;background:#f4f4f4;}\n".
".albumgrid{width:100%;border-collapse:collapse;}\n".
".albumgrid th,.albumgrid td{border:1px solid #888;padding:8px;vertical-align:middle;}\n".
".albumgrid th{background:#ddd;}\n".
".albumgrid input[type=text]{width:98%;box-sizing:border-box;}\n".
".albumgrid input[type=number]{width:60px;}\n".
".albumoccupied{background:#eee;font-style:italic;}\n".
".albumreserved{background:#e4e4e4;}\n".
".albumreserved input{background:#ddd;color:#777;}\n".
".albummsg{padding:8px;border:1px solid #588a58;background:#e9f7e9;margin-bottom:10px;}\n".
".albumerr{padding:8px;border:1px solid #a55;background:#fbeaea;margin-bottom:10px;}\n".
".albumhint{font-size:90%;color:#555;}\n".
".albumscope{margin-left:12px;}\n".
"</STYLE>\n".
"<SCRIPT TYPE=\"text/javascript\">\n".
"var albumLocationMap=".json_encode($js_location_map,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).";\n".
"var albumOriginalSlots=".json_encode($js_original_slots,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).";\n".
"var albumLoadedAlbum=".intval($album)."; var albumLoadedPage=".intval($page).";\n".
"function albumRefreshReservations(){\n".
" var reservedThrough=0;\n".
" var reservationStart=0;\n".
" var reservationCount=1;\n".
" var reservationTitle='';\n".
" for(var sleeve=1;sleeve<=".intval($sleeves_per_page).";sleeve++){\n".
"  var row=document.getElementById('album_row_'+sleeve);\n".
"  var movie=document.getElementById('album_movie_'+sleeve);\n".
"  var discs=document.getElementById('album_discs_'+sleeve);\n".
"  if(!row || !movie || !discs) continue;\n".
"  if(sleeve<=reservedThrough){\n".
"   if(movie.disabled===false){ movie.dataset.savedValue=movie.value; discs.dataset.savedValue=discs.value; }\n".
"   var discNumber=(sleeve-reservationStart)+1;\n".
"   movie.value=''; discs.value=''; movie.disabled=true; discs.disabled=true;\n".
"   movie.placeholder='→ Disc '+discNumber+' of '+reservationCount+' — '+reservationTitle;\n".
"   discs.placeholder='occupied'; row.classList.add('albumreserved');\n".
"   continue;\n".
"  }\n".
"  if(movie.disabled){\n".
"   movie.disabled=false; discs.disabled=false;\n".
"   if(movie.dataset.savedValue!==undefined){ movie.value=movie.dataset.savedValue; delete movie.dataset.savedValue; }\n".
"   if(discs.dataset.savedValue!==undefined){ discs.value=discs.dataset.savedValue; delete discs.dataset.savedValue; }\n".
"  }\n".
"  movie.placeholder='Start typing a movie title...'; discs.placeholder=''; row.classList.remove('albumreserved');\n".
"  var count=parseInt(discs.value,10); if(!count || count<1) count=1;\n".
"  if(movie.value.trim()!=='' && count>1){\n".
"   reservationStart=sleeve; reservationCount=count;\n".
"   reservationTitle=movie.value.replace(/^\\s*\\d+\\s*\\|\\s*/, '').replace(/\\s*\\(\\d{4}\\)\\s*$/, '');\n".
"   reservedThrough=Math.max(reservedThrough,sleeve+count-1);\n".
"  }\n".
" }\n".
"}\n".
"function albumMovieId(value){\n".
" var m=String(value||'').match(/^\\s*(\\d+)\\s*\\|/); return m?parseInt(m[1],10):0;\n".
"}\n".
"function albumConfirmOccupiedChanges(){\n".
" for(var sleeve=1;sleeve<=".intval($sleeves_per_page).";sleeve++){\n".
"  var input=document.getElementById('album_movie_'+sleeve); if(!input || input.disabled) continue;\n".
"  var incoming=albumMovieId(input.value); if(!incoming) continue;\n".
"  var original=albumOriginalSlots[String(sleeve)] || albumOriginalSlots[sleeve];\n".
"  if(!original || parseInt(original.movieid,10)===incoming) continue;\n".
"  var incomingLoc=albumLocationMap[String(incoming)] || albumLocationMap[incoming];\n".
"  var incomingTitle=input.value.replace(/^\\s*\\d+\\s*\\|\\s*/, '').replace(/\\s*\\(\\d{4}\\)\\s*$/, '');\n".
"  var existingTitle=original.title || ('Movie '+original.movieid);\n".
"  var question='';\n".
"  if(incomingLoc){\n".
"   question='Sleeve '+sleeve+' currently contains \"'+existingTitle+'\".\\n\\n'+\n".
"    '\"'+incomingTitle+'\" is currently at A'+incomingLoc.album+'/P'+incomingLoc.page+'/S'+incomingLoc.sleeve+'.\\n\\nSwap the two movies?';\n".
"  } else {\n".
"   question='Sleeve '+sleeve+' currently contains \"'+existingTitle+'\".\\n\\nReplace it with \"'+incomingTitle+'\"?\\n\\nThe existing movie will become unassigned.';\n".
"  }\n".
"  if(!window.confirm(question)) return false;\n".
" }\n".
" return true;\n".
"}\n".
"function albumSyncDestination(){\n".
" albumRefreshReservations();\n".
" if(!albumConfirmOccupiedChanges()) return false;\n".
" var a=document.getElementById('nav_album');\n".
" var p=document.getElementById('nav_page');\n".
" var m=document.getElementById('nav_media_scope');\n".
" if(a) document.getElementById('save_album').value=a.value;\n".
" if(p) document.getElementById('save_page').value=p.value;\n".
" if(m) document.getElementById('save_media_scope').value=m.value;\n".
" return true;\n".
"}\n".
"window.addEventListener('load',function(){\n".
" for(var sleeve=1;sleeve<=".intval($sleeves_per_page).";sleeve++){\n".
"  var movie=document.getElementById('album_movie_'+sleeve); var discs=document.getElementById('album_discs_'+sleeve);\n".
"  if(movie) movie.addEventListener('input',albumRefreshReservations);\n".
"  if(discs){ discs.addEventListener('input',albumRefreshReservations); discs.addEventListener('change',albumRefreshReservations); }\n".
" }\n".
" albumRefreshReservations();\n".
"});\n".
"</SCRIPT>\n";

$output.="<DIV CLASS=\"albumwrap\">";
$output.="<H2>Album Manager</H2>";
if (!isset($album_configs[$album])) $output.="<DIV CLASS=\"albumerr\"><B>Album ".intval($album)." is not configured.</B> Using the legacy fallback of 4 sleeves per page. <A HREF=\"tableedit.php\">Configure albums in Table edit</A> to enable correct page rollover and sleeve count.</DIV>";
$output.="<DIV CLASS=\"albumhint\"><B>".htmlspecialchars($scope_label)." catalog progress:</B> <B>".$located_count."</B> of <B>".$total_movies."</B> movies have a starting location; <B>".$remaining."</B> remain. ".
    (($media_scope==="dvdr")?"DVD-R is the default because those titles are most likely to be stored in the albums.":"Showing movies from every media type.")." Configured layout: <B>".$sleeves_per_page." sleeves/page</B>".(isset($album_configs[$album])?", <B>".intval($album_configs[$album]["pages"])." pages</B>.":".")."</DIV><BR />";

if ($message!=="") $output.="<DIV CLASS=\"albummsg\">".htmlspecialchars($message)."</DIV>";
if (count($errors)>0) {
    $output.="<DIV CLASS=\"albumerr\"><B>Nothing was saved:</B><UL>";
    foreach ($errors as $error) $output.="<LI>".$error."</LI>";
    $output.="</UL></DIV>";
}

$output.=form_begin("album.php","GET");
$output.="<DIV CLASS=\"albumnav\"><B>Album:</B> <INPUT TYPE=\"TEXT\" NAME=\"album\" ID=\"nav_album\" SIZE=\"4\" MAXLENGTH=\"5\" VALUE=\"".intval($album)."\">".
    " &nbsp; <B>Page:</B> <INPUT TYPE=\"TEXT\" NAME=\"page\" ID=\"nav_page\" SIZE=\"4\" MAXLENGTH=\"5\" VALUE=\"".intval($page)."\">".
    " <SPAN CLASS=\"albumscope\"><B>Movie choices:</B> <SELECT NAME=\"media_scope\" ID=\"nav_media_scope\">".
    "<OPTION VALUE=\"dvdr\"".(($media_scope==="dvdr")?" SELECTED":"").">DVD-R only</OPTION>".
    "<OPTION VALUE=\"all\"".(($media_scope==="all")?" SELECTED":"").">All media</OPTION>".
    "</SELECT></SPAN> &nbsp; ".submit("Jump")." &nbsp; ";
$previous=album_previous_location($album,$page,$album_configs);
if ($previous!==NULL) $output.="<A HREF=\"album.php?album=".$previous["album"]."&page=".$previous["page"]."&media_scope=".urlencode($media_scope)."\">&laquo; Previous page</A> &nbsp; ";
$next=album_next_location($album,$page,$album_configs);
if ($next!==NULL) {
    $output.="<A HREF=\"album.php?album=".$next["album"]."&page=".$next["page"]."&media_scope=".urlencode($media_scope)."\">Next page &raquo;</A>";
} else {
    $output.="<SPAN CLASS=\"albumhint\">Final configured page</SPAN>";
}
$output.="</DIV>";
$output.=form_end();

$output.=form_begin("album.php","POST","albumsave","","onsubmit=\"return albumSyncDestination();\"");
$output.=input_hidden("action","save");
$output.="<INPUT TYPE=\"HIDDEN\" NAME=\"album\" ID=\"save_album\" VALUE=\"".intval($album)."\">\n";
$output.="<INPUT TYPE=\"HIDDEN\" NAME=\"page\" ID=\"save_page\" VALUE=\"".intval($page)."\">\n";
$output.=input_hidden("source_album",$album);
$output.=input_hidden("source_page",$page);
$output.="<INPUT TYPE=\"HIDDEN\" NAME=\"media_scope\" ID=\"save_media_scope\" VALUE=\"".htmlspecialchars($media_scope,ENT_QUOTES)."\">\n";
$output.="<TABLE CLASS=\"albumgrid\"><TR><TH WIDTH=\"80\">Sleeve</TH><TH>Movie</TH><TH WIDTH=\"100\">Discs</TH></TR>";

for ($sleeve=1;$sleeve<=$sleeves_per_page;$sleeve++) {
    $index=album_slot_index($album,$page,$sleeve,$album_configs);
    $cell=(isset($occupancy[$album][$index]))?$occupancy[$album][$index]:array();
    $start_entry=NULL;
    $continuation_entry=NULL;
    foreach ($cell as $entry) {
        if ($entry["is_start"] && $entry["page"]===$page && $entry["sleeve"]===$sleeve) $start_entry=$entry;
        else if (!$entry["is_start"]) $continuation_entry=$entry;
    }

    $output.="<TR ID=\"album_row_".$sleeve."\">";
    $output.="<TD ALIGN=\"CENTER\"><B>".$sleeve."</B></TD>";

    if ($continuation_entry!==NULL && $start_entry===NULL) {
        $loc=album_index_location($album,$index,$album_configs);
        $output.="<TD CLASS=\"albumoccupied\">&rarr; Disc ".intval($continuation_entry["disc_number"])." of ".intval($continuation_entry["disc_count"]).
            " &mdash; ".htmlspecialchars($continuation_entry["title"]).
            "<BR /><SPAN CLASS=\"albumhint\">Started at Page ".intval($continuation_entry["page"]).", Sleeve ".intval($continuation_entry["sleeve"])."</SPAN></TD>";
        $output.="<TD CLASS=\"albumoccupied\" ALIGN=\"CENTER\">occupied</TD>";
    } else {
        $value="";
        $discs=1;
        if ($start_entry!==NULL) {
            $value=album_movie_label(array("id"=>$start_entry["movieid"],"title"=>$start_entry["title"],"reldate"=>$start_entry["reldate"]));
            $discs=max(1,intval($start_entry["disc_count"]));
        }
        $output.="<TD><INPUT TYPE=\"TEXT\" ID=\"album_movie_".$sleeve."\" NAME=\"slots[".$sleeve."][movie]\" LIST=\"moviechoices\" VALUE=\"".
            htmlspecialchars($value,ENT_QUOTES)."\" AUTOCOMPLETE=\"off\" PLACEHOLDER=\"Start typing a movie title...\"></TD>";
        $output.="<TD ALIGN=\"CENTER\"><INPUT TYPE=\"NUMBER\" ID=\"album_discs_".$sleeve."\" NAME=\"slots[".$sleeve."][disc_count]\" MIN=\"1\" MAX=\"20\" VALUE=\"".$discs."\"></TD>";
    }
    $output.="</TR>";
}
$output.="</TABLE>";

$output.="<DATALIST ID=\"moviechoices\">";
foreach ($movie_rows as $movie) {
    $label=album_movie_label($movie);
    $output.="<OPTION VALUE=\"".htmlspecialchars($label,ENT_QUOTES)."\"></OPTION>";
}
$output.="</DATALIST>";

$output.="<P ALIGN=\"CENTER\">".submit("Save page","save_page")." &nbsp; ".submit("Save & next page","save_next")."</P>";
$output.="<P CLASS=\"albumhint\"><B>Movie choices:</B> DVD-R only is the default for binder entry. Switch to All media if a title in an original DVD case or another media type is actually stored in an album.<BR /><B>Jump:</B> enter an Album/Page and click Jump to load that page. Save always updates the page currently loaded.<BR /><B>Move/swap:</B> select an already-located movie in a different sleeve and Save. If the destination sleeve is occupied, Album Manager asks before swapping the two movies; Cancel leaves everything unchanged. Moving into an empty sleeve frees the old sleeve.<BR /><B>Multi-disc titles:</B> enter the number of physical discs/sleeves used. On the current page, following sleeve(s) are immediately cleared and greyed out so another title cannot be entered there. Reservations also roll to Sleeve 1 on the next page after saving. Leave a movie field blank to clear that starting location.</P>";
$output.=form_end();
$output.="</DIV>";

$content["body"] =& $output;
dopage($content);
?>
