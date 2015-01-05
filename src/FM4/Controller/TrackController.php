<?php

namespace FM4\Controller;

use Silex\Application;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class TrackController
{
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
    	
        $data = array(
        	'tracks' => $app['db']->fetchAll('select * from playtime pt left join track t on (pt.track=t.id)  order by pt.id desc limit 50')
        );
        return $app['twig']->render('tracklist.html.twig', $data);
    }
    
    public function showTrackAction(Request $request, Application $app, $track)
    {
     

     
    	$data = array(
    		'playlist' => null,
    		'track' => $track
        );
    	
        return $app['twig']->render('track.html.twig', $data);
    }
    
}
