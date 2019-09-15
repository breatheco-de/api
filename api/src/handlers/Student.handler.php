<?php

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Carbon\Carbon;
use Helpers\BCValidator;
use Helpers\AuthHelper;
use Helpers\Mailer;
use Helpers\ArgumentException;

class StudentHandler extends MainHandler{

    protected $slug = 'Student';

    public function getAllStudentsHandler(Request $request, Response $response) {
        $students = $this->app->db->table('students');

        $data = $request->getParams();
        if(!empty($data["status"])) $students = $students->where('status', $data["status"]);
        if(!empty($data["financial_status"])) $students = $students->where('financial_status', $data["financial_status"]);
        if(isset($data["seeking_job"])) $students = $students->where('seeking_job', $data["seeking_job"]);
        if(isset($data["found_job"])) $students = $students->where('found_job', $data["found_job"]);
        if(!empty($data["language"])) $students = $students->where('language', $data["language"]);
        if(!empty($data["gender"])) $students = $students->where('gender', $data["gender"]);

        if(!empty($data["location_id"]))
            $students = $students->join('cohort_student', 'students.user_id', '=', 'cohort_student.student_user_id')
                                 ->join('cohorts', 'cohorts.id', '=', 'cohort_student.cohort_id')
                                 ->where('cohorts.location_id', $data["location_id"]);
        $students = (array) collect($students->distinct()->get())->unique('user_id')->toArray();
        return $this->success($response, Student::hydrate($students)->all());
    }

    public function getStudentHandler(Request $request, Response $response) {
        $breathecodeId = $request->getAttribute('student_id');
        if(is_numeric($breathecodeId)) $user = User::find($breathecodeId);
        else $user = User::where('username', $breathecodeId)->first();
        if(!$user) throw new ArgumentException('Invalid student email or id: '.$breathecodeId, 404);
        if(!$user->student) throw new ArgumentException('The user '.$breathecodeId.' is not a student', 404);

        return $this->success($response,$user->student->append('full_cohorts'));
    }

    public function updateStudentStatus(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');
        $data = $request->getParsedBody();

        $updated = false;
        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id: '.$studentId);

        if(!empty($data['status'])){
            if(!in_array($data['status'], Student::$possibleStatus))
                throw new ArgumentException('Invalid student status: '.$data['status']);

            $updated = true;
            $student->status = $data['status'];
            if($data['status'] === 'student_dropped') $student = $this->setMandatory($student,$data,'dropout_date',BCValidator::DATETIME);
            else $student->dropout_date = null;
        }

        if(!empty($data['financial_status'])){
            if(!in_array($data['financial_status'], Student::$possibleFinancialStatus))
                throw new ArgumentException('Invalid student finantial status: '.$data['financial_status']);

            $updated = true;
            $student->financial_status = $data['financial_status'];
        }

        if($updated) $student->save();
        else throw new ArgumentException('You have to specify either the financial_status or status.');

        return $this->success($response,$student);
    }

    public function getStudentActivityHandler(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');
        $data = $request->getParams();

        $limit = 10;
        if(isset($data["limit"])) $limit = $data["limit"];
        $skip = 0;
        if(isset($data["start"])) $skip = $data["start"];

        $activities = Activity::where('student_user_id', $studentId)->orderBy('created_at', 'desc')->skip($skip)->take($limit)->get();
        if(!$activities) throw new ArgumentException('Invalid student id:'.$studentId);

        return $this->success($response,$activities);
    }

    public function getStudentBriefing(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');

        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id: '.$studentId);

        $acumulatedPoints = $this->app->db->table('activities')->where([
            'activities.student_user_id' => $studentId
        ])->sum('activities.points_earned');
        $result['acumulated_points'] = $acumulatedPoints;

        $creation = $student->created_at;
        $result['creation_date'] = $creation->format('Y-m-d');

        $count = $this->_daysBetween($creation);
        $result['days'] = $count;

        $profile = Profile::find(1);
        $result['profile'] = $profile;

        return $this->success($response,$result);
    }

    public function createStudentHandler(Request $request, Response $response) {
        $data = $request->getParsedBody();
        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        $cohort = Cohort::where('slug', $data['cohort_slug'])->first();
        if(!$cohort) throw new ArgumentException('Invalid cohort slug');

        if(!empty($data['email'])) $data['email'] = urldecode($data['email']);
        else throw new ArgumentException('Invalid email');

        $user = User::where('username', $data['email'])->first();
        if($user && $user->student) throw new ArgumentException('There is already a student with this email on te API');
        else if(!$user)
        {
            $user = new User;
            $user->username = $data['email'];
            $user->type = 'student';
            $user = $this->setMandatory($user,$data,'first_name',BCValidator::NAME);
            $user = $this->setMandatory($user,$data,'last_name',BCValidator::NAME);
            $user = $this->setOptional($user,$data,'wp_id');
            $user = $this->setOptional($user,$data,'avatar_url');
            $user = $this->setOptional($user,$data,'bio');
            $user->save();
        }

        if($user)
        {
            $user = $this->setMandatory($user,$data,'first_name',BCValidator::NAME);
            $user = $this->setMandatory($user,$data,'last_name',BCValidator::NAME);
            $user = $this->setOptional($user,$data,'avatar_url');
            $user = $this->setOptional($user,$data,'bio');
            $user->save();

            $student = new Student();
            $student = $this->setOptional($student,$data,'total_points');
            $student = $this->setOptional($student,$data,'phone');
            $student = $this->setOptional($student,$data,'github');
            $student = $this->setOptional($student,$data,'internal_profile_url');
            $user->student()->save($student);
            $student->cohorts()->save($cohort);

            if(EMAIL_NOTIFICATIONS) $this->_sendUserInvitation($user);

        }

        return $this->success($response,$student);
    }

