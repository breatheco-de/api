<?php

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Helpers\BCValidator;
use Helpers\ArgumentException;

class MainHandler{

    protected $app;
    protected $slug;

    private $validProfileSlugs = [];
    private $validSpecialtySlugs = [];

    function __construct($app){

        $this->app = $app;

        if(!defined(DEBUG) || !DEBUG)
        {
            $c = $this->app->getContainer();
            $c['errorHandler'] = function ($c) {
                return array($this, 'fail');
            };
            $c['phpErrorHandler'] = function ($c) {
                return array($this, 'fail');
            };
            $c['notFoundHandler'] = function ($c) {
                return array($this, 'fail');
            };
        }
    }

    public function success ($response,$data) {

        if(!$data) $data = [];
        $successArray = array(
            "code"=> 200,
            "data"=> $data
            );

        return $response->withJson($successArray);
    }

    public function fail($request, $response, $args=null) {

        $failCode = null;
        if($args) $failCode = $args->getCode();
        if(!$failCode || !in_array($failCode,[500,400,404,401,403,501,504])) $failCode = 500;

        $message = ($args) ? $args->getMessage():'Uknown error';
        $errorArray = array( "code"=> $failCode, "msg"=>  $message );

        return $response->withJson($errorArray,$failCode);
    }

    public function setOptional($model,$data,$key,$validator=null){

        if(isset($data[$key]))
        {
            if($validator && BCValidator::validate($validator,$data[$key],$key)){
                $model[$key] = $data[$key];
            }
            else if(!$validator)
            {
                $model[$key] = $data[$key];
            }
        }

        return $model;
    }

    public function setMandatory($model,$data,$key,$validator=null){

        if(!empty($data[$key]))
        {
            if(BCValidator::validate($validator,$data[$key],$key)){
                $model->$key = $data[$key];
            }
        }
        else throw new ArgumentException('Mising parameter: '.$key);

        return $model;
    }

    public function getValidationErrors(){
        return BCValidator::getErrors();
    }

    public function getAllHandler(Request $request, Response $response) {
        $limit = $request->getQueryParam('limit',null);
        $all = call_user_func_array($this->slug . '::limit', [$limit])->get();
        //$all = ::limit(30)->offset(30)->get();
        return $this->success($response,$all);
    }

    public function getSingleHandler(Request $request, Response $response) {
        $id = $request->getAttribute(strtolower($this->slug).'_id');

        $single = call_user_func_array($this->slug . '::find',[$id]);;
        if(!$single) throw new ArgumentException('Invalid '.strtolower($this->slug).'_id');

        return $this->success($response,$single);
    }

    public function syncMainData(Request $request, Response $response) {

        $log = [];

        $fullstack = Profile::where('slug', "full-stack-web")->first();
        if(!$fullstack){
            $fullstack = new Profile();
            $fullstack->name = "Full-Stack Web Developer";
            $fullstack->slug = "full-stack-web";
            $fullstack->description = "Manages front-end and back-end side of the web";
            $fullstack->save();

            $log[] = "The profile full-stack-web was created to train Full Stack Web Developers";

        }else $log[] = "The profile full-stack-web was already created.";

        return $this->success($response,$log);
    }

    public function importBadges(Request $request, Response $response) {

        $files = $request->getUploadedFiles();
        $params = $request->getParsedBody();
        //print_r($files); die();
        if (empty($files['badges'])) throw new ArgumentException('The file parameter name needs to be "badges"');
        if(empty($params['force_update'])) throw new ArgumentException('You need to specify a force_update parameter value');
        if(empty($params['validate_specialty'])) throw new ArgumentException('You need to specify a validate_specialty parameter value');

        $forceUpdate = ($params['force_update'] == 'true') ? true : false; //si queremos actualizar incluso cuando ya el badge fue creado anteriormente
        $validateSpecialty = ($params['validate_specialty'] == 'true') ? true : false; //si queremos actualizar incluso cuando ya el badge fue creado anteriormente
        $log = $this->_loadCSV($files['badges']->getStream(),$forceUpdate, $validateSpecialty);

        return $this->success($response,$log);
    }

    public function testHeaders(Request $request, Response $response) {
        print_r($request->getHeaders()); die();
        return $this->success($response, "OK");
    }

    private function _loadCSV($content,$forceUpdate = false, $validateSpecialty = false){

        $lines = explode(PHP_EOL, $content);
        $array = array();
        foreach ($lines as $line) {
            $array[] = str_getcsv($line);
        }
		if(!$array) throw new ArgumentException('The CSV has invalid caracters or format');

		//Create the badges posts into wordpress
		$log = null;
		if($this->_validateBadges($array, $validateSpecialty)) $log = $this->_createBadges($array, $forceUpdate);

    	return $log;
    }

