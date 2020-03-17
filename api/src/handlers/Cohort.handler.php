<?php

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Helpers\BCValidator;
use Helpers\ArgumentException;

class CohortHandler extends MainHandler{

    protected $slug = 'Cohort';

    public function getAllCohortsHandler(Request $request, Response $response) {

        $cohorts = Cohort::all();

        $data = $request->getParams();
        if(!empty($data))
        {
            $filtered = $cohorts->filter(function ($value, $key) use($data) {

                if(!empty($data["language"])) if($value->language != $data["language"]) return false;
                if(!empty($data["location"]) || !empty($data["location_id"])){
                    $location = $value->location()->first();
                    if(!empty($data["location"]) && $location->slug != $data["location"]) return false;
                    if(!empty($data["location_id"]) && $location->id != $data["location_id"]) return false;
                }
                if(!empty($data["stage"])) if(!in_array($value->stage, explode(",",$data["stage"]))) return false;
                if(!empty($data["stage_not"])) if(in_array($value->stage, explode(",",$data["stage_not"]))) return false;

                return true;
            });
            return $this->success($response,$filtered->values());
        }

        return $this->success($response,$cohorts);
    }

    public function getSingleCohort(Request $request, Response $response) {
        $cohortId = $request->getAttribute('cohort_id');

        $cohort = null;
        if(is_numeric($cohortId)) $cohort = Cohort::find($cohortId);
        else $cohort = Cohort::where('slug', $cohortId)->first();
        if(!$cohort) throw new ArgumentException('Invalid cohort slug or id: '.$cohortId);

        return $this->success($response,$cohort->makeHidden('teachers')->append('full_teachers'));
    }

    public function getAllCohortsFromLocationHandler(Request $request, Response $response) {
        $locationId = $request->getAttribute('location_id');

        $location = Location::find($locationId);
        if(!$location) throw new ArgumentException('Invalid location id:'.$locationId);

        return $this->success($response,$location->cohorts()->get());
    }

    public function getAllCohortsFromTeacherHandler(Request $request, Response $response) {
        $teacherId = $request->getAttribute('teacher_id');

        $teacher = Teacher::find($teacherId);
        if(!$teacher) throw new ArgumentException('Invalid teacher id:'.$teacherId);

        return $this->success($response,$teacher->cohorts()->get());
    }

    public function createCohortHandler(Request $request, Response $response) {
        $data = $request->getParsedBody();
        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        $location = Location::where('slug', $data['location_slug'])->first();
        if(!$location) throw new ArgumentException('Invalid '.$data['location_slug'].' slug');

        $profile = Profile::where('slug', $data['profile_slug'])->first();
        if(!$profile) throw new ArgumentException('Invalid profile slug: '.$data['profile_slug']);

        $cohort = new Cohort();
        $cohort = $this->setMandatory($cohort,$data,'name',BCValidator::NAME);
        $cohort = $this->setMandatory($cohort,$data,'slug',BCValidator::SLUG);
        $cohort->stage = Cohort::$possibleStages[0]; //not-started
        $cohort = $this->setOptional($cohort,$data,'language',BCValidator::SLUG);
        $cohort = $this->setOptional($cohort,$data,'streaming_slug',BCValidator::SLUG);
        $cohort = $this->setOptional($cohort,$data,'syllabus_slug',BCValidator::SLUG);
        $cohort = $this->setOptional($cohort,$data,'slack_url',BCValidator::URL);
        $cohort = $this->setOptional($cohort,$data,'kickoff_date',BCValidator::DATETIME);
        $cohort = $this->setOptional($cohort,$data,'ending_date',BCValidator::DATETIME);
        $cohort = $this->setOptional($cohort,$data,'meeting_url',BCValidator::URL);
        
        $cohort->profile()->associate($profile);
        $location->cohorts()->save($cohort);
        $cohort->save();

        return $this->success($response,$cohort);
    }

