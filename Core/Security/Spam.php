<?php

namespace Minds\Core\Security;

use Minds\Helpers\Text;
use Minds\Core\Config;
use Minds\Core\Security\ProhibitedDomains;
use Minds\Exceptions\ProhibitedDomainException;

class Spam
{
    /**
     * Check for spam
     * @param mixed $entity
     * @return bool
     */
    public function check($entity): ?bool
    {
        $foundSpam = false;

        switch ($entity->getType()) {
            case 'comment':
                $foundSpam = Text::strposa($entity->getBody(), ProhibitedDomains::DOMAINS);
                break;
            case 'activity':
                $foundSpam = Text::strposa($entity->getMessage(), ProhibitedDomains::DOMAINS) ?:
                    Text::strposa($entity->getPermaUrl(), ProhibitedDomains::DOMAINS);
                break;
            case 'object':
                if ($entity->getSubtype() === 'blog') {
                    $foundSpam = Text::strposa($entity->getBody(), ProhibitedDomains::DOMAINS);
                    break;
                }
                if (method_exists($entity, 'getDescription')) {
                    $foundSpam = Text::strposa($entity->getDescription(), ProhibitedDomains::DOMAINS);
                }
                break;
            case 'user':
                $foundSpam = Text::strposa($entity->briefdescription, ProhibitedDomains::DOMAINS);
                break;
            case 'group':
                $foundSpam = Text::strposa($entity->getBriefDescription(), ProhibitedDomains::DOMAINS);
                break;
            default:
                error_log("[spam-check]: $entity->type:$entity->subtype not supported");
        }

        if ($foundSpam) {
            throw new ProhibitedDomainException("Sorry, you included a reference to a domain name linked to spam (${foundSpam})");
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
        if ($foundSpam = Text::strposa($text, ProhibitedDomains::DOMAINS) ?? false) {
            throw new ProhibitedDomainException("Sorry, you included a reference to a domain name linked to spam (${foundSpam})");
        }
        return true;
    }
}
