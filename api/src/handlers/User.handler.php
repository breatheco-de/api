<?php

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Helpers\Mailer;
use Helpers\AuthHelper;
use Helpers\BCValidator;
use Helpers\ArgumentException;
use Helpers\NotFoundException;

class UserHandler extends MainHandler{

    public function getMe(Request $request, Response $response) {

        $data = $request->getQueryParams();
        if(!empty($data['access_token']))
        {
            $storage = $this->app->storage;
            $user = $storage->getUserFromToken($data['access_token']);
            if(!empty($user) and isset($user['username']))
            {
                $bcUser = User::where('username', $user['username'])->first();
                if(!$bcUser) throw new ArgumentException('This token does not correspond to any users');

                if($bcUser->type == 'student')
                {
                    $bcUser->student->type = 'student';
                    return $this->success($response, $bcUser->student->makeHidden('cohorts')->append('full_cohorts'));
                }
                else if($bcUser->type == 'teacher'){

                    $bcUser->teacher->type = 'teacher';
                    return $this->success($response, $bcUser->teacher->makeHidden('cohorts')->append('full_cohorts'));
                }

                return $this->success($response,$bcUser);

            }else throw new ArgumentException('This token does not correspond to any users');
        }else throw new ArgumentException('No access_token provided');

        return $this->success($response,null);

    }

    public function getAllUsersHandler(Request $request, Response $response) {
        $users = User::all();
        return $this->success($response,$users);
    }

    public function getUserHandler(Request $request, Response $response) {
        $breathecodeId = $request->getAttribute('user_id');

        $user = null;
        if(is_numeric($breathecodeId)) $user = User::find($breathecodeId);
        else{

            // validate email format
            if(!is_numeric($breathecodeId)) BCValidator::validate(BCValidator::EMAIL, $breathecodeId, 'email');

            $user = User::where('username', $breathecodeId)->first();
        }


        if(!$user) throw new NotFoundException('User not found: '.$breathecodeId);

        return $this->success($response,$user);
    }

    public function createUserDevice(Request $request, Response $response){

        $userId = $request->getAttribute('user_id');
        if(empty($userId)) throw new ArgumentException('There was an error retrieving the user_id');

        $data = $request->getParsedBody();
        if(empty($data)) throw new ArgumentException('There was an error retrieving the device token, it needs to be a valid JSON');

        $device = Device::find($data['token']);
        if($device) return $this->success($response,$device);

        $user = User::find($userId);
        if(!$user) throw new ArgumentException('User id '.$userId.' not found', 404);

        $newDevice = new Device();
        $newDevice->token = $data['token'];
        $newDevice->user()->associate($user->id);
        $newDevice->save();

        return $this->success($response,$user->devices()->get());
    }

    public function getUserDevices(Request $request, Response $response){

        $userId = $request->getAttribute('user_id');
        if(empty($userId)) throw new ArgumentException('There was an error retrieving the user_id');

        $user = User::find($userId);
        if(!$user) throw new ArgumentException('User id '.$userId.' not found', 404);

        return $this->success($response,$user->devices()->get());
    }


    public function deleteUserDevice(Request $request, Response $response){

        $deviceId = $request->getAttribute('device_id');
        if(empty($deviceId)) throw new ArgumentException('There was an error retrieving the device_id');

        $device = Device::find($deviceId);
        if($device) $device->delete();

        return $this->success($response,'ok');
    }

    public function createUserHandler(Request $request, Response $response) {

        $data = $request->getParsedBody();
        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        $user = User::where('username', $data['username'])->first();
        if($user) throw new ArgumentException('There is already a user with this email');

        $user = new User();
        $user = $this->setMandatory($user,$data,'type',BCValidator::SLUG);
        $user = $this->setMandatory($user,$data,'first_name',BCValidator::NAME);
        $user = $this->setMandatory($user,$data,'last_name',BCValidator::NAME);
        $user = $this->setMandatory($user,$data,'username',BCValidator::EMAIL);
        //$user = $this->setMandatory($user,$data,'phone',BCValidator::PHONE);
        $user->save();

        //create/update the teacher or student representation
        $this->generateStudentOrTeacher($user);

        return $this->success($response,$user);
    }

