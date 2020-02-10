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
    const INT = 'integer';
    const SLUG = 'slug';
    const DATETIME = 'datetime';
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
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value, 400);
                
            break;
            case self::SLUG:
                
                $validator = new Rules\AllOf(
                    new Rules\Alnum('. _ -'),
                    new Rules\NoWhitespace(),
                    new Rules\Length(1, 150)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value, 400);
                
            break;
            case self::USERNAME:
                
                $validator = new Rules\AllOf(
                    new Rules\Alnum(),
                    new Rules\NoWhitespace(),
                    new Rules\Length(1, 15)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value, 400);
                
            break;
            case self::DATETIME:
                
                $validator = new Rules\AllOf(
                    new Rules\Date()
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value.' format must be Y-M-D', 400);
                
            break;
            case self::PHONE:
                
                $validator = new Rules\AllOf(
                    new Rules\Phone()
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value, 400);
                
            break;
            case self::EMAIL:
                
                $validator = new Rules\AllOf(
                    new Rules\Email(),
                    new Rules\NoWhitespace(),
                    new Rules\Length(1, 255)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value, 400);
                
            break;
            case self::URL:
                
                $validator = new Rules\AllOf(
                    new Rules\Url(),
                    new Rules\NoWhitespace(),
                    new Rules\Length(0, 255)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value, 400);
                
            break;
            case self::POINTS:
                
                $validator = new Rules\AllOf(
                    new Rules\IntVal(),
                    new Rules\Length(0, 255)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value, 400);
                
            break;
            case self::INT:
                
                $validator = new Rules\AllOf(
                    new Rules\IntVal(),
                    new Rules\Length(0, 255)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value, 400);
                
            break;
            case self::DESCRIPTION:
                
                $validator = new Rules\AllOf(
                    new Rules\Length(0, 255)
                );
                if(!$validator->validate($value)) throw new Exception('Parameter '.$name.' has an invalid value: '.$value, 400);
                
            break;
            default:
                if(empty($value)) throw new Exception('Parameter '.$name.' is required', 400);
            break;
        }
        return true;
    }
    
}

class ArgumentException extends Exception{
    protected $code = 400;   
}
class NotFoundException extends Exception{
    protected $code = 404;   
}
?>
