<?php

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Helpers\BCValidator;
use Helpers\ArgumentException;

class TaskHandler extends MainHandler{

    protected $slug = 'Task';


    public function getAllTasksHandler(Request $request, Response $response) {

        $studentId = $request->getQueryParam('student', null);
        $cohortId = $request->getQueryParam('cohort', null);
        $teacherId = $request->getQueryParam('teacher', null);
        $type = $request->getQueryParam('type', null);


        $query = $this->app->db->table('tasks')
                    ->join('cohort_student','cohort_student.student_user_id','=','tasks.student_user_id')
                    ->join('students','students.user_id','=','cohort_student.student_user_id');

        if(isset($cohortId)) $query = $query->where('cohort_student.cohort_id',$cohortId);
        if(isset($teacherId)){
            $query = $query->join('cohort_teacher','cohort_student.cohort_id','=','cohort_teacher.cohort_id');
            $query = $query->where('cohort_teacher.teacher_user_id',$teacherId);
        }
        if(isset($studentId)) $query = $query->where('tasks.student_user_id',$studentId);
        if(isset($type)) $query = $query->where('tasks.type',$type);
        $tasks = $query->select('tasks.*')->get();

        $tasks = (array) collect($tasks)->toArray();
        return $this->success($response,Task::hydrate($tasks)->all());
    }

    public function getAllStudentTasksHandler(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');

        $student = Student::find($studentId);
        if(!$student){
            $user = User::where('username', $studentId)->first();
            if(!$user) throw new ArgumentException('Invalid student id');
            $student = Student::find($user->id);
            if(!$student) throw new ArgumentException('Invalid student id');
        }
        

        return $this->success($response,$student->tasks()->get());
    }

    public function deleteAllStudentTasksHandler(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');

        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id');

        $tasks = $student->tasks()->get();
        foreach($tasks as $t) $t->delete();

        return $this->success($response,'ok');
    }

    public function createTaskHandler(Request $request, Response $response) {

        $studentId = $request->getAttribute('student_id');
        if(!$studentId) throw new ArgumentException('Invalid student id');

        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id');

        $data = $request->getParsedBody();
        if(!is_array($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        $result = null;
        if(isset($data[0])) $result = $this->_addMultipleTodo($student, $data);
        else if(!empty($data)) $result = $this->_addSingleTodo($student, $data);
        else $result = "Nothing to add";

        return $this->success($response,$result);
    }

    public function updateTaskHandler(Request $request, Response $response) {
        $taskId = $request->getAttribute('task_id');

        $data = $request->getParsedBody();
        if(!$data)  throw new ArgumentException('Error parsing the request (invalid JSON)');

        $task = Task::find($taskId);
        if(!$task) throw new ArgumentException('Invalid task id');

        if(!in_array($data['status'],Task::$possibleStages)) throw new ArgumentException("Invalid status ".$data['status'].", the only valid status are: ".implode(',',Task::$possibleStages));

        try{
            if($task->type === 'assignment' and $data['status'] === 'done'){
                if(empty($data['github_url'])) throw new ArgumentException("To mark an assignment as done, you need to specify the github url");
                $task->github_url = $data['github_url'];
                $task->revision_status = Task::$revisionStages[0]; //pending
            }

            $task->status = $data['status'];
            $task->save();

        }
        catch(ArgumentException $e){
            throw $e;
        }

        return $this->success($response,$task);
    }

    public function deleteTaskHandler(Request $request, Response $response) {
        $taskId = $request->getAttribute('task_id');

        $task = Task::find($taskId);
        if(!$task) throw new ArgumentException('Invalid task id');

        $task->delete();

        return $this->success($response,"The task was successfully deleted.");
    }

    private function _addSingleTodo($student, $data){

        $tasks = $this->app->db->table('tasks')
        ->where([
            'tasks.student_user_id' => $student->user_id,
            'tasks.associated_slug' => $data['associated_slug'],
            'tasks.type' => $data['type']
        ])->select('tasks.id')->get();
        if(count($tasks)>0) throw new ArgumentException("There is already a task for this resource '".$data['associated_slug']."' and the student '".$student->user_id."'");

        if(!in_array($data['type'],Task::$possibleTypes)) throw new ArgumentException("Invalid type ".$data['type'].", the only valid types are: ".implode(',',Task::$possibleTypes));

        $task = new Task();
        $task = $this->setMandatory($task,$data,'associated_slug',BCValidator::SLUG);
        $task = $this->setMandatory($task,$data,'type',BCValidator::SLUG);
        $task = $this->setMandatory($task,$data,'title',BCValidator::DESCRIPTION);
        $task = $this->setOptional($task,$data,'description',BCValidator::DESCRIPTION);
        $task->status = 'pending';
        $task->student()->associate($student->user_id);

        $task->save();

        return $task;
    }

    private function _addMultipleTodo($student, $todos){
        $results = [];
        if(count($todos)==0) return $results;
        foreach($todos as $singleTodo){
            try{
                $results[] = $this->_addSingleTodo($student, $singleTodo);
            }
            catch(ArgumentException $e){
                foreach($results as $task) $task->delete();
                throw $e;
            }
        }

        return $results;
    }

}
