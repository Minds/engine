<?php
namespace Minds\Integrations\Seco\Controllers;

use Minds\Core\EntitiesBuilder;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Minds\Integrations\Seco\Services\ImportThreadsService;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class ImportThreadsController
{
    public function __construct(
        private ImportThreadsService $importThreadsService,
        private EntitiesBuilder $entitiesBuilder,
    ) {
        
    }

    public function importThreads(ServerRequest $request): JsonResponse
    {
        $groupGuid = $request->getAttribute('parameters')['groupGuid'] ?? null;

        if (!$groupGuid) {
            throw new UserErrorException('You must send a group guid');
        }

        $group = $this->entitiesBuilder->single($groupGuid);

        if (!$group instanceof Group) {
            throw new NotFoundException('Group not found');
        }

        /** @var User */
        $user = $request->getAttribute('_user');

        /** @var User */
        $secoAssistant = $this->entitiesBuilder->single(1746921807006404608);

        $files = $request->getUploadedFiles();
        $fileData = $files['file']->getStream()->getContents();

        $threads = json_decode($fileData, true);
        
        $this->importThreadsService->process(
            actorUser: $user,
            secoAssistant: $secoAssistant,
            group: $group,
            threads: $threads,
        );

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
