<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CommentFlaggedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'Comment not found.');
    }
}
