<?php 

namespace Helpers;
use Respect\Validation\Rules;
use \Exception;

class BCValidator{
    
    private static $errors = [];
    const USERNAME = 'username';
    const NAME = 'name';
    const EMAIL = 'email';
    const URL = 'url';
    const SLUG = 'slug';
    const POINTS = 'points';
    const DESCRIPTION = 'description';
    const PHONE = 'phone';
    
    public static function getErrors(){
        return self::$errors;
    }
    
    public static function validate($type, $value, $name){
        
        $result = false;
        switch($type)
        {
            case self::NAME:
                
                $validator = new Rules\AllOf(
                    new Rules\StringType(),
                    new Rules\Length(1, 35)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value);
                
            break;
            case self::SLUG:
                
                $validator = new Rules\AllOf(
                    new Rules\Slug(),
                    new Rules\Length(1, 30)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value);
                
            break;
            case self::USERNAME:
                
                $validator = new Rules\AllOf(
                    new Rules\Alnum(),
                    new Rules\NoWhitespace(),
                    new Rules\Length(1, 15)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value);
                
            break;
            case self::PHONE:
                
                $validator = new Rules\AllOf(
                    new Rules\Phone()
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value);
                
            break;
            case self::EMAIL:
                
                $validator = new Rules\AllOf(
                    new Rules\Email(),
                    new Rules\NoWhitespace(),
                    new Rules\Length(1, 255)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value);
                
            break;
            case self::URL:
                
                $validator = new Rules\AllOf(
                    new Rules\Url(),
                    new Rules\NoWhitespace(),
                    new Rules\Length(0, 255)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value);
                
            break;
            case self::POINTS:
                
                $validator = new Rules\AllOf(
                    new Rules\IntVal(),
                    new Rules\Length(0, 255)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value);
                
            break;
            case self::DESCRIPTION:
                
                $validator = new Rules\AllOf(
                    new Rules\Length(0, 255)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value);
                
            break;
            default:
                throw new Exception('Invalid validation type: '.$type);
            break;
        }
        
        return true;
    }
    
}
?>