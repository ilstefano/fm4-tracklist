#!/usr/bin/php
<?php
$link = mysqli_connect("localhost", "root", "") or die("Could not connect");

mysqli_select_db($link, "fm4") or die("Could not select database");

date_default_timezone_set('Europe/Berlin');

$json = file_get_contents('fm4.json');
$obj = json_decode($json);
$items = $obj[0]->items;


echo "\n#####################################################################";
echo "\nAktuelle Zeit: " . date("d.m.Y H:i");
echo "\n#####################################################################";


echo "\n---------------------------------------------------------------------";
echo "\nPlaytime-Analyse:";
echo "\n---------------------------------------------------------------------";

$i = 0;


foreach ($items as $item) {
    $i++;

    if ($item->type == 'M') {

        echo "\n$item->id, $item->startISO, $item->title, $item->interpreter";

        $spielid = $item->id;

        $query = "select id from playtime where idfm4=$spielid";
        $result = mysqli_query($link, $query) or die("Query $query failed. " . mysqli_error($link));
        $num_rows = mysqli_num_rows($result);

        $dateTimeStr = date('Y-m-d G:i:s', strtotime($item->startISO));

        if ($num_rows != 0) {
            echo " --> Alte Playtime: $dateTimeStr";
        } else {
            #
            # spielzeit wird eingetragen
            #

            $query = "insert into playtime set zeit='$dateTimeStr', idfm4=$spielid, track=null";
            $result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));

            # neue id zurückholen
            $query = "select id from playtime where idfm4=$spielid";
            $result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));

            $line = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $id_playtime = $line["id"];

            echo " --> Neue Playtime: $spielid";

            #
            # track suchen
            #

            $title = $item->title;
            $interpret = $item->interpreter;

            echo "\n $spielid: TRACK ANALYSE: Title (match-2):|>|$title|<|, Interpret (match-3):|>|$interpret|<|";

            #
            # escapen bzw. strippen
            #
            $title = mysqli_escape_string($link, html_entity_decode(trim($title)));
            $interpret = mysqli_escape_string($link, html_entity_decode(trim($interpret)));

            echo "\n $spielid: STRIPPED: Title:|>|$title|<|, Interpret:|>|$interpret|<|";

            $query = "select id from track where title='$title' and interpret='$interpret'";
            $result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));
            $num_rows = mysqli_num_rows($result);

            if ($num_rows != 0) {
                #
                # alter track zu playtime zuordnung
                #
                $line = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $id_track = $line["id"];

                $query = "insert into track_playtime set id_track=$id_track, id_playtime=$id_playtime";
                $result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));

                $status = 'Bekannter';
            } else {
                #
                # track wird eingetragen
                #

                $query = "insert into track set title='$title', interpret='$interpret', firstrun='$dateTimeStr'";
                $result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));

                # neue id zurückholen
                $query = "select id from track where title='$title' and interpret='$interpret'";
                $result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));

                #
                # neuer track zu playtime zuordnung
                #
                $line = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $id_track = $line["id"];

                $query = "insert into track_playtime set id_track=$id_track, id_playtime=$id_playtime";
                $result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));

                $status = 'Neuer';
            }


            # Addendum, 03./04./05.01.2015:
            # bisherige anzahl an spielzeiten feststellen
            $query = "select count(*) as count from track_playtime where id_track=$id_track";
            $result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));
            $line = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $count = $line['count'];

            # - von playtime direkt nach track joinen
            # - in playtimne die aktuellen counts des tracks eintragen
            $query = "update playtime set track=$id_track, count=$count where id=$id_playtime";
            $result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));

            $query = "update track set count=$count, lastrun='$dateTimeStr' where id=$id_track";
            $result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));

            echo "\n $dateTimeStr / $id_playtime: $status Track: $id_track Title:|>|$title|<|, Interpret:|>|$interpret|<|, Count:$count";
        }
    }
}



echo "\n---------------------------------------------------------------------";
echo "\nFinished.";
echo "\n---------------------------------------------------------------------";

mysqli_close($link);

?>
