<?php

class Cohort extends \Illuminate\Database\Eloquent\Model
{
    protected $hidden = ['pivot'];
    protected $appends = ['location_slug','teachers','profile_slug', 'profile'];

    public static $possibleStages = ['not-started', 'on-prework', 'on-course','on-final-project','finished'];

    public function setStageAttribute($value){
        if(!in_array($value, self::$possibleStages)) throw new Exception('Invalid stage value: '.$value);

        $this->attributes['stage'] = strtolower($value);
    }

    public function getTeachersAttribute(){
        return $this->teachers()->get()->pluck('id');
    }


    public function getFullTeachersAttribute(){
        return $this->teachers()->get();
    }

    public function getLocationSlugAttribute(){
        if($location = $this->location()->first()) return $location->slug;
        else return null;
    }

    public function getProfileSlugAttribute(){
        if($profile = $this->profile()->first()) return $profile->slug;
        return null;
    }

    public function getProfileAttribute(){
        if($profile = $this->profile()->first()) return $profile;
        return null;
    }

    public function students(){
        $students = $this->belongsToMany('Student','cohort_student','cohort_id','student_user_id')->withTimestamps();
        return $students;
    }

    public function location(){
        return $this->belongsTo('Location');
    }

    public function teachers(){

        $teachers = $this->belongsToMany('Teacher','cohort_teacher','cohort_id','teacher_user_id')->withPivot('is_instructor')->withTimestamps();
        return $teachers;
    }

    public function profile(){
        return $this->belongsTo('Profile');
    }

    public function setStatusAttribute($value)
    {
        if(in_array($value, ['not-started', 'on-prework', 'post-prework','final-project','finished'])) $this->attributes['status'] = strtolower($value);
        else throw new Exception('Invalid cohort stage: '.$value);
    }

}