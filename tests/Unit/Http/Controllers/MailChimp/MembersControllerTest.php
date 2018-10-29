<?php
declare(strict_types=1);

namespace Tests\App\Unit\Http\Controllers\MailChimp;

use App\Http\Controllers\MailChimp\MembersController;
use Tests\App\TestCases\MailChimp\MemberTestCase;

class MembersControllerTest extends MemberTestCase
{

    /**
     * @var object
     */
    protected $listObject = null;

    /**
     * Test controller returns error response when exception is thrown during create MailChimp request.
     *
     * @return void
     */
    public function testCreateMemberMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('post'));

        $this->assertMailChimpExceptionResponse($controller->create($this->getRequest(static::$memberData), $this->mailChimpId, true));
    }

    /**
     * Test controller returns error response when exception is thrown during get MailChimp request for getting one member.
     *
     * @return void
     */
    public function testGetOneMemberMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('get'));
        $member = $this->createMember(static::$memberData);

        // If there is no list id, skip
        if (is_null($member->getId())) {
            self::markTestSkipped('Unable to get the member, no id provide');

            return;
        }

        $this->assertMailChimpExceptionResponse($controller->show($this->mailChimpId, $member->getId()));
    }

    /**
     * Test controller returns error response when exception is thrown during get MailChimp request for getting all members.
     *
     * @return void
     */
    public function testGetAllMembersMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('get'));
        $member = $this->createMember(static::$memberData);

        // If there is no list id, skip
        if (is_null($member->getId())) {
            self::markTestSkipped('Unable to get the member, no id provide');

            return;
        }

        $this->assertMailChimpExceptionResponse($controller->showAll($this->mailChimpId, true));
    }

    /**
     * Test controller returns error response when exception is thrown during remove MailChimp request.
     *
     * @return void
     */
    public function testRemoveMemberMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('delete'));
        $member = $this->createMember(static::$memberData);

        // If there is no list id, skip
        if (is_null($member->getId())) {
            self::markTestSkipped('Unable to remove, no id provided for member');

            return;
        }

        $this->assertMailChimpExceptionResponse($controller->remove($this->mailChimpId, $member->getId()));
    }

    /**
     * Test controller returns error response when exception is thrown during update MailChimp request.
     *
     * @return void
     */
    public function testUpdateMemberMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('patch'));
        $member = $this->createMember(static::$memberData);

        // If there is no member id, skip
        if (is_null($member->getId())) {
            self::markTestSkipped('Unable to update, no id provided for member');

            return;
        }

        $this->assertMailChimpExceptionResponse($controller->update($this->getRequest(), $this->mailChimpId, $member->getId()));
    }
}
