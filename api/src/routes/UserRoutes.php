<?php

namespace Routes;

class UserRoutes{

    public function __construct($app, $scopes){

        /**
         * Everything Related to the user
         **/
        $userHandler = new \UserHandler($app);
        $app->get('/me', array($userHandler, 'getMe'))->add($scopes([]));
        $app->get('/users/', array($userHandler, 'getAllUsersHandler'))->add($scopes(['read_basic_info']));
        $app->get('/user/', array($userHandler, 'getAllUsersHandler'))->add($scopes(['read_basic_info']));
        $app->put('/user/', array($userHandler, 'createUserHandler'))->add($scopes(['super_admin']));
        $app->post('/user/sync', array($userHandler, 'syncUserHandler'))->add($scopes(['sync_data']));
        $app->get('/user/{user_id}', array($userHandler, 'getUserHandler'))->add($scopes(['read_basic_info']));
        $app->post('/user/{user_id}', array($userHandler, 'updateUserHandler'))->add($scopes(['read_basic_info']));
        $app->post('/credentials/user/', array($userHandler, 'createCredentialsHandler'))->add($scopes(['super_admin']));
        $app->post('/credentials/user/{user_id}', array($userHandler, 'updateCredentialsHandler'))->add($scopes(['sync_data']));
        $app->delete('/user/{user_id}', array($userHandler, 'deleteUser'))->add($scopes(['super_admin']));

        $app->post('/remind/user/{user_email}', array($userHandler, 'emailRemind'))->add($scopes([]));
        $app->get('/remind/user/{user_id}', array($userHandler, 'getRemindToken'))->add($scopes(['read_basic_info']));
        $app->post('/user/{user_id}/password', array($userHandler, 'changePassword'));

        $app->post('/settings/user/{user_id}', array($userHandler, 'updateUserSettings'))->add($scopes(['user_profile']));
        $app->get('/settings/user/{user_id}', array($userHandler, 'getUserSettings'))->add($scopes(['user_profile']));

        $app->post('/user/{user_id}/device', array($userHandler, 'createUserDevice'))->add($scopes(['read_basic_info']));
        $app->get('/user/{user_id}/device', array($userHandler, 'getUserDevices'))->add($scopes(['read_basic_info']));
        $app->delete('/user/device/{device_id}', array($userHandler, 'deleteUserDevice'))->add($scopes(['read_basic_info']));

    }


}