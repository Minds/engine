<?php
namespace Minds\Core\Ai\Services;

use Minds\Core\Ai\Ollama\OllamaClient;
use Minds\Core\Ai\Ollama\OllamaMessage;
use Minds\Core\Ai\Ollama\OllamaRoleEnum;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\Elastic\V2\Manager as FeedsService;
use Minds\Core\Feeds\Elastic\V2\QueryOpts;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\User;

class EntityIntelligenceService
{
    public function __construct(
        private readonly OllamaClient $ollamaClient,
        private readonly Config $config,
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly FeedsService $feedsService,
        private readonly Logger $logger,
    ) {
        
    }

    /**
     * TODO
     */
    public function analyzeUser(User $user): bool
    {
        // Get a list of posts
        $queryOpts = new QueryOpts(
            user: $user,
            limit: 50,
            onlyOwn: true,
        );

        $feed = $this->feedsService->getLatest($queryOpts);
        $input = array_map(function (Activity $activity) {
            return [
                'message' => $activity->getMessage(),
                'title' => $activity->getTitle(),
                'timestamp' => date('c', $activity->time_created),
            ];
        }, iterator_to_array($feed));

        $response = $this->ollamaClient->chat([
            new OllamaMessage(
                role: OllamaRoleEnum::USER,
                content: "
                    Answer the following questions and return your response in only json, as this will be read by a machine. 
                    Do not use backticks.
                    # categories
                    Pick the most appropriate categories that relate to these posts (multiple can be selected except for 'other' which can only be used alone and/or not other categories apply):
                        - art
                        - music
                        - technology
                        - spirituality
                        - testing
                        - other
                    # positive
                    Is this a positive or negative post? True if positive, false if negative.
                    # frequency
                    Does the user post hourly, daily, monthly or rarely?
                    # hashtags
                    Suggest an array of single word hashtags to assist with indexing
                    # summary
                    Return an overview about what this user is posting about. At least a couple of sentences.
                ",
            ),
            new OllamaMessage(
                role: OllamaRoleEnum::USER,
                content: json_encode($input),
            )
        ], false);

        $result = json_decode($response->getBody()->getContents(), true);
 
    
        if (is_array($result['message']['content'])) {
            var_dump($result['message']['content']);
        } else {
            var_dump(json_decode($result['message']['content'], true));
        }

        
        return false;
    }

}
