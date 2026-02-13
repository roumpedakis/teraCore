<?php

namespace App\Core;

class ErrorCodes
{
    public const GENERIC_ERROR = 'E9000';

    public const AUTH_REQUIRED = 'E1001';
    public const AUTH_INVALID = 'E1002';

    public const MODULE_NOT_FOUND = 'E2001';
    public const ENTITY_NOT_FOUND = 'E2002';
    public const METHOD_NOT_ALLOWED = 'E2003';
    public const ADMIN_API_BLOCKED = 'E2004';
    public const ENDPOINT_NOT_FOUND = 'E2005';

    public const MODULE_NO_ACCESS = 'E3001';
    public const MODULE_INSUFFICIENT = 'E3002';
    public const MODULE_PERMISSIONS_MISSING = 'E3003';
}
