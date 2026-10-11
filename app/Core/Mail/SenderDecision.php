<?php

namespace App\Core\Mail;

enum SenderDecision: string
{
    case Accept = 'accept';
    case NotAllowed = 'not_allowed';
    case Unauthenticated = 'unauthenticated';
}