    public function updateCohortHandler(Request $request, Response $response) {
        $cohortId = $request->getAttribute('cohort_id');
        $data = $request->getParsedBody();
        if(!$data) throw new ArgumentException('There was an error parsing the request information (JSON)');

        $cohort = Cohort::find($cohortId);
        if(!$cohort) throw new ArgumentException('Invalid cohort id: '.$cohortId);

        if(!empty($data['stage']) && !in_array($data['stage'], Cohort::$possibleStages))
            throw new ArgumentException('Invalid cohort stage');

        if(!empty($data['profile_slug']))
        {
            $profile = Profile::where('slug', $data['profile_slug'])->first();
            if(!$profile) throw new ArgumentException('Invalid profile slug: '.$data['profile_slug']);
            $cohort->profile()->associate($profile);
        }

        if(!empty($data['location_slug']))
        {
            $location = Location::where('slug', $data['location_slug'])->first();
            if(!$location) throw new ArgumentException('Invalid location slug: '.$data['location_slug']);
            $cohort->location()->associate($location);
        }

        $cohort = $this->setOptional($cohort,$data,'name',BCValidator::NAME);
        $cohort = $this->setOptional($cohort,$data,'stage',BCValidator::SLUG);
        $cohort = $this->setOptional($cohort,$data,'slug',BCValidator::SLUG);
        $cohort = $this->setOptional($cohort,$data,'streaming_slug',BCValidator::SLUG);
        $cohort = $this->setOptional($cohort,$data,'syllabus_slug',BCValidator::SLUG);
        $cohort = $this->setOptional($cohort,$data,'current_day',BCValidator::INT);
        $cohort = $this->setOptional($cohort,$data,'language',BCValidator::SLUG);
        $cohort = $this->setOptional($cohort,$data,'slack_url',BCValidator::URL);
        $cohort = $this->setOptional($cohort,$data,'meeting_url',BCValidator::URL);

        if(isset($data['stage'])){
            if($data['stage'] !== 'not-started' && empty($cohort->kickoff_date))
                $cohort = $this->setMandatory($cohort,$data,'kickoff_date',BCValidator::DATETIME);
            else $cohort = $this->setOptional($cohort,$data,'kickoff_date',BCValidator::DATETIME);

            if($data['stage'] !== 'not-started' && empty($cohort->kickoff_date))
                $cohort = $this->setMandatory($cohort,$data,'ending_date',BCValidator::DATETIME);
            else $cohort = $this->setOptional($cohort,$data,'ending_date',BCValidator::DATETIME);
        }

        $cohort->save();

        if(isset($data['stage'])){
            if($data['stage'] === 'finished'){
                $students = $cohort->students()->get();
                foreach($students as $student){
                    if($student->status == 'currently_active'){
                        $student->status = 'studies_finished';
                        $student->save();
                    }
                }
            }
        }

        return $this->success($response,$cohort);
    }

    public function updateCohortDayHandler(Request $request, Response $response) {
        $cohortId = $request->getAttribute('cohort_id');
        $data = $request->getParsedBody();
        if(!$data) throw new ArgumentException('There was an error parsing the request information (JSON)');

        $cohort = Cohort::find($cohortId);
        if(!$cohort) throw new ArgumentException('Invalid cohort id: '.$cohortId);

        $cohort = $this->setMandatory($cohort,$data,'current_day',BCValidator::INT);
        $cohort->save();

        return $this->success($response,$cohort);
    }

    public function deleteCohortHandler(Request $request, Response $response) {
        $cohortId = $request->getAttribute('cohort_id');

        $cohort = Cohort::find($cohortId);
        if(!$cohort) throw new ArgumentException('Invalid cohort id');

        $students = $cohort->students()->get();
        $totalStudents = count($students);
        if($totalStudents>0) throw new ArgumentException('Remove all the '.$totalStudents.' students from the cohort first.');

        $cohort->delete();

        return $this->success($response,"The cohort was deleted successfully");
    }

    public function getCohortStudentsHandler(Request $request, Response $response) {

        $cohortId = $request->getAttribute('cohort_id');

        $cohort = null;
        if(is_numeric($cohortId)) $cohort = Cohort::find($cohortId);
        else $cohort = Cohort::where('slug', $cohortId)->first();
        if(!$cohort) throw new ArgumentException('Invalid cohort id or slug:'.$cohortId);

        $students = $cohort->students()->get();

        return $this->success($response,$students);
    }

