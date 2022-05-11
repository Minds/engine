<?php
namespace Minds\Common;

use Psr\Http\Message\RequestInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;

class IpAddress
{
    /** @var RequestInterface */
    protected $serverRequest;

    public function __construct()
    {
        $this->serverRequest = ServerRequestFactory::fromGlobals();
    }

    /**
     * @param RequestInterface $request
     * @return IpAddress
     */
    public function setServerRequest(RequestInterface $request): IpAddress
    {
        $ipAddress = clone $this;
        $ipAddress->serverRequest = $request;

        return $ipAddress;
    }

    /**
     * @return string
     */
    public function get(): string
    {
        $ipHeader = $this->serverRequest->getHeader('X-FORWARDED-FOR');
        if (is_array($ipHeader) && count($ipHeader)) {
            $ip = strtok($ipHeader[0], ",");
        } else {
            $ip = 'local';
        }
        return $ip;
    }

    /**
     * Get IP address as hash.
     * @return string hash of IP.
     */
    public function getHash(): string
    {
        return hash('sha256', $this->get());
    }
}
