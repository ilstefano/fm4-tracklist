<?php

namespace FM4\Controller;

use Silex\Application;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

// ======================================================================================================================

class ReportController
{
	
	// ==================================================================================================================
	
    public function indexAction(Request $request, Application $app)
    {
    	
    	#$ret = file_get_contents(__DIR__ . "/../../../app/views/layout.tal.html");
    	#return $ret;
    	
    	#$template = new \PHPTAL(__DIR__ . "/../../../app/views/layout.tal.html");
    	#$template->title = 'mein docuadmin';
    	
    	// set your view file. view file is set under /views directory
    	#$app['phptal.view'] = "layout.tal.html";
    	#$app['phptal']->title = "PHPTAL in Silex";
    	
    	#return $template->execute();  
    	
    	# finde aktuelles Maximum --- todo: cachen
    	# wenn track in playtime durchgelaufen ist: $select = 'select max(anzahl) as max from (select count(*) as anzahl from playtime group by track) as c';
    	# temp:
    	$select = 'select max(anzahl) as max from (select count(*) as anzahl from playtime where track!=0  group by track) as c';
    	
    	$max = $app['db']->fetchColumn($select);
    	
    	$schwelle = 0.6; # 80% von max sollen angeziegt werden
    	$precision = 1; # anuahl an signifikanten stellen, die gerundet werden
    	
    	$varianz = floor($max * $schwelle);
    	
    	
    	$select = 'select
    			count(*) as count, t.interpret, t.title
    			from playtime
    			left join track t on (track=t.id)
    			group by track
    			having count > ' . $varianz .'
    			order by count desc';
    	
        $data = array(
        	'tracks' => $app['db']->fetchAll($select),
        	'varianz' => $varianz,
        	'max' => $max,
        	'schwelle' => $schwelle
        );
        return $app['twig']->render('reportlist.html.twig', $data);
    }
    
    // ==================================================================================================================
     
    
    public function showTrackAction(Request $request, Application $app, $track)
    {
     
    	$select = "SELECT 
    				DATE_FORMAT(zeit, '%d.%m.%Y %Hh%i') as zeit, id
    				FROM playtime p
    				where p.track=$track
    				order by id desc
    				limit 100";
    	
    	$res = $app['db']->fetchAll($select);
     
    	$data = array(
    		'playlist' => $app['db']->fetchAll($select),
    		'track' => $track
        );
    	
        return $app['twig']->render('track.html.twig', $data);
    }
    
}

// ======================================================================================================================
