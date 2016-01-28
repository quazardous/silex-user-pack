<?php
namespace Quazardous\Silex\UserPack\Exception;

class UserRegistrationException extends \RuntimeException
{
    /**
     * By fields errors
     * @var array
     */
    protected $fieldErrors = [];
    public function setFieldErrors($errors)
    {
        $this->fieldErrors = $errors;
    }
    
    public function getFieldErrors()
    {
        return $this->fieldErrors;
    }
}