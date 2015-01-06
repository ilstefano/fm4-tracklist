<?php

namespace FM4\Controller;

use Silex\Application;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

// ======================================================================================================================

class TrackController
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
    	
    	$limit_str = ' limit 50';  
    	
    	$select = 'select
    				DATE_FORMAT(zeit, "%d.%m. %Hh%i") as zeit, interpret, title, count, track
    				from playtime pt
    				left join track t on (pt.track=t.id)
    				order by pt.id desc' . $limit_str;
    	
        $data = array(
        	'tracks' => $app['db']->fetchAll($select)
        );
        return $app['twig']->render('tracklist.html.twig', $data);
    }
    
    // ==================================================================================================================
     
    
    public function showTrackPlaylistAction(Request $request, Application $app, $id_track)
    {
     
    	$limit_str = ' limit 50';
    	
    	$limit_req = $request->query->get('all', 0);
    	if ($limit_req)
    		$limit_str = '';
    	
    	$select = 'select
    				title, interpret
    				from track
    				where id=' . $id_track;
    	
    	$artist = array(
    			'interpret' => $app['db']->fetchColumn($select, array(), 1),
    			'title' => $app['db']->fetchColumn($select, array(), 0)
    			);
    	 	
    	$select = 'SELECT 
    				DATE_FORMAT(zeit, "%d.%m.%Y %Hh%i") as zeit, id, count
    				FROM playtime p
    				where p.track=' . $id_track .'
    				order by id desc' . $limit_str;
    	
    	$res = $app['db']->fetchAll($select);
     
    	$data = array(
    		'playlist' => $app['db']->fetchAll($select),
    		'artist' => $artist
        );
    	
        return $app['twig']->render('track.html.twig', $data);
    }
    
}

// ======================================================================================================================
