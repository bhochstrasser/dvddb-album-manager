<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";
$rows="";
$cells="";

if ($admin==FALSE) {
        header("Location: index.php");
        die();
}

$tables=array();
$tables[]=array("name" => "genre", "key" => "id", "desc" => "name", "usedin" => "movie", "fkey" => "genreid");
$tables[]=array("name" => "media", "key" => "id", "desc" => "name", "usedin" => "movie", "fkey" => "mediaid");
$tables[]=array("name" => "region", "key" => "code", "desc" => "name", "usedin" => "user", "fkey" => "regioncode");
$tables[]=array("name" => "country", "key" => "code", "desc" => "name", "usedin" => "user", "fkey" => "countrycode");
// 2026 PHP 8.x fork: physical binder configuration. This remains in the
// original generic Table edit admin instead of adding a one-off config page.
$tables[]=array(
    "name" => "album_config",
    "key" => "album",
    "desc" => "pages",
    "usedin" => "movie_location",
    "fkey" => "album",
    "type" => "album_config"
);

$action=$_GET["action"] ?? "";

if (!saneempty($action)) {
    // Get the table info.
    $requested_table=$_GET["table"] ?? ($_POST["table"] ?? "");
    $data=NULL;
    foreach ($tables as $table) {
        if ($table["name"]==$requested_table) {
            $data=$table;
            break;
        }
    }
    if ($data===NULL) {
        $output.="Unknown table.";
    } else {
        $special=(($data["type"] ?? "")==="album_config");

        switch($action) {
            case "edit":
                $output.=form_begin("tableedit.php","GET");
                $output.=input_hidden("action","commit");
                $output.=input_hidden("table",$data["name"]);
                $id=$_GET["id"] ?? "0";

                if ($special) {
                    $album="";
                    $pages="";
                    $sleeves=4;
                    if ($id!="0") {
                        $result=mysqli_fetch_assoc(doquery("select album, pages, sleeves_per_page from album_config where album=".intval($id)));
                        if ($result) {
                            $album=intval($result["album"]);
                            $pages=intval($result["pages"]);
                            $sleeves=intval($result["sleeves_per_page"]);
                        }
                        $output.=input_hidden("iu","u");
                        $output.=input_hidden("id",$album);
                        $output.="<B>Album:</B> ".intval($album)."<BR />\n";
                    } else {
                        $output.=input_hidden("iu","i");
                        $output.="<B>Album number:</B><BR />\n".input_text("id",6,6,$album)."<BR />\n";
                    }
                    $output.="<B>Number of pages:</B><BR />\n".input_text("pages",6,6,$pages)."<BR />\n";
                    $output.="<B>Sleeves per page:</B><BR />\n".input_text("sleeves_per_page",6,6,$sleeves)."<BR />\n";
                } else {
                    $value="";
                    if ($id!="0") {
                        $result=mysqli_fetch_row(doquery("select ".$data["desc"]." from ".$data["name"]." where ".$data["key"]." = \"".greatescape($id)."\""));
                        $value=$result[0];
                        $output.=input_hidden("iu","u");
                    } else {
                        $output.=input_hidden("iu","i");
                    }

                    $output.="Edit ".$data["name"].":<BR />\n".input_text("desc",30,64,$value)."<BR />\n";

                    if ($data["name"]!="genre"&&$data["name"]!="media"&&$id=="0") {
                        $output.="2 digit ".$data["name"]." code:<BR />\n".input_text("id",2,4)."<BR />\n";
                    } else {
                        $output.=input_hidden("id",$id);
                    }
                }

                $output.=submit("Save");
                $output.=form_end();
            break;

            case "delete":
                $id=$_GET["id"] ?? "0";
                $result=mysqli_fetch_row(doquery("select count(*) from ".$data["usedin"]." where ".$data["fkey"]." = \"".greatescape($id)."\""));
                if ($result[0]!=0) {
                    $output.="This ".(($special)?"album configuration":$data["name"])." is in use and cannot be deleted until records using this value have been removed\n";
                } else {
                    doquery("delete from ".$data["name"]." where ".$data["key"]." = \"".greatescape($id)."\"");
                    header("Location: tableedit.php");
                    die();
                }
            break;

            case "commit":
                $iu=$_GET["iu"] ?? "";
                if ($special) {
                    $album=intval($_GET["id"] ?? 0);
                    $pages=intval($_GET["pages"] ?? 0);
                    $sleeves=intval($_GET["sleeves_per_page"] ?? 0);
                    if ($album<1) $output.="Album number must be 1 or greater.<BR />\n";
                    if ($pages<1) $output.="Number of pages must be 1 or greater.<BR />\n";
                    if ($sleeves<1) $output.="Sleeves per page must be 1 or greater.<BR />\n";

                    if (saneempty($output)) {
                        if ($iu=="i") {
                            $result=doquery("select album from album_config where album=".$album);
                            if (mysqli_num_rows($result)!=0) {
                                $output.="Album ".$album." is already configured. Edit the existing row instead.<BR />\n";
                            } else {
                                $query="insert into album_config (album,pages,sleeves_per_page) values (".$album.",".$pages.",".$sleeves.")";
                            }
                        } else {
                            $query="update album_config set pages=".$pages.", sleeves_per_page=".$sleeves." where album=".$album;
                        }
                    }
                } else if ($iu=="i") {
                    if ($data["name"]=="genre"||$data["name"]=="media") {
                        $query="insert ".$data["name"]." values (NULL, \"".greatescape($_GET["desc"] ?? "")."\")";
                    } else {
                        $id=$_GET["id"] ?? "";
                        $result=doquery("select * from ".$data["name"]." where ".$data["key"]." = \"".greatescape($id)."\"");
                        if (mysqli_num_rows($result)!=0) {
                            $output.=greatescape($id)." is already in use. Pick another code\n";
                        } else {
                            $query="insert ".$data["name"]." values (\"".greatescape($id)."\",\"".greatescape($_GET["desc"] ?? "")."\")";
                        }
                    }
                } else {
                    if ($data["name"]=="genre"||$data["name"]=="media") {
                        $query="update ".$data["name"]." set name = \"".greatescape($_GET["desc"] ?? "")."\" where id=\"".intval($_GET["id"] ?? 0)."\"";
                    } else {
                        $query="update ".$data["name"]." set ".$data["desc"]." = \"".greatescape($_GET["desc"] ?? "")."\" where ".$data["key"]." = \"".greatescape($_GET["id"] ?? "")."\"";
                    }
                }

                if (saneempty($output)) {
                    doquery($query);
                    header("Location: tableedit.php");
                    die();
                }
            break;
        }
    }
} else {
    foreach ($tables as $table) {
        $special=(($table["type"] ?? "")==="album_config");
        if ($special) {
            $result=doquery("select album, pages, sleeves_per_page from album_config order by album");
            $rows=tr(td("Album","","tablehead").td("Pages","","tablehead").td("Sleeves/page","","tablehead").td("Action","","tablehead"));
            $c=0;
            while ($row=mysqli_fetch_assoc($result)) {
                if ($c++%2==0) $class="tablecell0"; else $class="tablecell1";
                $id=intval($row["album"]);
                $actions="<A HREF=\"tableedit.php?action=edit&table=album_config&id=".$id."\">Edit</A> | <A HREF=\"tableedit.php?action=delete&table=album_config&id=".$id."\">Delete</A>";
                $rows.=tr(td($id,"",$class).td(intval($row["pages"]),"",$class).td(intval($row["sleeves_per_page"]),"",$class).td($actions,"",$class));
            }
        } else {
            $result=doquery("select ".$table["key"].", ".$table["desc"]." from ".$table["name"]." order by ".$table["desc"]);
            $rows=tr(td(ucwords($table["key"]),"","tablehead").td(ucwords($table["name"]),"","tablehead").td("Action","","tablehead"));
            $c=0;
            while ($row=mysqli_fetch_row($result)) {
                if ($c++%2==0) $class="tablecell0"; else $class="tablecell1";
                $rows.=tr(td($row[0],"",$class).td($row[1],"",$class).td("<A HREF=\"tableedit.php?action=edit&table=".$table["name"]."&id=".$row[0]."\">Edit</A> | <A HREF=\"tableedit.php?action=delete&table=".$table["name"]."&id=".$row[0]."\">Delete</A>","",$class));
            }
        }

        $form=form_begin("tableedit.php","GET");
        $form.=input_hidden("table",$table["name"]);
        $form.=input_hidden("action","edit");
        $form.=input_hidden("id",0);
        $form.=submit("New ".(($special)?"album":$table["name"]));
        $form.=form_end();

        $label=($special)?"<B>Album configuration</B><BR /><SPAN STYLE=\"font-size:90%\">Physical binder dimensions used by Album Manager.</SPAN><BR />":"";
        $cells.=td($label.$form.table($rows,0,1,0,"","configtable"),"","","CENTER","TOP");
    }

    $output.=table(tr($cells),0,0,0,"100%");
}

$content["body"] =& $output;
dopage($content);

?>
