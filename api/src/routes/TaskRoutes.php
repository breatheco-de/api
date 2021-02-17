<?php

namespace Routes;

class TaskRoutes{

    public function __construct($app, $scopes){

        $handler = new \TaskHandler($app);
        $app->get('/task/', array($handler, 'getAllTasksHandler'))->add($scopes(['read_basic_info']));
        $app->get('/student/{student_id}/task/', array($handler, 'getAllStudentTasksHandler'));//->add($scopes(['read_basic_info']));
        $app->post('/student/{student_id}/task/', array($handler, 'createTaskHandler'))->add($scopes(['student_tasks']));
        $app->delete('/student/{student_id}/task/all', array($handler, 'deleteAllStudentTasksHandler'))->add($scopes(['student_tasks']));

        $app->post('/task/{task_id}', array($handler, 'updateTaskHandler'))->add($scopes(['student_tasks']));
        $app->delete('/task/{task_id}', array($handler, 'deleteTaskHandler'))->add($scopes(['student_tasks']));
    }


}
