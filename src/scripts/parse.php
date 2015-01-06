#!/usr/local/bin/php
<?php

$link = mysql_connect("localhost", "root", "") or die("Could not connect");
print "Connected successfully";
mysql_select_db("fm4") or die("Could not select database");

date_default_timezone_set('Europe/Berlin');

define('INPUT_DIR', 'trackservice');

#if ($handle = opendir('input')) {
if ($handle = opendir(INPUT_DIR)) {

	$compStrOld = "";
	$compStr = "";

	while (false !== ($file = readdir($handle)))
		
		if ($file != "." && $file != ".." && ereg("^main", $file)) {
		
			$compStrOld = $compStr;
			$compStr = "";

			$filename = INPUT_DIR . '/' . $file;
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


			$p="/([0-9]{2}:[0-9]{2}): <b>([^<]*)<\/b> \| <i>([^<]*)<\/i>/i";

			preg_match_all($p, $o, $matches);

			#var_dump($matches);
						
			for ($i=0; $i< count($matches[0]); $i++) {

				echo "\n\nmatched: ".$matches[0][$i];
				echo "\n1: ".$matches[1][$i]."  ". bin2hex($matches[1][$i]);
				echo "\n2: ".$matches[2][$i]."  ". bin2hex($matches[2][$i]);
				echo "\n3: ".$matches[3][$i]."  ". bin2hex($matches[3][$i]);

				$compStr .= $matches[1][$i].$matches[2][$i].$matches[3][$i];
			}
			
			if ($compStr == $compStrOld)
			{
				echo "\n\n$compStr ==>  File gleicht Vorgängerin.";
			}
			else
			{
				echo "\n";
				
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
					$result = mysql_query($query) or die("Query $query failed. " . mysql_error());
					$num_rows = mysql_num_rows($result);

					if ($num_rows != 0)
					{
						echo "\n\nAlte Playtime: $dateTimeStr.";
						#$erg = mysql_fetch_array($result);
						#var_dump($erg);
					}
					else
					{
						#
						# spielzeit wird eingetragen
						#

						$query = "insert into playtime set zeit='$dateTimeStr'";
						$result = mysql_query($query) or die("\nQuery $query failed. " . mysql_error());

						# neue id zurückholen
						$query = "select id from playtime where zeit='$dateTimeStr'";
						$result = mysql_query($query) or die("\nQuery $query failed. " . mysql_error());

						$line = mysql_fetch_array($result, MYSQL_ASSOC);
						$id_playtime = $line["id"];

						echo "\n\nNeue Playtime: $dateTimeStr, Id: $id_playtime";

						#
						# track suchen
						#
						
						$title = $matches[2][$i];
						$interpret = $matches[3][$i];

						echo "\n\nTRACK\n\nTitle (match-2):|>|$title|<|, Interpret (match-3):|>|$interpret|<|";						
						
						$firstchar = substr($title, 0, 1);
						
						# wenn erster buchstabe ein leerzeichen ist, verdreht FM3 title und artist
						if ($firstchar == ' ') {
							$title = $matches[3][$i];
							$interpret = $matches[2][$i];
							
							echo "\nSWITCH Title (match-3):|>|$title|<|, Interpret (match-2):|>|$interpret|<|";
							
						}

						$title = mysql_escape_string (  html_entity_decode ( trim ( $title )));
						$interpret = mysql_escape_string (  html_entity_decode ( trim ( $interpret )));
						
						echo "\nSTRIPPED: Title:|>|$title|<|, Interpret:|>|$interpret|<|";					

						$query = "select id from track where title='$title' and interpret='$interpret'";
						$result = mysql_query($query) or die("\nQuery $query failed. " . mysql_error());
						$num_rows = mysql_num_rows($result);

						if ($num_rows != 0)
						{
							#
							# alter track zu playtime zuordnung
							#
							$line = mysql_fetch_array($result, MYSQL_ASSOC);
							$id_track = $line["id"];

							$query = "insert into track_playtime set id_track=$id_track, id_playtime=$id_playtime";
							$result = mysql_query($query) or die("\nQuery $query failed. " . mysql_error());
						
							$status = 'Bekannter';
						}
						else
						{
							#
							# track wird eingetragen
							#

							$query = "insert into track set title='$title', interpret='$interpret', firstrun='$dateTimeStr'";
							$result = mysql_query($query) or die("\nQuery $query failed. " . mysql_error());

							# neue id zurückholen
							$query = "select id from track where title='$title' and interpret='$interpret'";
							$result = mysql_query($query) or die("\nQuery $query failed. " . mysql_error());

							#
							# neuer track zu playtime zuordnung
							#
							$line = mysql_fetch_array($result, MYSQL_ASSOC);
							$id_track = $line["id"];

							$query = "insert into track_playtime set id_track=$id_track, id_playtime=$id_playtime";
							$result = mysql_query($query) or die("\nQuery $query failed. " . mysql_error());
							
							$status = 'Neuer';
						}
						
					
						# Addendum, 03./04./05.01.2015:

						# bisherige anzahl an spielzeiten feststellen
						$query = "select count(*) as count from track_playtime where id_track=$id_track";
						$result = mysql_query($query) or die("\nQuery $query failed. " . mysql_error());						
						$line = mysql_fetch_array($result, MYSQL_ASSOC);	
						$count = $line['count'];

						# - von playtime direkt nach track joinen
						# - in playtimne die aktuellen counts des tracks eintragen
						$query = "update playtime set track=$id_track, count=$count where id=$id_playtime";
						$result = mysql_query($query) or die("\nQuery $query failed. " . mysql_error());
						
						$query = "update track set count=$count, lastrun='$dateTimeStr' where id=$id_track";
						$result = mysql_query($query) or die("\nQuery $query failed. " . mysql_error());
												
						echo "\n$status Track: $id_track Title:|>|$title|<|, Interpret:|>|$interpret|<|, Count:$count";
					}
				}
			}
		}

	echo "\n\n";
	mysql_close($link);
	closedir($handle);
}
?>
