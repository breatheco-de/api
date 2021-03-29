<?php

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Helpers\ArgumentException;

class CatalogHandler extends MainHandler{
    
    public static $countries = [
        "venezuela" => ["Caracas", "Maracaibo", "Valencia"],
        "usa" => ["Miami", "Orlando", "New York"],
        "mexico" => ["Mexico City", "Guadalajara", 'Monterrey'],
        "chile" => ["Santiago"],
        "peru" => ["Lima"],
        "ecuador" => ["Quito", "Guayaquil"],
        "spain" => ["Madrid", "Barcelona", 'Galicia', 'Valencia'],
        "colombia" => ["Bogota", "Medellin"],
        "guatemala" => ["Guatemala City"],
        "uruguay" => ["San Jose", "Montevideo"],
        "costa-rica" => ["San Jose"],
        ];
    public static $technologies = ['CSS3','JS','PHP','GIT','C9','HTML5','REACT','WORDPRESS','AJAX','DJANGO','MYSQL','MONGODB'];
    
    public function getCatalog(Request $request, Response $response) {
        $catalog_slug = $request->getAttribute('catalog_slug');
        $catalog = [];
        switch($catalog_slug){
            case "technologies":
                $catalog = self::$technologies;
            break;
            case "countries":
                $catalog = $this->getAllCountries();
            break;
            case "cohort_stages":
                $catalog = Cohort::$possibleStages;
            break;
            case "student_status":
                $catalog = Student::$possibleStatus;
            break;
            case "atemplate_difficulties":
                $catalog = Atemplate::$possibleDifficulties;
            break;
            case "finantial_status":
                $catalog = Student::$possibleFinancialStatus;
            break;
            case "user_types":
                $catalog = User::$possibleTypes;
            break;
            default:
                throw new ArgumentException('Invalid catalog slug: '.$catalog_slug, 400);
            break;
        }
        return $this->success($response,$catalog);
    }  
    
    public function getAllCatalogs(Request $request, Response $response) {
        $catalogs = [
            "technologies" => self::$technologies,
            //"countries" => $this->getAllCountries(),
            "cohort_stages" => Cohort::$possibleStages,
            "student_status" => Student::$possibleStatus,
            "finantial_status" => Student::$possibleFinancialStatus,
            "user_types" => User::$possibleTypes
        ];
        return $this->success($response,$catalogs);
    }  

    private function getAllCountries() {
        $aux = [];
        foreach(self::$countries as $key => $val){
            if(!isset($aux[strtolower($key)])) $aux[strtolower($key)] = [];
            foreach(self::$countries[$key] as $val) $aux[strtolower($key)][] = ucwords($val);
        }
        
        ksort($aux);
        return $aux;
    }  
}
