<?php

namespace Minds\Core\Security;

use Minds\Helpers\Text;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Security\ProhibitedDomains;
use Minds\Exceptions\ProhibitedDomainException;

class Spam
{
    public function __construct(private ?Config $config = null)
    {
        $this->config ??= Di::_()->get(Config::class);
    }

    /**
     * Check for spam
     * @param mixed $entity
     * @return bool
     */
    public function check($entity): ?bool
    {
        $foundSpam = false;

        $domains = $this->getDomains();

        switch ($entity->getType()) {
            case 'comment':
                $foundSpam = Text::strposa($entity->getBody(), $domains);
                break;
            case 'activity':
                $foundSpam = Text::strposa($entity->getMessage(), $domains) ?:
                    Text::strposa($entity->getPermaUrl(), $domains);
                break;
            case 'object':
                if ($entity->getSubtype() === 'blog') {
                    $foundSpam = Text::strposa($entity->getBody(), $domains);
                    break;
                }
                if (method_exists($entity, 'getDescription')) {
                    $foundSpam = Text::strposa($entity->getDescription(), $domains);
                }
                break;
            case 'user':
                $foundSpam = Text::strposa($entity->briefdescription, $domains);
                break;
            case 'group':
                $foundSpam = Text::strposa($entity->getBriefDescription(), $domains);
                break;
            default:
                error_log("[spam-check]: $entity->type:$entity->subtype not supported");
        }

        if ($foundSpam) {
            throw new ProhibitedDomainException("Sorry, you included a reference to a domain name linked to spam ({$foundSpam})");
            return true;
        }
        return $foundSpam ? true : false;
    }

    /**
     * Check string of text for prohibited spam domains.
     * @param string $text - text to check.
     * @throws ProhibitedDomainException - on prohibited domain found.
     * @return bool true if no domain is found. Else will throw exception.
     */
    public function checkText(string $text): bool
    {
        if ($foundSpam = Text::strposa($text, $this->getDomains()) ?? false) {
            throw new ProhibitedDomainException("Sorry, you included a reference to a domain name linked to spam ({$foundSpam})");
        }
        return true;
    }

    /**
     * @return string[]
     */
    private function getDomains(): array
    {
        if ($this->config->get('tenant_id')) {
            return ProhibitedDomains::DOMAINS;
        }

        // url shorteners are dissallowed on Minds, but allowed on tenants
        return [...ProhibitedDomains::DOMAINS, ...ProhibitedDomains::SHORT_DOMAINS];
    }
}
