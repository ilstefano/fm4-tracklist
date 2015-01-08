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
	
    /**
     * Einstiegsseite: zeige die zuletzt gespielten Tracks
     * 
     * @param Request $request
     * @param Application $app
     */
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

    	# bestimme opt. 'n': Anzahl an auszugebenden Tracks
    	$n = $request->query->get('n', 0);
    	
    	$n_max = 1000;
    	$n_default = 10;
    	if ((!$n) || (preg_match('/^[0-9]*$/', $n) === 0)) $n = $n_default; 	
    	if ($n > $n_max) $n = $n_max;
    	
    	# bestimme opt. 'id': "schwerpunkt" der Ausgabe
    	$id = $request->query->get('id', 0);
    	
    	$select = 'select max(id) from playtime';
    	$id_max =  $app['db']->fetchColumn($select); 	
    	if ((!$id) || (preg_match('/^[0-9]*$/', $n) === 0) || ($id > $id_max))
    	{
    		# kein neuer schwerpunkt: vom jüngsten moment an
    		$id = $id_max;
    		$dateFormatStr = '%d.%m. %Hh%i'; # sollte das in die view ???
    	} else {
    		$id += floor($n/2);
    		$dateFormatStr = '%d.%m.%Y %Hh%i'; # mit jahr, wenn andere auswahl
    	}

    	
    	$select = 'select
    				pt.id, DATE_FORMAT(zeit, "' . $dateFormatStr . '") as zeit, interpret, title, firstrun, pt.switch, pt.count as count, track as trackid
    				from playtime pt
    				left join track t on (pt.track=t.id)
    				where pt.id <= ' . $id .'
    				order by pt.id desc limit ' . $n;	
    	
        $data = array(
        	'tracks' => $app['db']->fetchAll($select)
        );
        return $app['twig']->render('tracklist.html.twig', $data);
    }
    
    // ==================================================================================================================
     
    
    public function showTrackPlaylistAction(Request $request, Application $app, $id)
    {
     
    	$limit_str = ' limit 50';
    	
    	$limit_req = $request->query->get('all', 0);
    	
    	if ($limit_req)
    		$limit_str = '';
    	
    	$select = 'select title, interpret from track where id=' . $id;
    	
    	$row = $app['db']->fetchAssoc($select);
    	
    	$track = array(
    			'artist' => $row['interpret'],
    			'title' => $row['title']
    			);
    	 	
    	$select = 'select 
    				DATE_FORMAT(zeit, "%d.%m.%Y %Hh%i") as zeit, id, count
    				from playtime p
    				where p.track=' . $id .'
    				order by id desc' . $limit_str;
    	
    	$res = $app['db']->fetchAll($select);
     
    	$data = array(
    		'playlist' => $app['db']->fetchAll($select),
    		'track' => $track
        );
    	
        return $app['twig']->render('trackplaylist.html.twig', $data);
    }
    
    // ==================================================================================================================
     
    
    public function showTrackArtistIndexAction(Request $request, Application $app)
    {
    	 
		return 'No Index Action for artist';
    	 
    	return $app['twig']->render('xxx.html.twig', $data);
    } 
    
    // ==================================================================================================================
     
    
    public function showTrackArtistListAction(Request $request, Application $app, $id)
    {
    
    	$select = 'select interpret from track where id =' . $id;
    	 
    	$artist = $app['db']->fetchColumn($select);
    	
    	$select = 'select count, id as trackid, title, DATE_FORMAT(lastrun, "%d.%m.%Y %Hh%i") as lastrun, DATE_FORMAT(firstrun, "%d.%m.%Y %Hh%i") as firstrun from track where interpret ="' . $artist .'"';
    	 
    	$data = array(
    			'tracklist' => $app['db']->fetchAll($select),
    			'artist' => $artist
    	);
    
    	return $app['twig']->render('trackartistlist.html.twig', $data);
    }
    
}

// ======================================================================================================================
