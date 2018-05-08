<?php

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Helpers\BCValidator;
use Helpers\ArgumentException;

class AtemplateHandler extends MainHandler{
    
    protected $slug = 'Atemplate';
    
    public function createHandler(Request $request, Response $response) {
        $data = $request->getParsedBody();
        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        if(isset($data['wp_id'])){
            $at = Atemplate::where('wp_id', $data['wp_id'])->first();
            if($at) throw new ArgumentException('There is already a Project Template with this wp_id: '.$data['wp_id']);
        } 

        if(isset($data['project_slug'])){
            $at = Atemplate::where('project_slug', $data['project_slug'])->first();
            if($at) throw new ArgumentException('There is already a Project Template with project_slug '.$data['project_slug']);
        } 
        
        if(!empty($data['difficulty']) && !in_array($data['difficulty'], Atemplate::$possibleDifficulties))
            throw new ArgumentException('Invalid difficulty: '.$data['difficulty']);
        //print_r($data); die();
        $at = new Atemplate();
        $at = $this->setMandatory($at,$data,'project_slug', BCValidator::SLUG);
        $at = $this->setMandatory($at,$data,'difficulty');
        $at = $this->setMandatory($at,$data,'title');
        $at = $this->setMandatory($at,$data,'duration');
        $at = $this->setMandatory($at,$data,'technologies');
        $at = $this->setOptional($at,$data,'excerpt');
        $at = $this->setOptional($at,$data,'wp_id');
        $at->save();
        
        return $this->success($response,$at);
    }
    
    public function syncFromWPHandler(Request $request, Response $response) {
        $wp_id = $request->getAttribute('wp_id');
        
        $data = $request->getParsedBody();
        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');

        $at = Atemplate::where('wp_id', $wp_id)->first();
        if(!empty($data['project_slug']) && (!$at || $at->project_slug != $data['project_slug']))
        {
            $otherAt = Atemplate::where('project_slug', $data['project_slug'])->first();
            if($otherAt) throw new ArgumentException('There is another template with this slug: '.$data['project_slug']);
        }
        
        if(!$at){
            $at = new Atemplate();
            $at->project_slug = $data['project_slug'];
            $at->title = $data['title'];
            $at->duration = $data['duration'];
            $at->technologies = $data['technologies'];
            $at->wp_id = $wp_id;
        }
        else{
            $at = $this->setOptional($at,$data,'project_slug');
            $at = $this->setOptional($at,$data,'title');  
            $at = $this->setOptional($at,$data,'duration');  
        }
        
        $at = $this->setOptional($at,$data,'excerpt');
        $at->save();
        
        return $this->success($response,$at);
    }
    
    public function updateHandler(Request $request, Response $response) {
        $atId = $request->getAttribute('atemplate_id');
        $data = $request->getParsedBody();
        
        $at = Atemplate::find($atId);
        if(!$at){
            $at = Atemplate::where('project_slug', $data['project_slug'])->first();
            if(!$at) throw new ArgumentException('Invalid template id or slug: '.$atId);
        }
        
        if(!empty($data['difficulty']) && !in_array($data['difficulty'], Atemplate::$possibleDifficulties))
            throw new ArgumentException('Invalid difficluty: '.$data['difficulty']);

        $at = $this->setOptional($at,$data,'project_slug', BCValidator::SLUG);
        $at = $this->setOptional($at,$data,'title');
        $at = $this->setOptional($at,$data,'excerpt');
        $at = $this->setOptional($at,$data,'difficulty');
        $at = $this->setOptional($at,$data,'duration');
        $at = $this->setOptional($at,$data,'wp_id');
        $at = $this->setOptional($at,$data,'technologies');
        $at->save();
        
        return $this->success($response,$at);
    }
    
    public function deleteHandler(Request $request, Response $response) {
        $atId = $request->getAttribute('atemplate_id');
        
        $at = Atemplate::find($atId);
        if(!$at) throw new ArgumentException('Invalid template id: '.$atId);
        
        $assingments = $at->assignments()->get();
        if(count($assingments)>0) throw new ArgumentException('The template cannot be deleted because it has assingments');
        
        $at->delete();
        
        return $this->success($response,"The template was deleted");
    }
    
}