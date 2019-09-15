<?php

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Helpers\BCValidator;
use Helpers\ArgumentException;

class ProfileHandler extends MainHandler{

    protected $slug = 'Profile';

    public function getSingleProfile(Request $request, Response $response) {
        $profileId = $request->getAttribute('profile_id');

        $profile = null;
        if(is_numeric($profileId)) $profile = Profile::find($profileId);
        else $profile = Profile::where('slug', $profileId)->first();
        if(!$profile) throw new ArgumentException('Invalid profile slug or id: '.$profileId);

        return $this->success($response,$profile);
    }

    public function createProfileHandler(Request $request, Response $response) {

        $data = $request->getParsedBody();
        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        //if(count($data['specialties'])<2) throw new ArgumentException('A profile must be created with at least two specialties');

        $profile = new Profile();
        $specialties = [];

        if(!empty($data['specialties']))
        foreach($data['specialties'] as $bslug)
        {
            $specialty = Specialty::where('slug', $bslug)->first();
            if($specialty) $specialties[] = $specialty->id;
            else throw new ArgumentException('Invalid specialty slug: '.$bslug);

            $specialties[] = $bslug;
        }
        $profile = $this->setMandatory($profile,$data,'slug',BCValidator::SLUG);
        $profile = $this->setMandatory($profile,$data,'name',BCValidator::NAME);
        $profile = $this->setOptional($profile,$data,'logo',BCValidator::NAME);
        $profile = $this->setMandatory($profile,$data,'duration_in_days',BCValidator::POINTS);
        $profile = $this->setMandatory($profile,$data,'duration_in_hours',BCValidator::POINTS);
        $profile = $this->setMandatory($profile,$data,'week_hours',BCValidator::POINTS);
        $profile = $this->setMandatory($profile,$data,'description',BCValidator::DESCRIPTION);
        $profile->save();

        if(!empty($data['specialties'])){
            try{
                $profile->specialties()->attach($specialties);
            }
            catch(ArgumentException $e)
            {
                $profile->delete();
                throw new ArgumentException($e->getMessage());
            }
        }
        return $this->success($response,$profile);
    }

    public function updateProfileHandler(Request $request, Response $response) {
        $profileId = $request->getAttribute('profile_id');

        $data = $request->getParsedBody();
        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        if(is_numeric($profileId)) $profile = Profile::find($profileId);
        else $profile = Profile::where('slug', $profileId)->first();
        if(!$profile) throw new ArgumentException('Invalid profile slug or id: '.$profileId);

        if(is_numeric($profileId)) $profile->slug = $data['slug'];
        $profile = $this->setOptional($profile,$data,'slug',BCValidator::SLUG);
        $profile = $this->setOptional($profile,$data,'name',BCValidator::NAME);
        $profile = $this->setOptional($profile,$data,'logo',BCValidator::NAME);
        $profile = $this->setOptional($profile,$data,'duration_in_hours',BCValidator::POINTS);
        $profile = $this->setOptional($profile,$data,'duration_in_days',BCValidator::POINTS);
        $profile = $this->setOptional($profile,$data,'week_hours',BCValidator::POINTS);
        $profile = $this->setOptional($profile,$data,'description',BCValidator::DESCRIPTION);

        if(!empty($data['specialties']))
        {
            if(count($data['specialties'])<2) throw new ArgumentException('A profile must have at least two specialties');
            $specialties = [];
            foreach($data['specialties'] as $bslug)
            {
                $specialty = Specialty::where('slug', $bslug)->first();
                if($specialty) $specialties[] = $specialty->id;
                else throw new ArgumentException('Invalid specialty slug: '.$bslug);
            }

            $currentSpecialties = $profile->specialties()->get();
            $profile->specialties()->detach($currentSpecialties);
            $profile->specialties()->attach($specialties);
        }

        $profile->save();

        return $this->success($response,$profile);
    }

    public function deleteProfileHandler(Request $request, Response $response) {
        $profileId = $request->getAttribute('profile_id');

        $profile = Profile::find($profileId);
        if(!$profile) throw new ArgumentException('Invalid profile id: '.$profileId);

        $specialties = $profile->specialties()->get();
        if(count($specialties)>2) throw new ArgumentException('Only profiles wit 2 or less specialties can be removed, please remove specialties from this profile first');

        $cohorts = $profile->cohorts()->get();
        if(count($cohorts)>0) throw new ArgumentException('Only profiles with no cohorts can be deleted');
        /*
        $attributes = $specialty->getAttributes();
        $now = time(); // or your date as well
        $daysOld = floor(($now - strtotime($attributes['created_at'])) / DELETE_MAX_DAYS);
        if($daysOld>5) throw new ArgumentException('The specialty is too old to delete');
        */

        $currentSpecialties = $profile->specialties()->get();
        $profile->specialties()->detach($currentSpecialties);
        $profile->delete();

        return $this->success($response,"ok");
    }

}