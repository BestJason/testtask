<?php
declare(strict_types=1);

namespace Tests\App\TestCases\MailChimp;

use App\Database\Entities\MailChimp\MailChimpMember;
use Mailchimp\Mailchimp;

abstract class MemberTestCase extends ListTestCase
{
    /**
     * @var array
     */
    protected $createdMemberEmails = [];

    /**
     * @var string
     */
    protected $mailChimpId = '38d3a90209';

    /**
     * @var array
     */
    protected static $memberData = [
        'email_address' => 'mrjasonedu+3@gmail.com',
        'email_type' => 'html',
        'status' => 'subscribed',
        'merge_fields' => [
            'FNAME' => 'Jason',
            'LNAME' => 'Lee',
        ],
        'language' => 'en',
        'vip' => true,
        'location' => [
            'latitude' => 32.232323,
            'longitude' => 141.726453
        ],
        'marketing_permissions' => [
            [
                'marketing_permission_id' => 'Permissions Id Test'
            ],
            [
                'enabled' => false
            ],
        ],
        'ip_signup' => '192.168.0.1',
        'timestamp_signup' => '2018-09-09 09:09:09',
        'ip_opt' => '172.132.10.1',
        'timestamp_opt' => '2018-09-09 09:09:09',
        'tags' => [
            'tag1',
            'tag2',
            'tag3'
        ]
    ];

    /**
     * @var array
     */
    protected static $notRequired = [
        'email_type',
        'merge_fields',
        'interests',
        'language',
        'vip',
        'location',
        'marketing_permissions',
        'ip_signup',
        'timestamp_signup',
        'ip_opt',
        'timestamp_opt',
        'tags'
    ];

    /**
     * Create MailChimp member into database.
     *
     * @param array $data
     *
     * @return \App\Database\Entities\MailChimp\MailChimpMember
     */
    protected function createMember(array $data): MailChimpMember
    {
        // Create a member for list
        $member = new MailChimpMember($data);
        $member->setListId($this->mailChimpId);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Call MailChimp to delete members created during test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        /** @var Mailchimp $mailChimp */
        $mailChimp = $this->app->make(Mailchimp::class);

        foreach ($this->createdMemberEmails as $memberEmail) {
            // Delete member on MailChimp after test
            $mailChimp->delete('lists/' . $this->mailChimpId . '/members/' . \md5($memberEmail));
        }

        parent::tearDown();
    }

    /**
     * Asserts error response when member not found.
     *
     * @param string $memberId
     *
     * @return void
     */
    protected function assertMemberNotFoundResponse(string $memberId): void
    {
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals(\sprintf('MailChimpMember[%s] not found', $memberId), $content['message']);
    }
}
