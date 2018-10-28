<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpMember;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;

class MembersController extends Controller
{
    /**
     * @var \Mailchimp\Mailchimp
     */
    private $mailChimp;

    /**
     * MembersController constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Mailchimp\Mailchimp $mailchimp
     */
    public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
    {
        parent::__construct($entityManager);

        $this->mailChimp = $mailchimp;
    }

    /**
     * Create MailChimp list member.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request, string $listId): JsonResponse
    {
        // Instantiate entity
        $member = new MailChimpMember($request->all());
        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->findOneBy(['mailChimpId' => $listId]);

        if (is_null($list)) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        try {
            // Save member into list
            $this->mailChimp->post('lists/' . $listId . '/members', $member->toMailChimpArray());
            // Set List Id For Member and Save member into db
            $this->saveEntity($member->setListId($listId));
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * Update One Member Of MailChimp list.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $listId, string $memberId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpMember::class)->find($memberId);

        if (is_null($member)) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMember[member_id: %s, list_id: %s] not found', $memberId, $listId)],
                404
            );
        }

        // Update member properties
        $member->fill($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            // Update member into database
            $this->saveEntity($member);
            // Update member into MailChimp
            $this->mailChimp->patch('lists/' . $listId . '/members/' . md5($member->getEmailAddress()), $member->toMailChimpArray());
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * Retrieve and return a member from MailChimp list.
     *
     * @param string $listId
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $listId, string $memberId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpMember::class)->find($memberId);

        if (is_null($member)) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMember[member_id: %s, list_id: %s] not found', $memberId, $listId)],
                404
            );
        }

        try {
            // Get member into MailChimp
            $response = $this->mailChimp->get('lists/' . $listId . '/members/' . md5($member->getEmailAddress()));
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($response->toArray());
    }

    /**
     * Retrieve and return all members from MailChimp list.
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showAll(string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->findOneBy(['mailChimpId' => $listId]);

        if (is_null($list)) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        try {
            // Get member into MailChimp
            $response = $this->mailChimp->get('lists/' . $listId . '/members');
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($response->toArray());
    }


}
