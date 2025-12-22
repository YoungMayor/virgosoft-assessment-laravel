<?php

namespace App\Exceptions;

use Exception;

class ClientException extends Exception
{
    protected $code = 400;
}