    public function createCredentialsHandler(Request $request, Response $response) {
        $data = $request->getParsedBody();
        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        if(!in_array($data['type'],User::$possibleTypes)) throw new ArgumentException('Invalid user type: "'.$data['type'].'" given');

        $user = User::where('username', $data['email'])->first();
        if(!$user)
        {
            $user = new User();
            $user = $this->setOptional($user,$data,'wp_id');
            $user = $this->setOptional($user,$data,'first_name');
            $user = $this->setOptional($user,$data,'last_name');
            $user = $this->setMandatory($user,$data,'type',BCValidator::SLUG);
            $user->username = $data['email'];
            $user->save();

            if(!empty($data['parent_location_id'])){
                $location = Cohort::find($data['parent_location_id']);
                if($location) $user->parent_location()->associate($location);
            }
            $user->save();

            $token = new Passtoken();
            $token->token = md5(AuthHelper::randomToken());
            $token->user()->associate($user);
            $token->save();

            $mailer = new Mailer();
            $callback = ($data['type'] == 'student') ? STUDENT_URL : ADMIN_URL;
            $result = $mailer->sendAPI("invite", [
                "email"=> $user->username,
                "url"=> ASSETS_URL.'/apps/remind/?id='.$user->id.'&t='.$token->token.'&invite=true&callback='.base64_encode($callback)
            ]);
        }
        else throw new ArgumentException('User already exists with email: '.$data['email']);

        return $this->success($response,$user);
    }

    public function updateCredentialsHandler(Request $request, Response $response) {
        $userId = $request->getAttribute('user_id');
        if(empty($userId)) throw new ArgumentException('There was an error retrieving the user_id');

        $data = $request->getParsedBody();
        if(empty($data) || empty($data['password'])) throw new ArgumentException('You need to specify a password');

        $user = User::find($userId);
        if(!$user) throw new ArgumentException('Invalid user id: '.$userId);

        $storage = $this->app->storage;
        $oauthUser = $storage->setUserWithoutHash($user->username, $data['password'], null, null);

        if(empty($oauthUser)) throw new ArgumentException('Unable to update User credentials');

        return $this->success($response,$user);
    }

    public function updateUserHandler(Request $request, Response $response) {
        $userId = $request->getAttribute('user_id');
        if(empty($userId)) throw new ArgumentException('There was an error retrieving the user_id');

        $data = $request->getParsedBody();

        $user = User::find($userId);
        if(!$user) throw new ArgumentException('Invalid user id: '.$userId);

        $typeIsChanging = false;
        if(!empty($data['type']) and $data['type'] != $user->type){
            $typeIsChanging = true;
            if(!in_array($data['type'], User::$possibleTypes))
                throw new ArgumentException('Invalid student type');
        }

        if(!empty($data['parent_location_id'])){
            $location = Cohort::find($data['parent_location_id']);
            if($location) $user->parent_location()->associate($location);
        }

        $user = $this->setOptional($user,$data,'first_name',BCValidator::NAME);
        $user = $this->setOptional($user,$data,'last_name',BCValidator::NAME);
        $user = $this->setOptional($user,$data,'type',BCValidator::SLUG);
        $user->save();

        //create/update the teacher or student representation
        if($typeIsChanging) $this->generateStudentOrTeacher($user);

        return $this->success($response,$user);
    }

    public function emailRemind(Request $request, Response $response) {
        $userEmail = $request->getAttribute('user_email');
        if(empty($userEmail)) throw new ArgumentException('There was an error retrieving the user_email', 400);
        else $user = User::where('username', $userEmail)->first();
        if(!$user) throw new ArgumentException('Invalid user email: '.$userEmail, 400);
        //debug($userEmail);

        $token = new Passtoken();
        $token->token = md5(AuthHelper::randomToken());
        $token->user()->associate($user);
        $token->save();

        $mailer = new Mailer();
        $callback = ($user->type == 'student') ? STUDENT_URL : ($user->type == 'teacher') ? TEACHER_URL : ADMIN_URL;
        $result = $mailer->sendAPI("password_reminder", [
            "email"=> $user->username,
            "url"=> ASSETS_URL.'/apps/remind/?id='.$user->id.'&t='.$token->token.'&callback='.base64_encode($callback)
        ]);

        if(!$result) throw new ArgumentException('Unable to send email', 400);
        return $this->success($response,'ok');
    }

