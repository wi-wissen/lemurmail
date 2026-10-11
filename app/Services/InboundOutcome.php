<?php

namespace App\Services;

/** Result of one inbound webhook. Only Stored creates a message row. */
enum InboundOutcome: string
{
    case Stored = 'stored';
    case Duplicate = 'duplicate';
    case UnknownRecipient = 'unknown_recipient';
    case NotAllowed = 'not_allowed';
    case Unauthenticated = 'unauthenticated';
}
