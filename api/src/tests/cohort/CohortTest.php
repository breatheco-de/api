<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

class CohortTest extends BaseTestCase {
 
    protected $app;
    public function setUp()
    {
        parent::setUp();
        $this->app->addRoutes(['cohort']);
        $this->app->addRoutes(['location']);
        $this->app->addRoutes(['profile']);
        $this->app->addRoutes(['teacher']);
    }

    function testCreateLocation(){
        $body = [
            "name" => "Caracas - Venezuela",
            "slug" => "nuevalocation-cohort",
            "address" => "Av Caracas",
            "country" => "Venezuela"
        ];
        $location = $this->mockAPICall(['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/location/'], $body)
            ->expectSuccess()
            ->getParsedBody();
    }

    function testCreateProfile(){
        $body = [
            "slug" => "nuevoprofile-cohort",
            "name" => "Desarrollador",
            "description" => "Web Developer"
        ];
        $profile = $this->mockAPICall(['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/profile/'], $body)
            ->expectSuccess()
            ->getParsedBody();
    }

    function testCreateCohort(){
        $body = [
            "location_slug" => "nuevalocation-cohort",
            "profile_slug" => "nuevoprofile-cohort",
            "name" => "Nuevo Cohort",
            "slug" => "nuevocohort-cohort",
            "language" => "es",
            "slack_url" => "http://www.asidj.com",
            "kickoff_date" => "2017-04-10"
        ];
        $cohort = $this->mockAPICall(['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/cohort/'], $body)
            ->expectSuccess()
            ->getParsedBody();

        return $cohort->data;
    }

    /*function testCreateDoubleCohort(){
        $body = [
            "location_slug" => "nuevalocation-cohort",
            "profile_slug" => "nuevoprofile-cohort",
            "name" => "Nuevo Cohort",
            "slug" => "nuevocohort-cohort",
            "language" => "es",
            "slack_url" => "http://www.asidj.com",
            "kickoff_date" => "2017-04-10"
        ];
        $cohort = $this->mockAPICall(['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/cohort/'], $body)
            ->expectFailure()
            ->getParsedBody();
    }*/

    function testCreateCohortLanguageChrSpecial(){
        $body = [
            "location_slug" => "nuevalocation-cohort",
            "profile_slug" => "nuevoprofile-cohort",
            "name" => "Nuevo Cohort",
            "slug" => "nuevocohort-cohort",
            "language" => "es!@#",
            "slack_url" => "http://www.asidj.com",
            "kickoff_date" => "2017-04-10"
        ];
        $cohort = $this->mockAPICall(['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/cohort/'], $body)
            ->expectFailure()
            ->getParsedBody();
    }

    function testCreateCohortSlugChrSpecial(){
        $body = [
            "location_slug" => "nuevalocation-cohort",
            "profile_slug" => "nuevoprofile-cohort",
            "name" => "Nuevo Cohort",
            "slug" => "nuevocohort-cohort!@#",
            "language" => "es",
            "slack_url" => "http://www.asidj.com",
            "kickoff_date" => "2017-04-10"
        ];
        $cohort = $this->mockAPICall(['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/cohort/'], $body)
            ->expectFailure()
            ->getParsedBody();
    }

    function testCreateCohortDateChrSpecial(){
        $body = [
            "location_slug" => "nuevalocation-cohort",
            "profile_slug" => "nuevoprofile-cohort",
            "name" => "Nuevo Cohort",
            "slug" => "nuevocohort-cohort",
            "language" => "es",
            "slack_url" => "http://www.asidj.com",
            "kickoff_date" => "2017-04-10!@#"
        ];
        $cohort = $this->mockAPICall(['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/cohort/'], $body)
            ->expectFailure()
            ->getParsedBody();
    }

    /**
     * @depends testCreateCohort
     */
    function testUpdateCohort($cohort){
        $body = [
            "location_slug" => "nuevalocation-cohort",
            "profile_slug" => "nuevoprofile-cohort",
            "name" => "Update Cohort",
            "slug" => "updatecohort-cohort",
            "language" => "es",
            "slack_url" => "http://www.holamundo.com",
            "kickoff_date" => "2017-04-10"
        ];
        $cohort = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/cohort/'.$cohort->id], $body)
            ->expectSuccess()
            ->getParsedBody();
    }

    /**
     * @depends testCreateCohort
     */
    function testUpdateSlugChrSpecial($cohort){
        $body = [
            "location_slug" => "nuevalocation-cohort",
            "profile_slug" => "nuevoprofile-cohort",
            "name" => "Update Cohort",
            "slug" => "updatecohort-cohort!@#",
            "language" => "es",
            "slack_url" => "http://www.holamundo.com",
            "kickoff_date" => "2017-04-10"
        ];
        $cohort = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/cohort/'.$cohort->id], $body)
            ->expectFailure()
            ->getParsedBody();
    }

    /**
     * @depends testCreateCohort
     */
    function testUpdateCohortIdEmpty($cohort){
        $body = [
            "location_slug" => "nuevalocation-cohort",
            "profile_slug" => "nuevoprofile-cohort",
            "name" => "",
            "slug" => "",
            "language" => "",
            "slack_url" => "",
            "kickoff_date" => ""
        ];
        $cohort = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/cohort/'.$cohort->id], $body)
            ->expectFailure()
            ->getParsedBody();
    }

    /**
     * @depends testCreateCohort
     */
    function testDeleteCohort($cohort){
        $cohort = $this->mockAPICall(['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/cohort/'.$cohort->id])
            ->expectSuccess()
            ->getParsedBody();
    }

    /**
     * @depends testCreateCohort
     */
    function testDeletedCohort($cohort){
        $cohort = $this->mockAPICall(['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/cohort/'.$cohort->id])
            ->expectFailure()
            ->getParsedBody();
    }

    function testCreateTeacher(){
        $body = [
            "email" => "teacherCohort@4geeks.com",
            "full_name" => "Prof Cohort",
        ];
        $teacher = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/teacher/'], $body)
            ->expectSuccess()
            ->withPropertiesAndValues([
                "email" => $body["email"],
                "full_name" => $body["full_name"]])
            ->getParsedBody();
    }

    // function testCreateTeacher(){
    //     $body = [
    //         "email" => "teacherCohort@4geeks.com",
    //         "full_name" => "Prof Cohort",
    //     ];
    //     $teacher = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/teacher/'], $body)
    //         ->expectSuccess()
    //         ->getParsedBody();
        
    //     return $teacher->data;
    // }

    /**
     * @depends testCreateTeacher
     * @depends testCreateCohort
     */
    /*function testCreateTeacherCohort($teacher, $cohort){
        $body = [
            "teacher_id" => $teacher->id,
            "is_instructor" => true,
        ];
        $cohort = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/teacher/cohort/'.$cohort->id], $body)
            ->expectSuccess()
            ->getParsedBody();
    }*/

}