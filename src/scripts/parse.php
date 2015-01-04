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
					echo "\n\n" . ($i+1) . ") Spielzeit: $spielzeit, Tageszeit: $tageszeit, Spieldatum: $spieldatum.";
					
					$dateTimeStr = $spieldatum;
					$dateTimeStr .= " ";
					$dateTimeStr .= $spielzeit . ":00";

					$query = "select id from playtime where zeit='$dateTimeStr'";
					$result = mysql_query($query) or die("Query $query failed. " . mysql_error());
					$num_rows = mysql_num_rows($result);

					if ($num_rows != 0)
					{
						echo "\nAlte Playtime: $dateTimeStr.";
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

						echo "\nNeue Playtime: $dateTimeStr // $id_playtime";

						#
						# track suchen
						#

						$title = mysql_escape_string($matches[2][$i]);
						$interpret = mysql_escape_string($matches[3][$i]);

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
						
							echo "\nBekannter Track: $id_track ($title, $interpret, new=$new)";

						}
						else
						{
							#
							# track wird eingetragen
							#

							$query = "insert into track set title='$title', interpret='$interpret'";
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
							
							echo "\nNeuer Track: $id_track ($title, $interpret, new=$new)";
						}
						
						# Addendum, 03./04.01.2015:
						# - von playtime direkt nach track joinen
						# - in playtimne die aktuellen counts des tracls eintragen
						$query = "update playtime set track=$id_track, count=$num_rows where id=$id_playtime";
						$result = mysql_query($query) or die("\nQuery $query failed. " . mysql_error());
						
					}
				}
			}
		}

	echo "\n\n";
	mysql_close($link);
	closedir($handle);
}
?>
