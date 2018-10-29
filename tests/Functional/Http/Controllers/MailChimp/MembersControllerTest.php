<?php
declare(strict_types=1);

namespace Tests\App\Functional\Http\Controllers\MailChimp;

use Tests\App\TestCases\MailChimp\MemberTestCase;

class MembersControllerTest extends MemberTestCase
{
    /**
     * Test application creates successfully member and returns it back with member id from MailChimp.
     *
     * @return void
     */
    public function testCreateMemberSuccessfully(): void
    {
        // Create Temp Member
        list($memberData, $content) = $this->createTempMember();

        $this->assertResponseOk();
        $this->seeJson($memberData);
        self::assertArrayHasKey('member_id', $content);
        self::assertNotNull($content['member_id']);

        $this->createdMemberEmails[] = $content['email_address']; // Store MailChimp member email address for cleaning purposes
    }

    /**
     * Test application returns error response with errors when member validation fails.
     *
     * @return void
     */
    public function testCreateMemberValidationFailed(): void
    {
        $this->post('/mailchimp/lists/' . $this->mailChimpId . '/members');

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals('Invalid data given', $content['message']);

        foreach (\array_keys(static::$memberData) as $key) {
            if (\in_array($key, static::$notRequired, true)) {
                continue;
            }

            self::assertArrayHasKey($key, $content['errors']);
        }
    }

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testRemoveMemberNotFoundException(): void
    {
        $this->delete('/mailchimp/lists/' . $this->mailChimpId . '/members/invalid-member-id');

        $this->assertMemberNotFoundResponse('invalid-member-id', $this->mailChimpId);
    }

    /**
     * Test application returns empty successful response when removing existing member.
     *
     * @return void
     */
    public function testRemoveMemberSuccessfully(): void
    {
        // Create Temp Member
        list($memberData, $content) = $this->createTempMember();

        $this->delete('/mailchimp/lists/' . $this->mailChimpId . '/members/' . $content['member_id']);

        $this->assertResponseOk();
        self::assertEmpty(\json_decode($this->response->content(), true));
    }

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testShowMemberNotFoundException(): void
    {
        $this->get('/mailchimp/lists/' . $this->mailChimpId . '/members/invalid-member-id');

        $this->assertMemberNotFoundResponse('invalid-member-id', $this->mailChimpId);
    }

    /**
     * Test application returns successful response with member data when requesting existing member.
     *
     * @return void
     */
    public function testShowMemberSuccessfully(): void
    {
        // Create Temp Member
        list($memberData, $member) = $this->createTempMember();

        if (isset($member['member_id'])) {
            $this->createdMemberEmails[] = $member['email_address']; // Store MailChimp member email address for cleaning purposes
        }

        $this->get('/mailchimp/lists/' . $this->mailChimpId . '/members/' . $member['member_id']);
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach ($memberData as $key => $value) {
            if ($key != 'marketing_permissions') {
                self::assertArrayHasKey($key, $content);
            }
            if ($key == 'location') {
                self::assertArraySubset($value, $content[$key]);
            }
        }
    }

    /**
     * Test application returns error response when list not found.
     *
     * @return void
     */
    public function testShowAllMemberNotFoundException(): void
    {
        $this->get('/mailchimp/lists/invalid-list-id/members');

        $this->assertListNotFoundResponse('invalid-list-id');
    }

    /**
     * Test application returns successful response with all members data.
     *
     * @return void
     */
    public function testShowAllMemberSuccessfully(): void
    {
        // Create Temp Member
        list($memberData, $member) = $this->createTempMember();

        if (isset($member['member_id'])) {
            $this->createdMemberEmails[] = $member['email_address']; // Store MailChimp member email address for cleaning purposes
        }

        $this->get('/mailchimp/lists/' . $this->mailChimpId . '/members');
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseOk();
        self::assertArrayHasKey('members', $content);
        self::assertArrayHasKey('list_id', $content);
        self::assertEquals($this->mailChimpId, $content['list_id']);
    }

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testUpdateMemberNotFoundException(): void
    {
        $this->put('/mailchimp/lists/' . $this->mailChimpId . '/members/invalid-member-id');

        $this->assertMemberNotFoundResponse('invalid-member-id', $this->mailChimpId);
    }

    /**
     * Test application returns successfully response when updating existing member with updated values.
     *
     * @return void
     */
    public function testUpdateMemberSuccessfully(): void
    {
        // Create Temp Member
        list($memberData, $member) = $this->createTempMember();

        if (isset($member['member_id'])) {
            $this->createdMemberEmails[] = $member['email_address']; // Store MailChimp member email address for cleaning purposes
        }

        $this->put('/mailchimp/lists/' . $this->mailChimpId . '/members/' . $member['member_id'], ['status' => 'subscribed']);
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach (\array_keys($memberData) as $key) {
            self::assertArrayHasKey($key, $content);
            self::assertEquals('subscribed', $content['status']);
        }
    }

    /**
     * Test application returns error response with errors when member validation fails.
     *
     * @return void
     */
    public function testUpdateMemberValidationFailed(): void
    {
        // Create Temp Member
        list($memberData, $member) = $this->createTempMember();

        if (isset($member['member_id'])) {
            $this->createdMemberEmails[] = $member['email_address']; // Store MailChimp member email address for cleaning purposes
        }

        $this->put('/mailchimp/lists/' . $this->mailChimpId . '/members/' . $member['member_id'], ['status' => 'invalid']);
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertArrayHasKey('status', $content['errors']);
        self::assertEquals('Invalid data given', $content['message']);
    }
}