    function _validateBadges($badgesRows, $validateSpecialty){

		//print_r($badgesRows); die();

    	$errors = [];

    	if(!$badgesRows or count($badgesRows)==0) $errors[] = 'No badges found in the CSV or the format was incorrect';
    	if($badgesRows[0] and !isset($badgesRows[0][6])) $errors[] = 'The CSV needs to have 7 columns exactly';

    	if(count($errors)==0)
    	{
    		for($i=0;$i<count($badgesRows);$i++)
    		{
    			if($i==0) continue;//it's the header of the CSV table

    			$b = $this->_badgeRowToArray($badgesRows[$i]);

    			//validate that all the columns of the row need to have a value
    			$emptyRows = false;
    			//we loop them all, if we find one empty column we return an error
    			foreach($b as $key => $bColumn){
    			    if($key == 'badge_technologies') continue;//the only row that can be empty is badge_technologies
    			    if(!$emptyRows && empty($bColumn)) $emptyRows = true;
    			}
    			if($emptyRows) $errors[] = "Row $i columns need to have a value";


			    //If the specialty_slug has not been validated (to avoid redundant DB validations in the next rows)
    			if($validateSpecialty && (!isset($this->validSpecialtySlugs[$b['specialty_slug']]) || $this->validSpecialtySlugs[$b['specialty_slug']]==false))
    			{
    			    //If the badge is not in our database
        			$specialty = Specialty::where('slug', $b['specialty_slug'])->first();
        			if(!$specialty) $errors[] = "The specialty_slug: '".$b['specialty_slug']."' for the badge '".$b['badge_slug']."' on the row $i was not found in the Database.";
        			else{
        			    //mark the specialty_slug as valid
        			    $this->validSpecialtySlugs[$b['specialty_slug']] = $specialty;
        			}
    			}

			    //If the profile_slug has not been validated (to avoid redundant DB validations in the next rows)
    			if(!isset($this->validSpecialtySlugs[$b['profile_slug']]) || $this->validSpecialtySlugs[$b['profile_slug']]==false)
    			{
    			    //If the badge is not in our database
        			$profile = Profile::where('slug', $b['profile_slug'])->first();
        			if(!$profile) $errors[] = "The profile_slug: '".$b['profile_slug']."' for the badge '".$b['badge_slug']."' on the row $i was not found in the Database.";
        			else{
        			    //mark the profile_slug as valid
        			    $this->validProfileSlugs[$b['profile_slug']] = $profile;
        			}
    			}
    		}
    	}

    	if(count($errors)>10) throw new ArgumentException('More than 10 errors where found in the calendar, here is a few: '.$this->arrayToHTML($errors));
    	if(count($errors)>0) throw new ArgumentException('The calendar was not imported because the following erros have been found: '.$this->arrayToHTML($errors));

    	return true;
    }

    function arrayToHTML($array){
    	$content = '<ul>';
    	$i = 0;
    	while($i < count($array) and $i < 20)
    	{
    		$content .= '<li>'.$array[$i].'</li>';
    		$i++;
    	}
    	$content .= '</ul>';

    	return $content;
    }

    function _createBadges($badges, $forceUpdate = false){

    	$changes = [];
    	$changes['updated'] = 0;
    	$changes['created'] = 0;
    	$changes['ignored'] = 0;
    	$changes['repeated'] = 0;
    	$totalBadges = count($badges);
    	$repeatedSlugs = [];

    	for($i=0;$i<$totalBadges;$i++)
    	{

    		if($i==0) continue;//jump the first line, it's the header of the CSV table
    		$badgeArray = $this->_badgeRowToArray($badges[$i]);//convert into a more explicit array

    		$oldBadge = Badge::where('slug',$badgeArray['badge_slug'])->first();//get the badge from the DB
    		if(!$forceUpdate && $oldBadge!=null){//if force=false we don't want to update badges
    			$changes['ignored'] += 1;
    			continue;
    		}

    		if($oldBadge)
    		{
    			$changes['updated'] += 1;
    		}
    		//if there is not, create it.
    		else
    		{
    		    $oldBadge = new Badge();
    		    $oldBadge->slug = $badgeArray['badge_slug'];
    			$changes['created'] += 1;
    		}

    		if(!in_array($badgeArray['badge_slug'], $repeatedSlugs)){
        		$repeatedSlugs[] = $badgeArray['badge_slug'];

        		$oldBadge->name = $badgeArray['badge_title'];
        		$oldBadge->points_to_achieve = $badgeArray['badge_points'];
        		$oldBadge->description = $badgeArray['badge_description'];
        		$oldBadge->technologies = $badgeArray['badge_technologies'];
    		}else $changes['repeated']++;

    		if(!empty($this->validProfileSlugs[$badgeArray['specialty_slug']]))
    		{
                $this->validProfileSlugs[$badgeArray['specialty_slug']]->profiles()->attach();
                $this->validProfileSlugs[$badgeArray['specialty_slug']]->badges()->attach($badges);
    		}

    		$oldBadge->save();

    	}

    	$totalBadges--;
    	$log = [];
    	$log[] = $changes['ignored'].' out of '.$totalBadges.' badges where ignored (because the slugs where already in the BD and you do not want a foced update).';
    	$log[] = $changes['created'].' out of '.$totalBadges.' badges where created (because we did not find any slugs like those).';

    	if($forceUpdate) $updateReason = ' (because the updated options was enforced)';
    	$log[] = $changes['updated'].' out of '.$totalBadges.' badges where updated'.$updateReason.'.';
    	$log[] = $changes['repeated'].' out of '.$totalBadges.' badges where repeated and where added to their respectives specialties';

    	return $log;
    }

    private function _badgeRowToArray($badgeRow){
		$badgeArray['profile_slug']	    = $badgeRow[0];
		$badgeArray['specialty_slug']	= $badgeRow[1];
		$badgeArray['badge_slug']	    = $badgeRow[2];
		$badgeArray['badge_title']		= $badgeRow[3];
		$badgeArray['badge_points']     = $badgeRow[4];
		$badgeArray['badge_technologies']= $badgeRow[5];
		$badgeArray['badge_description']= $badgeRow[6];
		return $badgeArray;
    }


}