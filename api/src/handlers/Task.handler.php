<?php

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Helpers\BCValidator;

class TaskHandler extends MainHandler{
    
    protected $slug = 'Task';
    
    public function getAllStudentTasksHandler(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');
        
        $student = Student::find($studentId);
        if(!$student) throw new Exception('Invalid student id');
        
        return $this->success($response,$student->tasks()->get());
    }
    
    public function deleteAllStudentTasksHandler(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');
        
        $student = Student::find($studentId);
        if(!$student) throw new Exception('Invalid student id');
        
        $tasks = $student->tasks()->get();
        foreach($tasks as $t) $t->delete();
        
        return $this->success($response,'ok');
    }
    
    public function createTaskHandler(Request $request, Response $response) {
        
        $studentId = $request->getAttribute('student_id');
        if(!$studentId) throw new Exception('Invalid student id');
        
        $student = Student::find($studentId);
        if(!$student) throw new Exception('Invalid student id');
        
        $data = $request->getParsedBody();
        if(!is_array($data)) throw new Exception('There was an error retrieving the request content, it needs to be a valid JSON');

        $result = null;
        if(isset($data[0])) $result = $this->_addMultipleTodo($student, $data);
        else if(!empty($data)) $result = $this->_addSingleTodo($student, $data);
        else $result = "Nothing to add";
        
        return $this->success($response,$result);
    }
    
    public function updateTaskHandler(Request $request, Response $response) {
        $taskId = $request->getAttribute('task_id');
        
        $data = $request->getParsedBody();
        if(!$data)  throw new Exception('Error parsing the request (invalid JSON)');
        
        $task = Task::find($taskId);
        if(!$task) throw new Exception('Invalid task id');

        if(!in_array($data['status'],Task::$possibleStages)) throw new Exception("Invalid status ".$data['status'].", the only valid status are: ".implode(',',Task::$possibleStages));

        try{
            $task->status = $data['status'];
            $task->save();
        }
        catch(Exception $e){
            throw $e;
        }
        
        return $this->success($response,$task);
    }
    
    public function deleteTaskHandler(Request $request, Response $response) {
        $taskId = $request->getAttribute('task_id');
        
        $task = Task::find($taskId);
        if(!$task) throw new Exception('Invalid task id');
        
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
        if(count($tasks)>0) throw new Exception("There is already a task for this resource '".$data['associated_slug']."' and the student '".$student->user_id."'");
        
        if(!in_array($data['type'],Task::$possibleTypes)) throw new Exception("Invalid type ".$data['type'].", the only valid types are: ".implode(',',Task::$possibleTypes));
        
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
        foreach($todos as $singleTodo)
            $results[] = $this->_addSingleTodo($student, $singleTodo);

        return $results;
    }
    
}