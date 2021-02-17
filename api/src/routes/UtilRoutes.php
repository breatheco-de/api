<?php

namespace Routes;

class UtilRoutes{
    
    public function __construct($app, $scopes){
        
        /**
         * Main basic stuff
         **/
        $mainHandler = new \MainHandler($app);
        $app->post('/sync/', array($mainHandler, 'syncMainData'))->add($scopes(['sync_data']));
        $app->post('/badges/import/', array($mainHandler, 'importBadges'))->add($scopes(['sync_data']));
        $app->get('/test/headers/', array($mainHandler, 'testHeaders'));

    }
    

}