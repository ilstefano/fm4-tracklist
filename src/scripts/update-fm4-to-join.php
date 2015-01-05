#!/usr/bin/php
<?php

	date_default_timezone_set('Europe/Berlin');
	
	
	//connect to database
	try {
		$dbh = new PDO('mysql:host=localhost;dbname=fm4', "root", "");
	} catch (PDOException $e) {
		die ("\nNo mysql ... " . $e->getMessage() . "\n");
	}

	
	
	
	
	$query = "select id from playtime where track=0";
	$res = $dbh->query($query);
	#$lines = $res->fetchAll();

	foreach ($res as $line) {
		
		
		$id = $line['id'];
		
		
		#echo "\nPLAYTIME: $id";
		
		$query = "select id_track from track_playtime where id_playtime = $id";
		$res = $dbh->query($query);
		$all = $res->fetchAll();
		
		if (!sizeof($all)) {
			echo "\n*************** PLAYTIME: $id WITHOUT TRACK_PLAYTIME";
			continue;
		}
		
		$id_track = $all[0]['id_track'];
		
		#var_dump($all);

		#echo "\nTRACK: $id_track";
	
		$query = "select count(id_playtime) as count from track_playtime where id_playtime <= $id and id_track=$id_track";
		$res = $dbh->query($query);
		$all = $res->fetchAll();
		$count = $all[0]['count'];
		
		echo "\nPLAYTIME: $id, TRACK: $id_track, COUNT: $count";
		
		
		$query = "update playtime set track=$id_track, count=$count where id = $id";
		$res = $dbh->query($query);
		
	}




	echo "\n\n";


?>
