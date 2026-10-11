<?php

namespace App\Core\Registration;

enum DomainClass: string
{
    case Invalid = 'invalid';       // not a syntactically valid e-mail address
    case OwnMailDomain = 'own';     // the service's own mailbox domain (or a subdomain of it)
    case Freemail = 'freemail';     // freemail / disposable domain (or a subdomain of one)
    case Approved = 'approved';     // exact match on the approved organisation domains
    case Unknown = 'unknown';       // needs admin approval
}
