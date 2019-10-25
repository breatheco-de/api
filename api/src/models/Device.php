<?php

class Device extends \Illuminate\Database\Eloquent\Model
{
    public $incrementing = false;
    protected $primaryKey = 'token';

    public function user(){
        return $this->belongsTo('User');
    }
}