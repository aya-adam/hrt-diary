<?php

namespace App\Tests\Api;

use ApiPlatform\Test\ApiTestCase;
use App\Api\Resource\EventResource;
use App\Entity\EventEntity;
use App\Factory\EventFactory;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;
use DateTimeInterface;
use Override;

use function Zenstruck\Foundry\faker;

class EventResourceTest extends ApiTestCase
{
    use AuthenticatedClientTrait, GraphQlTestTrait, RegexTrait;

    /**
     * If you're using RecreateDatabaseTrait, RefreshDatabaseTrait, ReloadDatabaseTrait from theofidry/AliceBundle, you
     * probably need to set this property to false in your test class to avoid recreating the database on each client creation.
     *
     * - `null` triggers a deprecation message and always boots the kernel
     * - `false` does not boot the kernel if it's already booted
     * - `true` always boots the kernel without any deprecation message
     */
    protected static ?bool $alwaysBootKernel = true;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        UserFactory::createMany(3);

        foreach (UserFactory::all() as $user) {
            EventFactory::new()
                ->isCritical()
                ->reuse($user)
                ->create();

            EventFactory::new()
                ->withEarlyNotification(7)
                ->reuse($user)
                ->create();

            EventFactory::new()
                ->withRepeatInterval(30 * 3)
                ->reuse($user)
                ->create();

            EventFactory::new()
                ->isCritical()
                ->withEarlyNotification(7)
                ->withRepeatInterval(15)
                ->reuse($user)
                ->create();
        }
    }

    public function testGetCollectionReturnsOwnedEventsAsRegularUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query Events {
                events {
                    id
                    name
                    date
                    repeatInterval
                    earlyNotification
                    critical
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Events')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertCount(4, $body['data']['events']);

        foreach ($body['data']['events'] as $event) {
            $this->assertMatchesIri($event['id'], EventResource::class);
            $this->assertArrayHasKey('name', $event);
            $this->assertArrayHasKey('repeatInterval', $event);
            $this->assertArrayHasKey('earlyNotification', $event);
            $this->assertIsBool($event['critical']);
            $this->assertMatchesDate($event['date']);
            $this->assertMatchesDate($event['createdAt']);
            $this->assertMatchesDate($event['updatedAt']);
        }
    }

    public function testGetCollectionReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            query Events {
                events {
                    id
                    name
                    date
                    repeatInterval
                    earlyNotification
                    critical
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Events')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['events']);
    }

    public function testGetItemReturnsOwnedEventAsRegularUser(): void
    {
        $user = UserFactory::random();

        // Get event with early notification and repeat interval
        $events = EventFactory::findBy(['user' => $user]);
        $candidates = array_filter($events, fn(EventEntity $e) => $e->getEarlyNotification() !== null && $e->getRepeatInterval() !== null);
        $event = $candidates[array_rand($candidates)];

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query Event($id: ID!) {
                event(id: $id) {
                    id
                    name
                    date
                    repeatInterval
                    earlyNotification
                    critical
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Event')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['event']['id'], EventResource::class);
        $this->assertSame($event->getName(), $body['data']['event']['name']);
        $this->assertSame($event->getDate()->format(DateTimeInterface::ATOM), $body['data']['event']['date']);
        $this->assertSame($event->getRepeatInterval()->format('P%yY%mM%dDT%hH%iM%sS'), $body['data']['event']['repeatInterval']);
        $this->assertSame($event->getEarlyNotification()->format('P%yY%mM%dDT%hH%iM%sS'), $body['data']['event']['earlyNotification']);
        $this->assertSame($event->isCritical(), $body['data']['event']['critical']);
        $this->assertSame($event->getCreatedAt()->format(DateTimeInterface::ATOM), $body['data']['event']['createdAt']);
        $this->assertSame($event->getUpdatedAt()->format(DateTimeInterface::ATOM), $body['data']['event']['updatedAt']);
    }

    public function testGetItemReturnsNullForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $event = EventFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            query Event($id: ID!) {
                event(id: $id) {
                    id
                    name
                    date
                    repeatInterval
                    earlyNotification
                    critical
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Event')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNull($body['data']['event']);
    }

    public function testGetItemReturns403ForAnonymousUser(): void
    {
        $event = EventFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            query Event($id: ID!) {
                event(id: $id) {
                    id
                    name
                    date
                    repeatInterval
                    earlyNotification
                    critical
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Event')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['event']);
    }

    public function testPostCreatesEvent(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Event Name',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['createEvent']['event']['id'], EventResource::class);
        $this->assertSame('Test Event Name', $body['data']['createEvent']['event']['name']);
        $this->assertSame('2026-10-28T10:00:00+00:00', $body['data']['createEvent']['event']['date']);
        $this->assertSame('P0Y0M30DT0H0M0S', $body['data']['createEvent']['event']['repeatInterval']);
        $this->assertSame('P0Y0M7DT0H0M0S', $body['data']['createEvent']['event']['earlyNotification']);
        $this->assertSame(false, $body['data']['createEvent']['event']['critical']);
        $this->assertMatchesDate($body['data']['createEvent']['event']['createdAt']);
        $this->assertMatchesDate($body['data']['createEvent']['event']['updatedAt']);
    }

    public function testPostCreatesEventWithCriticality(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Event Name',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => true,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['createEvent']['event']['id'], EventResource::class);
        $this->assertSame('Test Event Name', $body['data']['createEvent']['event']['name']);
        $this->assertSame('2026-10-28T10:00:00+00:00', $body['data']['createEvent']['event']['date']);
        $this->assertSame('P0Y0M30DT0H0M0S', $body['data']['createEvent']['event']['repeatInterval']);
        $this->assertSame('P0Y0M7DT0H0M0S', $body['data']['createEvent']['event']['earlyNotification']);
        $this->assertSame(true, $body['data']['createEvent']['event']['critical']);
        $this->assertMatchesDate($body['data']['createEvent']['event']['createdAt']);
        $this->assertMatchesDate($body['data']['createEvent']['event']['updatedAt']);
    }

    public function testPostReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Event Name',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['createEvent']);
    }

    public function testPostReturns422WhenNameIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value should not be blank.');
        $this->assertNull($body['data']['createEvent']);
    }

    public function testPostReturns422WhenNameIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => '',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value should not be blank.');
        $this->assertNull($body['data']['createEvent']);
    }

    public function testPostReturns422WhenNameIsTooLong(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => faker()->text(1000),
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value is too long. It should have 100 characters or less.');
        $this->assertNull($body['data']['createEvent']);
    }

    public function testPostReturns422WhenDateIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Event Name',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'date', 'This value should not be blank.');
        $this->assertNull($body['data']['createEvent']);
    }

    public function testPostReturns422WhenDateIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Event Name',
            'date' => '',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'date', 'This value should not be blank.');
        $this->assertNull($body['data']['createEvent']);
    }

    public function testPostReturns422WhenDateIsInvalidValue(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Event Name',
            'date' => 'invalid_value',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'date', 'Invalid value. Enter a date in the ATOM (Y-m-d\TH:i:sP) format.');
        $this->assertNull($body['data']['createEvent']);
    }

    public function testPostReturns422WhenRepeatIntervalIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Event Name',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => '',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'repeatInterval', 'This value should not be blank.');
        $this->assertNull($body['data']['createEvent']);
    }

    public function testPostReturns422WhenRepeatIntervalIsInvalidValue(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Event Name',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'invalid_value',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'repeatInterval', 'Invalid value. Enter an interval in the ISO-8601 (P0Y0M0DT0H0M0S) format.');
        $this->assertNull($body['data']['createEvent']);
    }

    public function testPostReturns422WhenEarlyNotificationIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Event Name',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => '',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'earlyNotification', 'This value should not be blank.');
        $this->assertNull($body['data']['createEvent']);
    }

    public function testPostReturns422WhenEarlyNotificationIsInvalidValue(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Event Name',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => 'invalid_value',
            'critical' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'earlyNotification', 'Invalid value. Enter an interval in the ISO-8601 (P0Y0M0DT0H0M0S) format.');
        $this->assertNull($body['data']['createEvent']);
    }

    public function testPostReturns422WhenCriticalIsNotBooleanOrNull(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'name' => 'Test Event Name',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P0Y0M30DT0H0M0S',
            'earlyNotification' => 'P0Y0M7DT0H0M0S',
            'critical' => 'invalid_value',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreateEvent')->toArray(false);

        $this->assertGraphQlError($body, 'Variable "$critical" got invalid value "invalid_value"; Boolean cannot represent a non boolean value: "invalid_value"');
    }

    public function testPatchUpdatesEvent(): void
    {
        $user = UserFactory::random();
        $event = EventFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                updateEvent(input: { id: $id, name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'name' => 'Updated Event Name',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P5Y0M0DT0H0M0S',
            'earlyNotification' => 'P0Y1M0DT0H0M0S',
            'critical' => true,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame(sprintf('/events/%s', $event->getId()->toRfc4122()), $body['data']['updateEvent']['event']['id']);
        $this->assertSame('Updated Event Name', $body['data']['updateEvent']['event']['name']);
        $this->assertSame('2026-10-28T10:00:00+00:00', $body['data']['updateEvent']['event']['date']);
        $this->assertSame('P5Y0M0DT0H0M0S', $body['data']['updateEvent']['event']['repeatInterval']);
        $this->assertSame('P0Y1M0DT0H0M0S', $body['data']['updateEvent']['event']['earlyNotification']);
        $this->assertSame(true, $body['data']['updateEvent']['event']['critical']);
        $this->assertMatchesDate($body['data']['updateEvent']['event']['createdAt']);
        $this->assertMatchesDate($body['data']['updateEvent']['event']['updatedAt']);
    }

    public function testPatchReturns403ForAnonymousUser(): void
    {
        $event = EventFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                updateEvent(input: { id: $id, name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'name' => 'Updated Event Name',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P5Y0M0DT0H0M0S',
            'earlyNotification' => 'P0Y1M0DT0H0M0S',
            'critical' => true,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['updateEvent']);
    }

    public function testPatchReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $event = EventFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
                updateEvent(input: { id: $id, name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'name' => 'Updated Event Name',
            'date' => '2026-10-28T10:00:00+00:00',
            'repeatInterval' => 'P5Y0M0DT0H0M0S',
            'earlyNotification' => 'P0Y1M0DT0H0M0S',
            'critical' => true,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "/events/%s" not found.', $event->getId()->toRfc4122()));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "/events/%s" not found.', $event->getId()->toRfc4122()));
        $this->assertNull($body['data']['updateEvent']);
    }

    public function testPatchReturns422WhenNameIsBlank(): void
    {
        $user = UserFactory::random();
        $event = EventFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $name: String!) {
                updateEvent(input: { id: $id, name: $name }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'name' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value should not be blank.');
        $this->assertNull($body['data']['updateEvent']);
    }

    public function testPatchReturns422WhenNameIsTooLong(): void
    {
        $user = UserFactory::random();
        $event = EventFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $name: String!) {
                updateEvent(input: { id: $id, name: $name }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'name' => faker()->text(1000),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'name', 'This value is too long. It should have 100 characters or less.');
        $this->assertNull($body['data']['updateEvent']);
    }

    public function testPatchReturns422WhenDateIsBlank(): void
    {
        $user = UserFactory::random();
        $event = EventFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $date: String!) {
                updateEvent(input: { id: $id, date: $date }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'date' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'date', 'This value should not be blank.');
        $this->assertNull($body['data']['updateEvent']);
    }

    public function testPatchReturns422WhenDateIsInvalidValue(): void
    {
        $user = UserFactory::random();
        $event = EventFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $date: String!) {
                updateEvent(input: { id: $id, date: $date }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'date' => 'invalid_value',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'date', 'Invalid value. Enter a date in the ATOM (Y-m-d\TH:i:sP) format.');
        $this->assertNull($body['data']['updateEvent']);
    }

    public function testPatchReturns422WhenRepeatIntervalIsBlank(): void
    {
        $user = UserFactory::random();
        $event = EventFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $repeatInterval: String) {
                updateEvent(input: { id: $id, repeatInterval: $repeatInterval }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'repeatInterval' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'repeatInterval', 'This value should not be blank.');
        $this->assertNull($body['data']['updateEvent']);
    }

    public function testPatchReturns422WhenRepeatIntervalIsInvalidValue(): void
    {
        $user = UserFactory::random();
        $event = EventFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $repeatInterval: String) {
                updateEvent(input: { id: $id, repeatInterval: $repeatInterval }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'repeatInterval' => 'invalid_value',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'repeatInterval', 'Invalid value. Enter an interval in the ISO-8601 (P0Y0M0DT0H0M0S) format.');
        $this->assertNull($body['data']['updateEvent']);
    }

    public function testPatchReturns422WhenEarlyNotificationIsBlank(): void
    {
        $user = UserFactory::random();
        $event = EventFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $earlyNotification: String) {
                updateEvent(input: { id: $id, earlyNotification: $earlyNotification }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'earlyNotification' => '',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'earlyNotification', 'This value should not be blank.');
        $this->assertNull($body['data']['updateEvent']);
    }

    public function testPatchReturns422WhenEarlyNotificationIsInvalidValue(): void
    {
        $user = UserFactory::random();
        $event = EventFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $earlyNotification: String) {
                updateEvent(input: { id: $id, earlyNotification: $earlyNotification }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'earlyNotification' => 'invalid_value',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'earlyNotification', 'Invalid value. Enter an interval in the ISO-8601 (P0Y0M0DT0H0M0S) format.');
        $this->assertNull($body['data']['updateEvent']);
    }

    public function testPatchReturns422WhenCriticalIsNotBooleanOrNull(): void
    {
        $user = UserFactory::random();
        $event = EventFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdateEvent($id: ID!, $critical: Boolean) {
                updateEvent(input: { id: $id, critical: $critical }) {
                    event {
                        id
                        name
                        date
                        repeatInterval
                        earlyNotification
                        critical
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
            'critical' => 'invalid_value',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdateEvent')->toArray(false);

        $this->assertGraphQlError($body, 'Variable "$critical" got invalid value "invalid_value"; Boolean cannot represent a non boolean value: "invalid_value"');
    }

    public function testDeleteRemovesEvent(): void
    {
        $user = UserFactory::random();
        $event = EventFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation DeleteEvent($id: ID!) {
                deleteEvent(input: { id: $id }) {
                    event {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteEvent')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNull(EventFactory::repository()->find($event->getId()));
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $event = EventFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation DeleteEvent($id: ID!) {
                deleteEvent(input: { id: $id }) {
                    event {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, sprintf('Item "/events/%s" not found.', $event->getId()->toRfc4122()));
        $this->assertGraphQlDebugMessage($body, sprintf('Item "/events/%s" not found.', $event->getId()->toRfc4122()));
        $this->assertNull($body['data']['deleteEvent']);
    }

    public function testDeleteReturns403ForAnonymousUser(): void
    {
        $event = EventFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation DeleteEvent($id: ID!) {
                deleteEvent(input: { id: $id }) {
                    event {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => sprintf('/events/%s', $event->getId()->toRfc4122()),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeleteEvent')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['deleteEvent']);
    }
}