    public function getRemindToken(Request $request, Response $response) {
        $userId = $request->getAttribute('user_id');
        if(empty($userId)) throw new ArgumentException('There was an error retrieving the user_id');

        $token = $request->getQueryParam('token', null);
        if(!isset($token)) throw new ArgumentException('Missing params');

        $user = $this->app->db->table('users')
            ->join('passtokens', 'users.id', '=', 'passtokens.user_id')
            ->where('users.id', $userId)
            ->where('passtokens.token', $token)
            ->select('users.*', 'passtokens.*')
            ->get()->first();
        if(!$user) throw new ArgumentException('Invalid user or token or both');

        $user = User::find($userId);
        //TODO: The new token should expire after 15 min
        //$user->passtokens()->delete();

        $newToken = new Passtoken();
        $newToken->token = md5(AuthHelper::randomToken());
        $newToken->save();
        $newToken->user()->associate($user->id);
        $newToken->save();
        if(!$newToken) throw new ArgumentException('There was a problem');

        $user->token = $newToken->token;

        return $this->success($response,$user);
    }

    public function changePassword(Request $request, Response $response) {
        $userId = $request->getAttribute('user_id');
        if(empty($userId)) throw new ArgumentException('There was an error retrieving the user_id');

        else $user = User::where('id', $userId)->first();
        if(!$user) throw new ArgumentException('Invalid user id: '.$userId);

        $body = $request->getParsedBody();
        if(!isset($body['password'])) throw new ArgumentException('Missing param: password');
        if(!isset($body['repeat'])) throw new ArgumentException('Missing param: repeat');
        if(!isset($body['token'])) throw new ArgumentException('Missing param: token');
        if($body['repeat'] != $body['password']) throw new ArgumentException('Passwords must match');

        $storage = $this->app->storage;
        $oauthUser = $storage->setUser($user->username, $body['password'], null, null);
        if(empty($oauthUser)) throw new ArgumentException('Unable to update User credentials');
        else{
            $user->passtokens()->delete();

            $mailer = new Mailer();
            $result = $mailer->sendAPI("password_changed", ["email"=> $user->username]);

            if($result) return $this->success($response,$user);
            else return $this->success($response,$user);
        }

    }

    public function updateUserSettings(Request $request, Response $response) {
        $userId = $request->getAttribute('user_id');
        $data = $request->getParsedBody();

        $user = User::find($userId);
        if(!$user) throw new ArgumentException('Invalid user id: '.$userId);

        if(!$this->_validateSettings($data)) throw new ArgumentException('Invalid user settings');

        $settings = $user->getUserSettings();
        foreach($data as $key => $val) $settings[$key] = $val;

        unset($settings["client_id"]);
        unset($settings["client_secret"]);
        unset($settings["access_token"]);
        $user->setUserSettings($settings);
        $user->save();

        return $this->success($response,$settings);
    }

    public function getUserSettings(Request $request, Response $response) {
        $userId = $request->getAttribute('user_id');

        $user = User::find($userId);
        if(!$user) throw new ArgumentException('Invalid student id: '.$userId);

        $settings = $user->getUserSettings();

        return $this->success($response,$settings);
    }

    public function deleteUser(Request $request, Response $response) {
        $userId = $request->getAttribute('user_id');
        if(empty($userId)) throw new ArgumentException('There was an error retrieving the user_id');

        $user = User::find($userId);
        if(!$user) throw new ArgumentException('User not found');

        $user->delete();

        return $this->success($response,'The user was deleted successfully');
    }

    private function generateStudentOrTeacher($user){

        switch($user->type)
        {
            case "teacher":
                $teacher = $user->teacher()->first();
                if(!$teacher)
                {
                    $teacher = new Teacher();
                    $user->teacher()->save($teacher);
                }
                $user->student()->delete();

                return $teacher;
            break;
            case "student":
                $student = $user->student()->first();
                if(!$student)
                {
                    $student = new Student();
                    $user->student()->save($student);
                }
                $user->teacher()->delete();

                return $student;
            break;
        }
    }

    private function _validateSettings($settings){

        return true;

        $settingsExample = [
            "notification-new-badge" => true
        ];

    }

}