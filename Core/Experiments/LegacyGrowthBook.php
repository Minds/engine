<?php
namespace Minds\Core\Experiments;

use Minds\Entities\User;

/**
 * This class can be removed once the latest mobile release is sufficiently distributed
 */
class LegacyGrowthBook
{
    public static function getExportedConfigs(User $user = null): array
    {
        return [
            'attributes' => [
                'id' => $user?->getGuid(),
                'loggedIn' => !!$user,
                'environment' => 'production',
            ],
            'features' => json_decode('{
                "discovery-homepage": {
                    "defaultValue": true
                },
                "mob-4193-polling": {
                    "defaultValue": true
                },
                "mob-4107-channelrecs": {
                    "defaultValue": true
                },
                "mob-4231-captcha": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "variations": [
                                false,
                                true
                            ],
                            "weights": [
                                0.5,
                                0.5
                            ],
                            "hashAttribute": "id"
                        }
                    ]
                },
                "epic-168-polygon": {
                    "defaultValue": true
                },
                "mob-minds-3119-captcha-for-engagement": {
                    "defaultValue": false
                },
                "mob-discovery-redirect": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "variations": [
                                false,
                                true
                            ],
                            "weights": [
                                0.5,
                                0.5
                            ],
                            "hashAttribute": "id"
                        }
                    ]
                },
                "front-5333-persistent-feed": {
                    "defaultValue": false
                },
                "engine-1218-metrics-sockets": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "condition": {
                                "environment": {
                                    "$in": [
                                        "staging",
                                        "sandbox"
                                    ]
                                }
                            },
                            "force": true
                        },
                        {
                            "variations": [
                                false,
                                true
                            ],
                            "weights": [
                                0.5,
                                0.5
                            ],
                            "hashAttribute": "deviceId"
                        }
                    ]
                },
                "mobile-5645-media-quotes": {
                    "defaultValue": true
                },
                "mobile-supermind": {
                    "defaultValue": true
                },
                "mob-4424-sockets": {
                    "defaultValue": true
                },
                "minds-3477-connect-twitter-modal": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "force": true
                        }
                    ]
                },
                "mob-4472-in-app-verification": {
                    "defaultValue": false
                },
                "front-5812-supermind-button": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "condition": {
                                "id": "100000000000000063"
                            },
                            "force": true
                        },
                        {
                            "variations": [
                                false,
                                true
                            ],
                            "weights": [
                                0.5,
                                0.5
                            ],
                            "hashAttribute": "id"
                        }
                    ]
                },
                "front-5813-supermind-comment-prompt": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "force": true
                        }
                    ]
                },
                "mob-stripe-connect-4587": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "force": true
                        }
                    ]
                },
                "mob-4482-global-supermind-feed": {
                    "defaultValue": true
                },
                "mob-4637-ios-hide-minds-superminds": {
                    "defaultValue": true
                },
                "mob-4638-boost-v3": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "force": true
                        }
                    ]
                },
                "minds-3639-plus-notice": {
                    "defaultValue": 0,
                    "rules": [
                        {
                            "variations": [
                                0,
                                1,
                                2,
                                3,
                                4
                            ],
                            "weights": [
                                0.2,
                                0.2,
                                0.2,
                                0.2,
                                0.2
                            ],
                            "key": "minds-3639-plus-notice",
                            "hashAttribute": "id"
                        }
                    ]
                },
                "epic-275-in-app-verification": {
                    "defaultValue": false
                },
                "mob-4708-performance-monitoring": {
                    "defaultValue": true
                },
                "mob-4722-track-code-push": {
                    "defaultValue": true
                },
                "mob-twitter-oauth-4715": {
                    "defaultValue": false
                },
                "epic-303-boost-partners": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        },
                        {
                            "condition": {
                                "route": {
                                    "$in": [
                                        "/minds/",
                                        "/mark/",
                                        "/ottman/",
                                        "/jack/"
                                    ]
                                }
                            },
                            "force": true
                        }
                    ]
                },
                "front-5882-boost-preapprovals": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        }
                    ]
                },
                "mob-4596-create-modal": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "force": true
                        }
                    ]
                },
                "mob-4812-discovery-badge": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "variations": [
                                false,
                                true
                            ],
                            "weights": [
                                0.5,
                                0.5
                            ],
                            "key": "mob-4812-discovery-badge",
                            "hashAttribute": "id"
                        }
                    ]
                },
                "front-5938-discovery-nav-dot": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "variations": [
                                false,
                                true
                            ],
                            "weights": [
                                0.5,
                                0.5
                            ],
                            "hashAttribute": "id"
                        }
                    ]
                },
                "mob-4836-iap-no-cash": {
                    "defaultValue": true
                },
                "minds-3857-paywall-context": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        }
                    ]
                },
                "minds-3897-chatwoot": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": {
                                    "$in": [
                                        "staging",
                                        "canary"
                                    ]
                                }
                            },
                            "force": true
                        }
                    ]
                },
                "mob-4851-iap-boosts": {
                    "defaultValue": false
                },
                "minds-3921-mandatory-onboarding-tags": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        },
                        {
                            "variations": [
                                false,
                                true
                            ],
                            "weights": [
                                0.5,
                                0.5
                            ],
                            "hashAttribute": "deviceId"
                        }
                    ]
                },
                "mob-4903-referrer-banner": {
                    "defaultValue": false
                },
                "mob-4903-wefounder-banner": {
                    "defaultValue": true
                },
                "minds-3952-boost-goals": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        }
                    ]
                },
                "front-5986-reset-password": {
                    "defaultValue": true
                },
                "minds-4030-boost-platform-targeting": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        }
                    ]
                },
                "mob-4938-newsfeed-for-you": {
                    "defaultValue": true
                },
                "mob-4952-boost-platform-targeting": {
                    "defaultValue": false
                },
                "mob-4989-compose-fab": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "variations": [
                                false,
                                true
                            ],
                            "weights": [
                                0.5,
                                0.5
                            ],
                            "key": "mob-4989-compose-fab",
                            "hashAttribute": "id"
                        }
                    ]
                },
                "minds-4105-remove-rotator": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        }
                    ]
                },
                "minds-4096-onboarding-v5-enrollment": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        }
                    ]
                },
                "minds-4096-onboarding-v5-global-on-switch": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        },
                        {
                            "condition": {
                                "environment": "production"
                            },
                            "force": true
                        }
                    ]
                },
                "mob-5009-boost-rotator-in-feed": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "force": true
                        }
                    ]
                },
                "minds-4126-gift-card-claim": {
                    "defaultValue": true
                },
                "mob-5038-discovery-consolidation": {
                    "defaultValue": true
                },
                "front-6032-twitter-sync-settings": {
                    "defaultValue": true
                },
                "minds-4175-explicit-votes": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        },
                        {
                            "condition": {
                                "id": {
                                    "$in": [
                                        "1271629611221913617"
                                    ]
                                }
                            },
                            "force": true
                        }
                    ]
                },
                "minds-4127-search": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        }
                    ]
                },
                "minds-4157-livepeer": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        }
                    ]
                },
                "minds-4159-boost-groups": {
                    "defaultValue": true
                },
                "mob-5075-explicit-vote-buttons": {
                    "defaultValue": false
                },
                "mob-5075-hide-post-on-downvote": {
                    "defaultValue": false
                },
                "mob-5097-group-queue-received-notification": {
                    "defaultValue": false
                },
                "minds-4169-for-you-top-posts-injection": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        }
                    ]
                },
                "engine-2526-twitter-superminds": {
                    "defaultValue": false
                },
                "engine-2592-notification-count-sockets": {
                    "defaultValue": true
                },
                "minds-4228-for-you-tag-recs": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        },
                        {
                            "variations": [
                                false,
                                true
                            ],
                            "weights": [
                                0.5,
                                0.5
                            ],
                            "hashAttribute": "deviceId"
                        }
                    ]
                },
                "minds-4302-gift-card-purchase": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        }
                    ]
                },
                "minds-4384-sidenav-networks-link": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": false
                        },
                        {
                            "condition": {
                                "id": {
                                    "$in": [
                                        "1574163041233145867"
                                    ]
                                }
                            },
                            "force": true
                        }
                    ]
                },
                "mob-4990-iap-subscription-ios": {
                    "defaultValue": true
                },
                "mob-5221-google-hide-tokens": {
                    "defaultValue": true,
                    "rules": [
                        {
                            "force": true
                        }
                    ]
                },
                "front-6121-rbac-permissions": {
                    "defaultValue": true
                },
                "tmp-create-networks": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "condition": {
                                "id": {
                                    "$in": [
                                        "100000000000028025",
                                        "1361699947367370771",
                                        "1259169619235577874",
                                        "100000000000000341",
                                        "100000000000001111",
                                        "100000000000000063",
                                        "1383132887649357837",
                                        "930229554033729554",
                                        "928140507236802565",
                                        "1556442301645983758",
                                        "773311697292107790",
                                        "822461769950699526"
                                    ]
                                }
                            },
                            "force": true
                        }
                    ]
                },
                "epic-358-chat": {
                    "defaultValue": false,
                    "rules": [
                        {
                            "condition": {
                                "environment": "staging"
                            },
                            "force": true
                        }
                    ]
                },
                "epic-358-chat-mob": {
                    "defaultValue": false
                }
            }')
    ];
    }
}
