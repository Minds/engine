<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Validators;

use Exception;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Enums\BoostTargetSuitability;
use Minds\Core\Boost\V3\Enums\BoostGoal;
use Minds\Core\Boost\V3\Enums\BoostGoalButtonText;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\PaymentMethods\Manager as PaymentMethodsManager;
use Minds\Core\Session;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Interfaces\ValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Helpers\Text;
use Minds\Core\Security\ProhibitedDomains;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Entities\EntityInterface;

class BoostCreateRequestValidator implements ValidatorInterface
{
    private ?ValidationErrorCollection $errors = null;

    public function __construct(
        private ?PaymentMethodsManager $paymentMethodsManager = null,
        private ?MindsConfig $mindsConfig = null,
        private ?ExperimentsManager $experiments = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
    ) {
        $this->paymentMethodsManager ??= Di::_()->get('Stripe\PaymentMethods\Manager');
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->experiments ??= Di::_()->get('Experiments\Manager');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
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

        $this->checkDailyBid($dataToValidate);
        $this->checkDurationDays($dataToValidate);
        $this->checkGoals($dataToValidate);

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
        if (!$this->goalFeatureEnabled()) {
            // FEATURE IS OFF - nothing goal-related is valid
            if (isset($dataToValidate['goal'])) {
                $this->errors->add(
                    new ValidationError(
                        'goal',
                        'Boost goal feature must be enabled to set goal'
                    )
                );
            }
            if (isset($dataToValidate['goal_button_text'])) {
                $this->errors->add(
                    new ValidationError(
                        'goal_button_text',
                        'Boost goal feature must be enabled to set goal_button_text'
                    )
                );
            }
            if (isset($dataToValidate['goal_button_url'])) {
                $this->errors->add(
                    new ValidationError(
                        'goal_button_text',
                        'Boost goal feature must be enabled to set goal_button_url'
                    )
                );
            }
        } else {
            // FEATURE IS ON, continue validating...
            // ---------------------------------------------------------
            // GOALS AREN'T ALLOWED FOR CHANNEL BOOSTS OR FOR BOOSTING SOMEONE ELSE'S POST
            $boostedEntity = $this->getBoostedEntity($dataToValidate);

            $boostedEntityOwnerGuid = ($boostedEntity instanceof Activity) ? $boostedEntity->getOwnerGuid() : '';


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
                            'You can only set goal_button_text when boosting your own post'
                        )
                    );
                }
                if (isset($dataToValidate['goal_button_url'])) {
                    $this->errors->add(
                        new ValidationError(
                            'goal_button_text',
                            'You can only set goal_button_url when boosting your own post'
                        )
                    );
                }
            }
            // ---------------------------------------------------------
            // THIS IS THE OWNER OF THE BOOSTED POST. Continue validating...
            if (!isset($dataToValidate['goal'])) {
                // We still need a goal, it's required
                $this->errors->add(
                    new ValidationError(
                        'goal',
                        'Boost goal must be provided'
                    )
                );
            } elseif (!in_array($dataToValidate['goal'], BoostGoal::VALID, true)) {
                // and that goal must be valid
                $this->errors->add(
                    new ValidationError(
                        'goal',
                        'Boost goal must be one of the valid options'
                    )
                );
            } else {
                // We have a valid goal.
                // Now we use it to validate goal_button_text...
                if (!in_array($dataToValidate['goal'], BoostGoal::GOALS_REQUIRING_GOAL_BUTTON_TEXT, true)) {
                    // goal_button_text isn't allowed when it's not required
                    if (isset($dataToValidate['goal_button_text'])) {
                        $this->errors->add(new ValidationError(
                            'goal_button_text',
                            'goal_button_text is not a valid field for the selected boost goal'
                        ));
                    }
                } else {
                    // goal_button_text is required for the selected goal
                    if (!isset($dataToValidate['goal_button_text'])) {
                        // we still need goal_button_text
                        $this->errors->add(new ValidationError(
                            'goal_button_text',
                            'goal_button_text must be provided for the selected goal'
                        ));
                    } else {
                        // goal_button_text must be valid for the goals that require it
                        $invalidButtonTextForSubscriberGoal = (int) $dataToValidate['goal'] === BoostGoal::SUBSCRIBERS && !in_array($dataToValidate['goal_button_text'], BoostGoalButtonText::VALID_GOAL_BUTTON_TEXTS_WHEN_GOAL_IS_SUBSCRIBERS, true);

                        $invalidButtonTextForClickGoal = (int) $dataToValidate['goal'] === BoostGoal::CLICKS && !in_array($dataToValidate['goal_button_text'], BoostGoalButtonText::VALID_GOAL_BUTTON_TEXTS_WHEN_GOAL_IS_CLICKS, true);

                        if ($invalidButtonTextForSubscriberGoal || $invalidButtonTextForClickGoal) {
                            $this->errors->add(new ValidationError(
                                'goal_button_text',
                                'goal_button_text must be a valid option for the selected goal'
                            ));
                        }
                    }
                }
                // ---------------------------------------------------------
                // Now use the goal to validate goal_button_url...
                if (!in_array($dataToValidate['goal'], BoostGoal::GOALS_REQUIRING_GOAL_BUTTON_URL, true)) {
                    // goal_button_url isn't allowed when it's not required
                    if (isset($dataToValidate['goal_button_url'])) {
                        $this->errors->add(new ValidationError(
                            'goal_button_url',
                            'goal_button_url is not a valid field for the selected goal'
                        ));
                    }
                } else {
                    // goal_button_url is required
                    if (!isset($dataToValidate['goal_button_url'])) {
                        // we still need the goal_button_url
                        $this->errors->add(new ValidationError(
                            'goal_button_url',
                            'goal_button_url must be provided'
                        ));
                    } else {
                        // we have a goal_button_url, now check its validity...
                        if (Text::strposa($dataToValidate['goal_button_url'], ProhibitedDomains::DOMAINS)) {
                            // goal_button_url is spammy, don't allow it
                            $this->errors->add(new ValidationError(
                                'goal_button_url',
                                'goal_button_url references a domain name linked to spam'
                            ));
                        }

                        // OJM TODO - see if we have a helper for this
                        // if (goal_button_url is not in url format) {
                        //     $this->errors->add(new ValidationError(
                        //         'goal_button_url',
                        //         'goal_button_url is not a valid url format'
                        //     ));
                        // }
                    }
                }
            }
        }
    }

    /**
     * True if feature that allows users to set goals for boosted posts is enabled
     * @return boolean - true if feature is enabled.
     */
    private function goalFeatureEnabled(): bool
    {
        return $this->experiments->setUser(Session::getLoggedinUser())
            ->isOn('minds-3952-boost-goals');
    }

    /**
     * @param array|ServerRequestInterface $dataToValidate
     * @return EntityInterface entity - the entity being boosted
     */
    private function getBoostedEntity($dataToValidate): EntityInterface
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
}
