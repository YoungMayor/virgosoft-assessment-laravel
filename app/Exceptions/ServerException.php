<?php

namespace App\Exceptions;

use Exception;

class ServerException extends Exception
{
    protected $code = 500;
}
