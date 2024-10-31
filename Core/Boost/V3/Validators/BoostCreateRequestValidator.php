<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Validators;

use Exception;
use Minds\Common\SystemUser;
use Minds\Core\Blogs\Blog;
use Minds\Core\Boost\V3\Enums\BoostGoal;
use Minds\Core\Boost\V3\Enums\BoostGoalButtonText;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Enums\BoostTargetSuitability;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\InAppPurchases\Enums\InAppPurchasePaymentMethodIdsEnum;
use Minds\Core\Payments\Stripe\PaymentMethods\Manager as PaymentMethodsManager;
use Minds\Core\Security\ACL;
use Minds\Core\Security\ProhibitedDomains;
use Minds\Core\Session;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Helpers\Text;
use Minds\Interfaces\ValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;

class BoostCreateRequestValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors = null;

    public function __construct(
        private ?PaymentMethodsManager $paymentMethodsManager = null,
        private ?MindsConfig $mindsConfig = null,
        private ?ExperimentsManager $experiments = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        private ?ACL $acl = null,
        private ?SystemUser $systemUser = null
    ) {
        $this->paymentMethodsManager ??= Di::_()->get('Stripe\PaymentMethods\Manager');
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->experiments ??= Di::_()->get('Experiments\Manager');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->acl ??= Di::_()->get('Security\ACL');
        $this->systemUser ??= new SystemUser();
    }

    private function resetErrors(): void
    {
        $this->errors = new ValidationErrorCollection();
    }

    /**
     * @param array|ServerRequestInterface $dataToValidate
     * @return bool
     * @throws Exception
     */
    public function validate(array|ServerRequestInterface $dataToValidate): bool
    {
        $this->resetErrors();

        if (!isset($dataToValidate['entity_guid'])) {
            $this->errors->add(
                new ValidationError(
                    'entity_guid',
                    'Entity GUID must be provided'
                )
            );
        } elseif (!is_numeric($dataToValidate['entity_guid'])) {
            $this->errors->add(
                new ValidationError(
                    'entity_guid',
                    'Entity GUID must be a valid guid'
                )
            );
        }

        if (!isset($dataToValidate['target_suitability'])) {
            $this->errors->add(
                new ValidationError(
                    'target_suitability',
                    'Target suitability must be provided'
                )
            );
        } elseif (!in_array($dataToValidate['target_suitability'], BoostTargetSuitability::VALID, true)) {
            $this->errors->add(
                new ValidationError(
                    'target_suitability',
                    'Target suitability must be one of the valid options'
                )
            );
        }

        if (!isset($dataToValidate['target_location'])) {
            $this->errors->add(
                new ValidationError(
                    'target_location',
                    'Target location must be provided'
                )
            );
        } elseif (!in_array($dataToValidate['target_location'], BoostTargetLocation::VALID, true)) {
            $this->errors->add(
                new ValidationError(
                    'target_location',
                    'Target location must be one of the valid options'
                )
            );
        }

        if (!isset($dataToValidate['payment_method'])) {
            $this->errors->add(
                new ValidationError(
                    'payment_method',
                    'Payment method must be provided'
                )
            );
        } elseif (!in_array($dataToValidate['payment_method'], BoostPaymentMethod::VALID, true)) {
            $this->errors->add(
                new ValidationError(
                    'payment_method',
                    'Payment method must be one of the valid options'
                )
            );
        } elseif ((int) $dataToValidate['payment_method'] === BoostPaymentMethod::CASH) {
            if (
                $dataToValidate['payment_method_id'] !== GiftCard::DEFAULT_GIFT_CARD_PAYMENT_METHOD_ID &&
                in_array($dataToValidate['payment_method_id'], InAppPurchasePaymentMethodIdsEnum::cases(), true)
            ) {
                $isPaymentMethodIdValid = $this->paymentMethodsManager->checkPaymentMethodOwnership(
                    $this->getLoggedInUserGuid(),
                    $dataToValidate['payment_method_id']
                );
                if (!$isPaymentMethodIdValid) {
                    $this->errors->add(
                        new ValidationError(
                            "supermind_request:payment_options:payment_method_id",
                            "The provided payment method is not associated with your account"
                        )
                    );
                }
            }
        } elseif ((int) $dataToValidate['payment_method'] === BoostPaymentMethod::ONCHAIN_TOKENS) {
            if (!$dataToValidate['payment_tx_id'] || !str_starts_with($dataToValidate['payment_tx_id'], '0x')) {
                $this->errors->add(
                    new ValidationError(
                        "supermind_request:payment_options:payment_method_id",
                        "The provided payment method must be supplied along with a valid payment_tx_id"
                    )
                );
            }
        }

        $this->checkIAPTransaction($dataToValidate);

        $this->checkDailyBid($dataToValidate);
        $this->checkDurationDays($dataToValidate);
        $this->checkGoals($dataToValidate);
        $this->checkTargetPlatform($dataToValidate);
        $this->checkPublicReadAccess($dataToValidate);

        return $this->errors->count() === 0;
    }

    private function checkDailyBid(array $dataToValidate): void
    {
        if (!isset($dataToValidate['daily_bid'])) {
            $this->errors->add(
                new ValidationError(
                    'daily_bid',
                    'Daily bid must be provided'
                )
            );
        } elseif (!is_numeric($dataToValidate['daily_bid'])) {
            $this->errors->add(
                new ValidationError(
                    'daily_bid',
                    'Daily bid must be a numeric value'
                )
            );
        } elseif (isset($dataToValidate['payment_method'])) {
            if ((int) $dataToValidate['payment_method'] === BoostPaymentMethod::CASH) {
                if ((float) $dataToValidate['daily_bid'] < $this->mindsConfig->get('boost')['min']['cash']) {
                    $this->errors->add(
                        new ValidationError(
                            'daily_bid',
                            "Daily bid cannot be lower than \${$this->mindsConfig->get('boost')['min']['cash']}"
                        )
                    );
                } elseif ((float) $dataToValidate['daily_bid'] > $this->mindsConfig->get('boost')['max']['cash']) {
                    $this->errors->add(
                        new ValidationError(
                            'daily_bid',
                            "Daily bid cannot be higher than \${$this->mindsConfig->get('boost')['max']['cash']}"
                        )
                    );
                }
            } elseif ((int) $dataToValidate['payment_method'] === BoostPaymentMethod::OFFCHAIN_TOKENS) {
                if ((float) $dataToValidate['daily_bid'] < $this->mindsConfig->get('boost')['min']['offchain_tokens']) {
                    $this->errors->add(
                        new ValidationError(
                            'daily_bid',
                            "Daily bid cannot be lower than {$this->mindsConfig->get('boost')['min']['offchain_tokens']} MINDS tokens"
                        )
                    );
                } elseif ((float) $dataToValidate['daily_bid'] > $this->mindsConfig->get('boost')['max']['offchain_tokens']) {
                    $this->errors->add(
                        new ValidationError(
                            'daily_bid',
                            "Daily bid cannot be higher than {$this->mindsConfig->get('boost')['max']['offchain_tokens']} MINDS tokens"
                        )
                    );
                }
            }
        }
    }

    private function checkDurationDays(array $dataToValidate): void
    {
        if (!isset($dataToValidate['duration_days'])) {
            $this->errors->add(
                new ValidationError(
                    'duration_days',
                    'Daily bid must be provided'
                )
            );
        } elseif (!is_numeric($dataToValidate['duration_days'])) {
            $this->errors->add(
                new ValidationError(
                    'duration_days',
                    'Daily bid must be a numeric value'
                )
            );
        } elseif ($dataToValidate['duration_days'] < $this->mindsConfig->get('boost')['duration']['min']) {
            $this->errors->add(
                new ValidationError(
                    'duration_days',
                    "Duration in days cannot be less than {$this->mindsConfig->get('boost')['duration']['cash']['min']} days"
                )
            );
        } elseif ($dataToValidate['duration_days'] > $this->mindsConfig->get('boost')['duration']['max']) {
            $this->errors->add(
                new ValidationError(
                    'duration_days',
                    "Duration in days cannot be more than {$this->mindsConfig->get('boost')['duration']['cash']['max']} days"
                )
            );
        }
    }

    /**
     * Validate goals for boosted posts, and other fields associated with goals
     * (a.k.a. goal_button_text, goal_button_url)
     * @param array $dataToValidate
     * @return void
     */
    private function checkGoals(array $dataToValidate): void
    {
        // GOALS AREN'T ALLOWED FOR CHANNEL BOOSTS OR FOR BOOSTING SOMEONE ELSE'S POST
        $boostedEntity = $this->getBoostedEntity($dataToValidate);

        if (!$boostedEntity) {
            $this->errors->add(
                new ValidationError(
                    'entity_guid',
                    'You can only set a boost goal for an existing activity post or user'
                )
            );
        }

        $boostedEntityOwnerGuid = ($boostedEntity instanceof Activity || $boostedEntity instanceof Blog) ?
            $boostedEntity->getOwnerGuid() :
            '';

        if ($boostedEntityOwnerGuid !== $this->getLoggedInUserGuid()) {
            // Either it's a channel boost or it's not the post owner - all invalid
            if (isset($dataToValidate['goal'])) {
                $this->errors->add(
                    new ValidationError(
                        'goal',
                        'You can only set a boost goal when boosting your own post'
                    )
                );
            }
            if (isset($dataToValidate['goal_button_text'])) {
                $this->errors->add(
                    new ValidationError(
                        'goal_button_text',
                        'You can only set button text when boosting your own post'
                    )
                );
            }
            if (isset($dataToValidate['goal_button_url'])) {
                $this->errors->add(
                    new ValidationError(
                        'goal_button_text',
                        'You can only set a button url when boosting your own post'
                    )
                );
            }
            return; // we do not need to validate goal data as it is not there.
        }

        // ---------------------------------------------------------
        // THIS IS THE OWNER OF THE BOOSTED POST. Continue validating...
        if (!isset($dataToValidate['goal'])) {
            $this->errors->add(
                new ValidationError(
                    'goal',
                    'Boost goal must be provided'
                )
            );
        } elseif (!in_array((int) $dataToValidate['goal'], BoostGoal::VALID, true)) {
            $this->errors->add(
                new ValidationError(
                    'goal',
                    'Boost goal must be one of the valid options'
                )
            );
        } else {
            // ---------------------------------------------------------
            // Validate GOAL_BUTTON_TEXT

            if (!in_array((int) $dataToValidate['goal'], BoostGoal::GOALS_REQUIRING_GOAL_BUTTON_TEXT, true)) {
                if (isset($dataToValidate['goal_button_text'])) {
                    $this->errors->add(new ValidationError(
                        'goal_button_text',
                        'Button text is not allowed for the selected boost goal'
                    ));
                }
            } else {
                if (!isset($dataToValidate['goal_button_text'])) {
                    $this->errors->add(new ValidationError(
                        'goal_button_text',
                        'Button text must be provided for the selected goal'
                    ));
                } else {
                    // goal_button_text must be valid for the goals that require it
                    $invalidButtonTextForSubscriberGoal = (int) $dataToValidate['goal'] === BoostGoal::SUBSCRIBERS && !in_array($dataToValidate['goal_button_text'], BoostGoalButtonText::VALID_GOAL_BUTTON_TEXTS_WHEN_GOAL_IS_SUBSCRIBERS, true);


                    // (!in_array($dataToValidate['target_suitability'], BoostTargetSuitability::VALID, true))

                    $invalidButtonTextForClickGoal = (int) $dataToValidate['goal'] === BoostGoal::CLICKS && !in_array($dataToValidate['goal_button_text'], BoostGoalButtonText::VALID_GOAL_BUTTON_TEXTS_WHEN_GOAL_IS_CLICKS, true);

                    if ($invalidButtonTextForSubscriberGoal || $invalidButtonTextForClickGoal) {
                        $this->errors->add(new ValidationError(
                            'goal_button_text',
                            'Button text must be a valid option for the selected goal'
                        ));
                    }
                }
            }
            // ---------------------------------------------------------
            // Validate GOAL_BUTTON_URL
            if (!in_array((int) $dataToValidate['goal'], BoostGoal::GOALS_REQUIRING_GOAL_BUTTON_URL, true)) {
                if (isset($dataToValidate['goal_button_url'])) {
                    $this->errors->add(new ValidationError(
                        'goal_button_url',
                        'Button url is not a valid field for the selected goal'
                    ));
                }
            } else {
                if (!isset($dataToValidate['goal_button_url'])) {
                    $this->errors->add(new ValidationError(
                        'goal_button_url',
                        'Button url must be provided'
                    ));
                } else {
                    if (Text::strposa($dataToValidate['goal_button_url'], ProhibitedDomains::DOMAINS)) {
                        $this->errors->add(new ValidationError(
                            'goal_button_url',
                            'Button url references a domain name linked to spam'
                        ));
                    } elseif (!preg_match('#^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$#i', $dataToValidate['goal_button_url'])) {
                        $this->errors->add(new ValidationError(
                            'goal_button_url',
                            'Button url is not in valid url format'
                        ));
                    }
                }
            }
        }

    }

    /**
     * If platform targets are set,
     * ensure at least one is true
     * (e.g. web/android/ios)
     * @param array $dataToValidate
     * @return void
     */
    private function checkTargetPlatform(array $dataToValidate): void
    {
        if ($this->targetPlatformFeatureEnabled() && isset($dataToValidate['target_platform_web']) && isset($dataToValidate['target_platform_android']) && isset($dataToValidate['target_platform_ios'])) {
            if (!$dataToValidate['target_platform_web'] && !$dataToValidate['target_platform_android'] && !$dataToValidate['target_platform_ios']) {
                $this->errors->add(
                    new ValidationError(
                        'target_platform',
                        'At least one target platform must be selected'
                    )
                );
            }
        }
    }

    /**
     * Checks that an entity can be read by the system user, meaning that it is publicly accessible.
     * @param array|ServerRequestInterface $dataToValidate - data to validate.
     * @return void
     */
    private function checkPublicReadAccess(array|ServerRequestInterface $dataToValidate): void
    {
        $boostedEntity = $this->getBoostedEntity($dataToValidate);
        if (!$this->acl->read($boostedEntity, $this->systemUser)) {
            $entityType = ucfirst($boostedEntity->getType()) ?? 'Entity';
            $this->errors->add(
                new ValidationError(
                    'entity_guid',
                    $entityType . " cannot be boosted as it is not publicly accessible"
                )
            );
        }

        if (method_exists($boostedEntity, 'getTimeCreated') && $boostedEntity->getTimeCreated() > time()) {
            $this->errors->add(
                new ValidationError(
                    'entity_guid',
                    "Scheduled posts cannot be boosted"
                )
            );
        }
    }

    /**
     * True if feature that allows users to set audience platform targets
     * @return boolean - true if feature is enabled.
     */
    private function targetPlatformFeatureEnabled(): bool
    {
        return $this->experiments->setUser(Session::getLoggedinUser())
            ->isOn('minds-4030-boost-platform-targeting');
    }

    /**
     * @param array|ServerRequestInterface $dataToValidate
     * @return EntityInterface entity - the entity being boosted
     */
    private function getBoostedEntity(array|ServerRequestInterface $dataToValidate): ?EntityInterface
    {
        $boostedEntity = null;

        if (isset($dataToValidate['entity_guid'])) {
            $boostedEntity = $this->entitiesBuilder->single($dataToValidate['entity_guid']);
        }
        return $boostedEntity;
    }

    private function getLoggedInUserGuid(): string
    {
        return (string) Session::getLoggedinUser()->getGuid();
    }

    public function getErrors(): ?ValidationErrorCollection
    {
        return $this->errors;
    }

    /**
     * @param array|ServerRequestInterface $dataToValidate
     * @return void
     */
    private function checkIAPTransaction(array|ServerRequestInterface $dataToValidate): void
    {
        if (!in_array($dataToValidate['payment_method_id'], InAppPurchasePaymentMethodIdsEnum::cases(), true)) {
            return;
        }

        if (!isset($dataToValidate['iap-transaction'])) {
            $this->errors->add(
                new ValidationError(
                    'iap-transaction',
                    'IAP transaction must be provided'
                )
            );
        } elseif (!is_string($dataToValidate['iap-transaction'])) {
            $this->errors->add(
                new ValidationError(
                    'iap-transaction',
                    'IAP transaction must be a string'
                )
            );
        }
    }
}