    public function addStudentToCohortHandler(Request $request, Response $response) {
        $cohortId = $request->getAttribute('cohort_id');

        $studentsArray = $request->getParsedBody();
        if(empty($studentsArray)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        $cohort = null;
        if(is_numeric($cohortId)) $cohort = Cohort::find($cohortId);
        else $cohort = Cohort::where('slug', $cohortId)->first();
        if(!$cohort) throw new ArgumentException('Invalid cohort slug or id: '.$cohortId);
        $auxStudents = [];
        foreach($studentsArray as $stu) $auxStudents[] = $stu['student_id'];
        if($auxStudents>0) $cohort->students()->attach($auxStudents);
        else throw new ArgumentException('Error retreving Students form the body request');

        return $this->success($response,"There are ".$cohort->students()->count()." students in the cohort.");
    }

    public function deleteStudentFromCohortHandler(Request $request, Response $response) {
        $cohortId = $request->getAttribute('cohort_id');

        $studentsArray = $request->getParsedBody();
        if(empty($studentsArray)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        $cohort = null;
        if(is_numeric($cohortId)) $cohort = Cohort::find($cohortId);
        else $cohort = Cohort::where('slug', $cohortId)->first();
        if(!$cohort) throw new ArgumentException('Invalid cohort slug or id: '.$cohortId);

        $auxStudents = [];
        foreach($studentsArray as $stu) $auxStudents[] = $stu['student_id'];
        if($auxStudents>0) $cohort->students()->detach($auxStudents);
        else throw new ArgumentException('Error retreving Students form the body request');

        return $this->success($response,"There are now ".$cohort->students()->count()." students in the cohort.");
    }

    public function addTeacherToCohortHandler(Request $request, Response $response) {
        $cohortId = $request->getAttribute('cohort_id');

        $teachersArray = $request->getParsedBody();
        if(empty($teachersArray)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        //there can only be a max of one main instructor
        $mainInstructors = [];
        foreach($teachersArray as $t) if(isset($t['is_instructor']) && $t['is_instructor']=='true') $mainInstructors[] = $t['teacher_id'];
        if(count($mainInstructors)>1) throw new ArgumentException('There can only be one main instructor');


        $cohort = Cohort::find($cohortId);
        if(!$cohort) throw new ArgumentException('Invalid cohort id: '.$cohortId);

        $auxTeachers = [];
        $currentTeachers = $cohort->teachers()->get();
        foreach($teachersArray as $tea) {
            $teacher = Teacher::find($tea['teacher_id']);
            if(!$teacher) throw new ArgumentException('Invalid teacher id: '.$tea['teacher_id']);
            if(!$currentTeachers->contains($tea['teacher_id'])) $auxTeachers[] = $tea['teacher_id'];
        }

        //add the new teachers only
        if($auxTeachers>0) $cohort->teachers()->attach($auxTeachers);
        else throw new ArgumentException('Error retreving Teachers form the body request');

        foreach($currentTeachers as $ct){
            $cohort->teachers()->updateExistingPivot($ct->id, ['is_instructor'=>false]);
        }

        if(isset($mainInstructors[0])) $cohort->teachers()->updateExistingPivot($mainInstructors[0], ['is_instructor'=>true]);

        return $this->success($response,$cohort->teachers()->get());
    }

    public function deleteTeacherFromCohortHandler(Request $request, Response $response) {
        $cohortId = $request->getAttribute('cohort_id');

        $teachersArray = $request->getParsedBody();
        if(empty($teachersArray)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        $cohort = Cohort::find($cohortId);
        if(!$cohort) throw new ArgumentException('Invalid cohort id: '.$cohortId);

        $auxTeachers = [];
        foreach($teachersArray as $tea) {
            $teacher = Teacher::find($tea['teacher_id']);
            if(!$teacher) throw new ArgumentException('Invalid teacher id: '.$tea['teacher_id']);
            $auxTeachers[] = $tea['teacher_id'];
        }

        if($auxTeachers>0) $cohort->teachers()->detach($auxTeachers);
        else throw new ArgumentException('Error deleting teachers');

        return $this->success($response,"There are ".$cohort->teachers()->count()." teachers in the cohort.");
    }
}