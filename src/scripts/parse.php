#!/usr/bin/php
<?php

$link = mysqli_connect("localhost", "root", "") or die("Could not connect");

mysqli_select_db($link, "fm4") or die("Could not select database");

date_default_timezone_set('Europe/Berlin');

if ($handle = opendir('.')) {

	$compStrOld = "";
	$compStr = "";

	while (false !== ($file = readdir()))
		
		if ($file != "." && $file != ".." && preg_match("/^main/", $file)) {
		
			$compStrOld = $compStr;
			$compStr = "";

			$filename = $file;
			$fd = fopen ($filename, "r");
			$o = fread ($fd, filesize ($filename));
			
			# zeitstempel modeification der track-datei merken
			# (zugriff verändert die mtime!!)
			$filemtime = filemtime($filename);

			# debug
			echo "\n#####################################################################";
			echo "\nAktuelle Zeit: " . date("d.m.Y H:i");
			echo "\nDateiname    : $file";
			echo "\nDateistempel : " . date("r", $filemtime);
			echo "\n#####################################################################";

			echo "\nMatch-Analyse:";
			echo "\n---------------------------------------------------------------------";			
			
			$p="/([0-9]{2}:[0-9]{2}): <b>([^<]*)<\/b> \| <i>([^<]*)<\/i>/i";

			preg_match_all($p, $o, $matches);

			#var_dump($matches);
						
			for ($i=0; $i< count($matches[0]); $i++)
			{
				echo "\n" . ($i+1) . ") matched: ".$matches[0][$i];
				echo "\n 1: ".$matches[1][$i]."  ". bin2hex($matches[1][$i]);
				echo "\n 2: ".$matches[2][$i]."  ". bin2hex($matches[2][$i]);
				echo "\n 3: ".$matches[3][$i]."  ". bin2hex($matches[3][$i]);

				$compStr .= $matches[1][$i].$matches[2][$i].$matches[3][$i];
			}
			
			if ($compStr == $compStrOld)
			{
				echo "\n---------------------------------------------------------------------";
				echo "\n$compStr ==>  File gleicht Vorgängerin.";
				echo "\n---------------------------------------------------------------------";
			}
			else
			{
				echo "\n---------------------------------------------------------------------";
				echo "\nPlaytime-Analyse:";
				echo "\n---------------------------------------------------------------------";
				
				for ($i=0; $i< count($matches[0]); $i++)
				{
					$spielzeit = $matches[1][$i];
					$tageszeit = date("H:i");
					
					if ($tageszeit < $spielzeit)
					{
					    # tagesüberlauf nach mitternacht 
					    $spieldatum = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y")));
					}
					else
					{
					    $spieldatum = date("Y-m-d");
					}
					
					echo "\n" . ($i+1) . ") Spielzeit: $spielzeit, Tageszeit: $tageszeit, Spieldatum: $spieldatum.";
					
					$dateTimeStr = $spieldatum;
					$dateTimeStr .= " ";
					$dateTimeStr .= $spielzeit . ":00";

					$query = "select id from playtime where zeit='$dateTimeStr'";
					$result = mysqli_query($link, $query) or die("Query $query failed. " . mysqli_error($link));
					$num_rows = mysqli_num_rows($result);

					if ($num_rows != 0)
					{
						echo " --> Alte Playtime: $dateTimeStr.";
						#$erg = mysql_fetch_array($result);
						#var_dump($erg);
					}
					else
					{
						#
						# spielzeit wird eingetragen
						#

						$query = "insert into playtime set zeit='$dateTimeStr', track=null";
						$result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));

						# neue id zurückholen
						$query = "select id from playtime where zeit='$dateTimeStr'";
						$result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));

						$line = mysqli_fetch_array($result, MYSQLI_ASSOC);
						$id_playtime = $line["id"];

						echo " --> Neue Playtime: $dateTimeStr";
						echo "\n $dateTimeStr / $id_playtime: NEU id_playtime: $id_playtime";

						#
						# track suchen
						#
						
						$title = $matches[2][$i];
						$interpret = $matches[3][$i];

						echo "\n $dateTimeStr / $id_playtime: TRACK ANALYSE: Title (match-2):|>|$title|<|, Interpret (match-3):|>|$interpret|<|";						
						
						$firstchar = substr($title, 0, 1);
						
						# wenn erster buchstabe ein leerzeichen ist, verdreht FM3 title und artist
						$switch = 0;
						if ($firstchar == ' ')
						{
							$title = $matches[3][$i];
							$interpret = $matches[2][$i];
							
							echo "\n $dateTimeStr / $id_playtime SWITCH: Title (match-3):|>|$title|<|, Interpret (match-2):|>|$interpret|<|";
							
							$switch = 1;						
						}

						#
						# escapen bzw. strippen
						#
						$title = mysqli_escape_string ($link, html_entity_decode ( trim ( $title )));
						$interpret = mysqli_escape_string ($link, html_entity_decode ( trim ( $interpret )));
						
						echo "\n $dateTimeStr / $id_playtime: STRIPPED: Title:|>|$title|<|, Interpret:|>|$interpret|<|";					

						$query = "select id from track where title='$title' and interpret='$interpret'";
						$result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));
						$num_rows = mysqli_num_rows($result);

						if ($num_rows != 0)
						{
							#
							# alter track zu playtime zuordnung
							#
							$line = mysqli_fetch_array($result, MYSQLI_ASSOC);
							$id_track = $line["id"];

							$query = "insert into track_playtime set id_track=$id_track, id_playtime=$id_playtime";
							$result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));
						
							$status = 'Bekannter';
						}
						else
						{
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
						$query = "update playtime set track=$id_track, count=$count, switch=$switch where id=$id_playtime";
						$result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));
						
						$query = "update track set count=$count, lastrun='$dateTimeStr', switch=$switch where id=$id_track";
						$result = mysqli_query($link, $query) or die("\nQuery $query failed. " . mysqli_error($link));
												
						echo "\n $dateTimeStr / $id_playtime: $status Track: $id_track Title:|>|$title|<|, Interpret:|>|$interpret|<|, Count:$count";
					}
				}
			}
		}

	echo "\n---------------------------------------------------------------------";
	echo "\nFinished.";
	echo "\n---------------------------------------------------------------------";
	
	mysqli_close($link);
	closedir($handle);
}
?>
