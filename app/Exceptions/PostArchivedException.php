<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class PostArchivedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(410, 'Post is archived.');
    }
}
