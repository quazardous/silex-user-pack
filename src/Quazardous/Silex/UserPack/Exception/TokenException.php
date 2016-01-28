<?php
namespace Quazardous\Silex\UserPack\Exception;

class TokenException extends \RuntimeException
{
    const NOT_FOUND = 1;
    const TOO_OLD = 2;
    const ALREADY_USED = 3;
    const BAD_USE = 4;
}