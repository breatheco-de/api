<?php

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Helpers\BCValidator;
use Helpers\ArgumentException;

class BadgeHandler extends MainHandler{
    
    protected $slug = 'Badge';
    
    public function getSingleBadge(Request $request, Response $response) {
        $badgeId = $request->getAttribute('badge_id');
        
        $badge = null;
        if(is_numeric($badgeId)) $badge = Badge::find($badgeId);
        else $badge = Badge::where('slug', $badgeId)->first();
        if(!$badge) throw new ArgumentException('Invalid badge slug or id: '.$badgeId);
        
        return $this->success($response,$badge);
    }

    public function getAllStudentBadgesHandler(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');

        $badges = $this->app->db->table('badges')
        ->join('badge_student','badge_student.badge_id','=','badges.id')
        ->where('badge_student.student_user_id',$studentId)
        ->select('badges.*','badge_student.is_achieved','badge_student.points_acumulated')->get();
        if(!$badges) throw new ArgumentException('Invalid student id');
        
        return $this->success($response,$badges);
    }
    
    public function createOrUpdateBadgeHandler(Request $request, Response $response) {
        $badgeId = $request->getAttribute('badge_id');
        
        $data = $request->getParsedBody();
        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');
        
        if($badgeId){
            if(is_numeric($badgeId)) $badge = Badge::find($badgeId);
            else $badge = Badge::where('slug', $badgeId)->first();
            if(!$badge) throw new ArgumentException('Invalid badge slug or id: '.$badgeId);
        
            $badge = $this->setOptional($badge,$data,'slug',BCValidator::SLUG);
            $badge = $this->setOptional($badge,$data,'name');
            $badge = $this->setOptional($badge,$data,'technologies');
            $badge = $this->setOptional($badge,$data,'description');
            $badge = $this->setOptional($badge,$data,'points_to_achieve');
            
        } 
        else{
            $badge = Badge::where('slug', $data['slug'])->first();
            if($badge) throw new ArgumentException('There is already a badge with slug: '.$data['slug']);
            
            $badge = new Badge();
            $imageUrl = $this->uploadThumb($badge,$request);
            if(!$imageUrl) $imageUrl = PUBLIC_URL.'img/badge/rand/chevron-'.rand(1,21).'.png';
            
            $badge = $this->setMandatory($badge,$data,'slug',BCValidator::SLUG);
            $badge->name = $data['name'];
            $badge->icon = $imageUrl;
            $badge->points_to_achieve = $data['points_to_achieve'];
            $badge->description = $data['description'];
            $badge->technologies = $data['technologies'];
        }
        
        $badge->save();
        
        return $this->success($response,$badge);
    }
    
    private function uploadThumb($badge,$request){
        $files = $request->getUploadedFiles();
        //print_r($files); die();
        if (empty($files['thumb'])) return false;

        if(is_dir(PUBLIC_URL))
        {
            if(!is_dir(PUBLIC_URL.'img/')) mkdir(PUBLIC_URL.'img/');
            if(!is_dir(PUBLIC_URL.'img/badge/')) mkdir(PUBLIC_URL.'img/badge/');

            $destination = PUBLIC_URL.'img/badge/';
            if(is_dir($destination))
            {
                $newfile = $files['thumb'];
                
                $oldName = $newfile->getClientFilename();
                $name_parts = explode(".", $oldName);
                $ext = end($name_parts);
                if(!in_array($ext, VALID_IMG_EXTENSIONS)) throw new ArgumentException('Invalid image thumb extension: '.$ext);
                
                $newURL = $destination.$badge->slug.'.'.$ext;
                //print_r($newURL); die();
                $newfile->moveTo($newURL);
                return $newURL;
                
            }else throw new ArgumentException('Invalid thumb file destination: '.$destination);
            
        }else throw new ArgumentException('Invalid PUBLIC_URL destination: '.PUBLIC_URL);
        
        return false;
    }
    
    public function updateThumbHandler(Request $request, Response $response) {
        $badgeId = $request->getAttribute('badge_id');
        
        $badge = Badge::find($badgeId);
        if(!$badge) throw new ArgumentException('Invalid badge id: '.$badgeId);
        
        $imageUrl = $this->uploadThumb($badge,$request);
        if(empty($imageUrl)) throw new ArgumentException('Unable to upload thumb');
        $badge->icon = substr($imageUrl,2);
        $badge->save();
        
        return $this->success($response,$badge);
    }

    public function deleteBadgeHandler(Request $request, Response $response) {
        $badgeId = $request->getAttribute('badge_id');
        
        $badge = Badge::find($badgeId);
        if(!$badge) throw new ArgumentException('Invalid badge id: '.$badgeId);
        /*
        $attributes = $badge->getAttributes();
        $now = time(); // or your date as well
        $daysOld = floor(($now - strtotime($attributes['created_at'])) / DELETE_MAX_DAYS);
        if($daysOld>5) throw new ArgumentException('The badge is too old to delete');
        */
        $badge->delete();
        
        return $this->success($response,"ok");
    }
    
    public function addBadgesToSpecialtyHandler(Request $request, Response $response) {
        $specialtyId = $request->getAttribute('specialty_id');
        
        $badgesObj = $request->getParsedBody();
        if(empty($badgesObj['badges'])) throw new ArgumentException('There was an error retrieving the badges');
        
        $specialty = Specialty::find($specialtyId);
        if(!$specialty) throw new ArgumentException('Invalid specialty id: '.$specialtyId);
        
        $defenitiveBadges = [];
        $currentBadges = $specialty->badges()->get();
        foreach($badgesObj['badges'] as $badgeId) {
            $badge = Badge::find($badgeId);
            if(!$badge) throw new ArgumentException('Invalid badge id: '.$badgeId);
            if(!$currentBadges->contains($badgeId)) $defenitiveBadges[] = $badgeId;
        }
        
        if($defenitiveBadges>0) $specialty->badges()->attach($defenitiveBadges);
        
        return $this->success($response,$specialty);
    }
    
    public function deleteBadgesFromSpecialtyHandler(Request $request, Response $response) {
        $specialtyId = $request->getAttribute('specialty_id');
        
        $badgesObj = $request->getParsedBody();
        if(empty($badgesObj['badges'])) throw new ArgumentException('There was an error retrieving the badges');
        foreach($badgesObj['badges'] as $badgeId)
        {
            $badge = Badge::find($badgeId);
            if(!$badge) throw new ArgumentException('There is no badge with ID '.$badgeId);
        }
        
        $specialty = Specialty::find($specialtyId);
        if(!$specialty) throw new ArgumentException('Invalid specialty id: '.$specialtyId);
        
        if($badgesObj['badges']>0) $specialty->badges()->detach($badgesObj['badges']);
        else throw new ArgumentException('The badges array is empty');
        
        return $this->success($response,$specialty);
    }
    
}