    private function _sendUserInvitation($user){

        if(empty($user->username)) throw new Exception('Imposible to send email', 500);

        $token = new Passtoken();
        $token->token = md5(AuthHelper::randomToken());
        $token->user()->associate($user);
        $token->save();

        $mailer = new Mailer();
        $callback = ($user->type == 'student') ? STUDENT_URL.'/login' : ADMIN_URL;
        $result = $mailer->sendAPI("invite", [
            "email"=> $user->username,
            "url"=> ASSETS_URL.'/apps/remind/?invite=true&id='.$user->id.'&t='.$token->token.'&callback='.base64_encode($callback)
        ]);

    }

    public function updateStudentHandler(Request $request, Response $response) {

        $studentId = $request->getAttribute('student_id');
        $data = $request->getParsedBody();

        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id: '.$studentId);

        if(isset($data['email'])) throw new ArgumentException('Students emails cannot be updated through this service');

        $user = $student->user;
        $user = $this->setOptional($user,$data,'first_name');
        $user = $this->setOptional($user,$data,'last_name');
        $user = $this->setOptional($user,$data,'avatar_url');
        $user = $this->setOptional($user,$data,'description');
        $user->save();
        $student = $this->setOptional($student,$data,'internal_profile_url');
        $student = $this->setOptional($student,$data,'total_points');
        $student = $this->setOptional($student,$data,'show_tutorial');
        $student = $this->setOptional($student,$data,'github');
        $student = $this->setOptional($student,$data,'seeking_job');
        $student = $this->setOptional($student,$data,'found_job');
        $student = $this->setOptional($student,$data,'github');
        $student = $this->setOptional($student,$data,'phone');
        $student->save();

        return $this->success($response,$student);
    }

    public function updateStudentMeHandler(Request $request, Response $response) {

        $c = $this->app->getContainer();
        if(!$c["token_data"]["user"]) throw new ArgumentException('Unable to recognize user with that token');

        $data = $request->getParsedBody();
        $student = $c["token_data"]["user"]->student;

        if(isset($data['email'])) throw new ArgumentException('Students emails cannot be updated through this service');

        $user = $c["token_data"]["user"];
        $user = $this->setOptional($user,$data,'first_name');
        $user = $this->setOptional($user,$data,'last_name');
        $user = $this->setOptional($user,$data,'avatar_url');
        $user = $this->setOptional($user,$data,'description');
        $user->save();
        $student = $this->setOptional($student,$data,'internal_profile_url');
        $student = $this->setOptional($student,$data,'total_points');
        $student = $this->setOptional($student,$data,'show_tutorial');
        $student = $this->setOptional($student,$data,'github');
        $student = $this->setOptional($student,$data,'phone');
        $student->save();

        return $this->success($response,$student);
    }

    public function createStudentActivityHandler(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');
        $data = $request->getParsedBody();

        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        $badge = Badge::where('slug', $data['badge_slug'])->first();
        if(!$badge) throw new ArgumentException('Invalid badge slug: '.$data['badge_slug']);

        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id: '.$studentId);

        if(!in_array($data['type'],Activity::$possibleTypes))  throw new ArgumentException('Invalid activity type: '.$data['type']);

        if(empty($data['points_earned'])) throw new ArgumentException('It seems you are trying to give 0 or NULL points to the student');

        $activity = new Activity();
        $activity = $this->setMandatory($activity,$data,'type');
        $activity = $this->setMandatory($activity,$data,'name', BCValidator::DESCRIPTION);
        $activity = $this->setMandatory($activity,$data,'description', BCValidator::DESCRIPTION);
        $activity = $this->setMandatory($activity,$data,'points_earned', BCValidator::POINTS);

        $activity->student()->associate($student);
        $activity->badge()->associate($badge);
        $activity->save();


        $student->updateBasedOnActivity();

        $activity->makeHidden(["student"]);

        return $this->success($response,$activity);
    }

    public function deleteStudentActivityHandler(Request $request, Response $response) {
        $activityId = $request->getAttribute('activity_id');

        $activity = Activity::find($activityId);
        if(!$activity) throw new ArgumentException('Invalid activity id');

        $attributes = $activity->getAttributes();
        $now = time(); // or your date as well
        $daysOld = floor(($now - strtotime($attributes['created_at'])) / DELETE_MAX_DAYS);
        if($daysOld>5) throw new ArgumentException('The activity is to old to delete');

        $student = $activity->student()->first();
        $activity->delete();
        $student->updateBasedOnActivity();

        return $this->success($response,"ok");
    }

    public function deleteStudentHandler(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');

        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id');

        $attributes = $student->getAttributes();
        $now = time(); // or your date as well
        $daysOld = floor(($now - strtotime($attributes['created_at'])) / DELETE_MAX_DAYS);
        if($daysOld>5) throw new ArgumentException('The student is to old to delete');

        $studentActivities = $student->activities()->get();
        foreach($studentActivities as $act) $act->delete();

        $studentBadges = $student->badges()->get();
        $student->badges()->detach($studentBadges);

        $student->delete();

        return $this->success($response,"ok");
    }

    private function _daysBetween($date1, $date2=null){

        if(!$date2) $date2 = Carbon::now('America/Vancouver');
        return $date1->diffInDays($date2);
    }

}