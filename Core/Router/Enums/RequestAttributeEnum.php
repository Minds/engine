<?php
namespace Minds\Core\Router\Enums;

/**
 * Not real enums because we can't use enums as array keys
 */
class RequestAttributeEnum
{
    const PERSONAL_API_KEY = 'personal_api_key';
    const SCOPES = 'scopes';
    const USER = '_user';
    const REQUEST_HANDLER = '_request-handler';
    const CSP_NONCE = '_csp_nonce';
}
