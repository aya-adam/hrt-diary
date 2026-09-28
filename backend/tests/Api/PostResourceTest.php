<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Post;
use App\Factory\PostFactory;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatedClientTrait;
use App\Tests\Support\GraphQlTestTrait;
use App\Tests\Support\RegexTrait;
use Override;

use function Zenstruck\Foundry\faker;

class PostResourceTest extends ApiTestCase
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

        UserFactory::createMany(10);

        foreach (UserFactory::all() as $user) {
            PostFactory::createMany(10, ['user' => $user]);
        }
    }

    public function testGetCollectionReturnsOwnedPostsAsRegularUser(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query Posts {
                posts {
                    id
                    title
                    excerpt
                    text
                    publishedAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Posts')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertCount(10, $body['data']['posts']);

        foreach ($body['data']['posts'] as $post) {
            $this->assertMatchesIri($post['id'], Post::class);
            $this->assertIsString($post['title']);
            $this->assertArrayHasKey('excerpt', $post);
            $this->assertArrayHasKey('text', $post);
            $this->assertArrayHasKey('publishedAt', $post);
            $this->assertMatchesDate($post['createdAt']);
            $this->assertMatchesDate($post['updatedAt']);
        }
    }

    public function testGetCollectionReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            query Posts {
                posts {
                    id
                    title
                    excerpt
                    text
                    publishedAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $body = $this->executeGraphQl($client, $query, [], 'Posts')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['posts']);
    }

    public function testGetItemReturnsOwnedPostAsRegularUser(): void
    {
        $user = UserFactory::random();
        $post = PostFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            query Post($id: ID!) {
                post(id: $id) {
                    id
                    title
                    excerpt
                    text
                    publishedAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Post')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertSame($this->getIriFromResource($post), $body['data']['post']['id']);
        $this->assertSame($post->getTitle(), $body['data']['post']['title']);
        $this->assertSame($post->getExcerpt(), $body['data']['post']['excerpt']);
        $this->assertSame($post->getText(), $body['data']['post']['text']);
        $this->assertSame($post->getCreatedAt()->format('c'), $body['data']['post']['createdAt']);
        $this->assertSame($post->getUpdatedAt()->format('c'), $body['data']['post']['updatedAt']);
    }

    public function testGetItemReturnsNullForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $post = PostFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            query Post($id: ID!) {
                post(id: $id) {
                    id
                    title
                    excerpt
                    text
                    publishedAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Post')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertNull($body['data']['post']);
    }

    public function testGetItemReturns403ForAnonymousUser(): void
    {
        $post = PostFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            query Post($id: ID!) {
                post(id: $id) {
                    id
                    title
                    excerpt
                    text
                    publishedAt
                    createdAt
                    updatedAt
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'Post')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['post']);
    }

    public function testPostCreatesPost(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreatePost($title: String!, $excerpt: String, $text: String) {
                createPost(input: { title: $title, excerpt: $excerpt, text: $text }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'title' => 'New post title',
            'excerpt' => 'New post excerpt',
            'text' => 'New post text',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreatePost')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['createPost']['post']['id'], Post::class);
        $this->assertSame('New post title', $body['data']['createPost']['post']['title']);
        $this->assertSame('New post excerpt', $body['data']['createPost']['post']['excerpt']);
        $this->assertSame('New post text', $body['data']['createPost']['post']['text']);
        $this->assertNull($body['data']['createPost']['post']['publishedAt']);
        $this->assertMatchesDate($body['data']['createPost']['post']['createdAt']);
        $this->assertMatchesDate($body['data']['createPost']['post']['updatedAt']);
    }

    public function testPostCreatesPublishedPost(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreatePost($title: String!, $excerpt: String, $text: String, $published: Boolean) {
                createPost(input: { title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'title' => 'New post title',
            'excerpt' => 'New post excerpt',
            'text' => 'New post text',
            'published' => true,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreatePost')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['createPost']['post']['id'], Post::class);
        $this->assertSame('New post title', $body['data']['createPost']['post']['title']);
        $this->assertSame('New post excerpt', $body['data']['createPost']['post']['excerpt']);
        $this->assertSame('New post text', $body['data']['createPost']['post']['text']);
        $this->assertMatchesDate($body['data']['createPost']['post']['publishedAt']);
        $this->assertMatchesDate($body['data']['createPost']['post']['createdAt']);
        $this->assertMatchesDate($body['data']['createPost']['post']['updatedAt']);
    }

    public function testPostReturns403ForAnonymousUser(): void
    {
        $client = self::createClient();

        $query = <<<'GQL'
            mutation CreatePost($title: String!, $excerpt: String, $text: String, $published: Boolean) {
                createPost(input: { title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'title' => 'New post title',
            'excerpt' => 'New post excerpt',
            'text' => 'New post text',
            'published' => false,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['createPost']);
    }

    public function testPostReturns422WhenTitleIsMissing(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreatePost($excerpt: String, $text: String, $published: Boolean) {
                createPost(input: { excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'excerpt' => 'New post excerpt',
            'text' => 'New post text',
            'published' => false,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreatePost')->toArray(false);

        $this->assertGraphQlError($body, 'Field createPostInput.title of required type String! was not provided.');
    }

    public function testPostReturns422WhenTitleIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreatePost($title: String!, $excerpt: String, $text: String, $published: Boolean) {
                createPost(input: { title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'title' => '',
            'excerpt' => 'New post excerpt',
            'text' => 'New post text',
            'published' => false,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'title', 'This value should not be blank.');
        $this->assertNull($body['data']['createPost']);
    }

    public function testPostReturns422WhenTitleIsTooLong(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreatePost($title: String!, $excerpt: String, $text: String, $published: Boolean) {
                createPost(input: { title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'title' => faker()->text(1000),
            'excerpt' => 'New post excerpt',
            'text' => 'New post text',
            'published' => false,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'title', 'This value is too long. It should have 255 characters or less.');
        $this->assertNull($body['data']['createPost']);
    }

    public function testPostReturns422WhenExcerptIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreatePost($title: String!, $excerpt: String, $text: String, $published: Boolean) {
                createPost(input: { title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'title' => 'New post title',
            'excerpt' => '',
            'text' => 'New post text',
            'published' => false,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'excerpt', 'This value should not be blank.');
        $this->assertNull($body['data']['createPost']);
    }

    public function testPostReturns422WhenExcerptIsTooLong(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreatePost($title: String!, $excerpt: String, $text: String, $published: Boolean) {
                createPost(input: { title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'title' => 'New post title',
            'excerpt' => faker()->text(1000),
            'text' => 'New post text',
            'published' => false,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'excerpt', 'This value is too long. It should have 255 characters or less.');
        $this->assertNull($body['data']['createPost']);
    }

    public function testPostReturns422WhenTextIsBlank(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreatePost($title: String!, $excerpt: String, $text: String, $published: Boolean) {
                createPost(input: { title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'title' => 'New post title',
            'excerpt' => 'New post excerpt',
            'text' => '',
            'published' => false,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'text', 'This value should not be blank.');
        $this->assertNull($body['data']['createPost']);
    }

    public function testPostReturns422WhenPublishedIsNotBooleanOrNull(): void
    {
        $user = UserFactory::random();

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation CreatePost($title: String!, $excerpt: String, $text: String, $published: Boolean) {
                createPost(input: { title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'title' => 'New post title',
            'excerpt' => 'New post excerpt',
            'text' => 'New post text',
            'published' => 'true',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'CreatePost')->toArray(false);

        $this->assertGraphQlError($body, 'Variable "$published" got invalid value "true"; Boolean cannot represent a non boolean value: "true"');
    }

    public function testPatchUpdatesPost(): void
    {
        $user = UserFactory::random();
        $post = PostFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdatePost($id: ID!, $title: String!, $excerpt: String, $text: String, $published: Boolean) {
                updatePost(input: { id: $id, title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
            'title' => 'Updated post title',
            'excerpt' => 'Updated post excerpt',
            'text' => 'Updated post text',
            'published' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdatePost')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['updatePost']['post']['id'], Post::class);
        $this->assertSame('Updated post title', $body['data']['updatePost']['post']['title']);
        $this->assertSame('Updated post excerpt', $body['data']['updatePost']['post']['excerpt']);
        $this->assertSame('Updated post text', $body['data']['updatePost']['post']['text']);
        $this->assertNull($body['data']['updatePost']['post']['publishedAt']);
        $this->assertMatchesDate($body['data']['updatePost']['post']['createdAt']);
        $this->assertMatchesDate($body['data']['updatePost']['post']['updatedAt']);
    }

    public function testPatchUpdatesPostToPublic(): void
    {
        $user = UserFactory::random();
        $post = PostFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdatePost($id: ID!, $title: String!, $excerpt: String, $text: String, $published: Boolean) {
                updatePost(input: { id: $id, title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
            'title' => 'Updated post title',
            'excerpt' => 'Updated post excerpt',
            'text' => 'Updated post text',
            'published' => true,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdatePost')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['updatePost']['post']['id'], Post::class);
        $this->assertSame('Updated post title', $body['data']['updatePost']['post']['title']);
        $this->assertSame('Updated post excerpt', $body['data']['updatePost']['post']['excerpt']);
        $this->assertSame('Updated post text', $body['data']['updatePost']['post']['text']);
        $this->assertMatchesDate($body['data']['updatePost']['post']['publishedAt']);
        $this->assertMatchesDate($body['data']['updatePost']['post']['createdAt']);
        $this->assertMatchesDate($body['data']['updatePost']['post']['updatedAt']);
    }

    public function testPatchUpdatesPostToPrivate(): void
    {
        $user = UserFactory::random();
        $post = PostFactory::new()->published()->create(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdatePost($id: ID!, $title: String!, $excerpt: String, $text: String, $published: Boolean) {
                updatePost(input: { id: $id, title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
            'title' => 'Updated post title',
            'excerpt' => 'Updated post excerpt',
            'text' => 'Updated post text',
            'published' => false,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdatePost')->toArray(false);

        $this->assertGraphQlHasNoErrors($body);
        $this->assertMatchesIri($body['data']['updatePost']['post']['id'], Post::class);
        $this->assertSame('Updated post title', $body['data']['updatePost']['post']['title']);
        $this->assertSame('Updated post excerpt', $body['data']['updatePost']['post']['excerpt']);
        $this->assertSame('Updated post text', $body['data']['updatePost']['post']['text']);
        $this->assertNull($body['data']['updatePost']['post']['publishedAt']);
        $this->assertMatchesDate($body['data']['updatePost']['post']['createdAt']);
        $this->assertMatchesDate($body['data']['updatePost']['post']['updatedAt']);
    }

    public function testPatchReturns403ForAnonymousUser(): void
    {
        $post = PostFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation UpdatePost($id: ID!, $title: String!, $excerpt: String, $text: String, $published: Boolean) {
                updatePost(input: { id: $id, title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
            'title' => 'Updated post title',
            'excerpt' => 'Updated post excerpt',
            'text' => 'Updated post text',
            'published' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['updatePost']);
    }

    public function testPatchReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $post = PostFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation UpdatePost($id: ID!, $title: String!, $excerpt: String, $text: String, $published: Boolean) {
                updatePost(input: { id: $id, title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
            'title' => 'Updated post title',
            'excerpt' => 'Updated post excerpt',
            'text' => 'Updated post text',
            'published' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, "Item \"{$this->getIriFromResource($post)}\" not found.");
        $this->assertGraphQlDebugMessage($body, "Item \"{$this->getIriFromResource($post)}\" not found.");
        $this->assertNull($body['data']['updatePost']);
    }

    public function testPatchReturns422WhenTitleIsBlank(): void
    {
        $user = UserFactory::random();
        $post = PostFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdatePost($id: ID!, $title: String!, $excerpt: String, $text: String, $published: Boolean) {
                updatePost(input: { id: $id, title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
            'title' => '',
            'excerpt' => 'Updated post excerpt',
            'text' => 'Updated post text',
            'published' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'title', 'This value should not be blank.');
        $this->assertNull($body['data']['updatePost']);
    }

    public function testPatchReturns422WhenTitleIsTooLong(): void
    {
        $user = UserFactory::random();
        $post = PostFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdatePost($id: ID!, $title: String!, $excerpt: String, $text: String, $published: Boolean) {
                updatePost(input: { id: $id, title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
            'title' => faker()->text(1000),
            'excerpt' => 'Updated post excerpt',
            'text' => 'Updated post text',
            'published' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'title', 'This value is too long. It should have 255 characters or less.');
        $this->assertNull($body['data']['updatePost']);
    }

    public function testPatchReturns422WhenExcerptIsBlank(): void
    {
        $user = UserFactory::random();
        $post = PostFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdatePost($id: ID!, $title: String!, $excerpt: String, $text: String, $published: Boolean) {
                updatePost(input: { id: $id, title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
            'title' => 'Updated post title',
            'excerpt' => '',
            'text' => 'Updated post text',
            'published' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'excerpt', 'This value should not be blank.');
        $this->assertNull($body['data']['updatePost']);
    }

    public function testPatchReturns422WhenExcerptIsTooLong(): void
    {
        $user = UserFactory::random();
        $post = PostFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdatePost($id: ID!, $title: String!, $excerpt: String, $text: String, $published: Boolean) {
                updatePost(input: { id: $id, title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
            'title' => 'Updated post title',
            'excerpt' => faker()->text(1000),
            'text' => 'Updated post text',
            'published' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'excerpt', 'This value is too long. It should have 255 characters or less.');
        $this->assertNull($body['data']['updatePost']);
    }

    public function testPatchReturns422WhenTextIsBlank(): void
    {
        $user = UserFactory::random();
        $post = PostFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdatePost($id: ID!, $title: String!, $excerpt: String, $text: String, $published: Boolean) {
                updatePost(input: { id: $id, title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
            'title' => 'Updated post title',
            'excerpt' => 'Updated post excerpt',
            'text' => '',
            'published' => null,
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdatePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 422);
        $this->assertGraphQlFieldViolation($body, 'text', 'This value should not be blank.');
        $this->assertNull($body['data']['updatePost']);
    }

    public function testPatchReturns422WhenPublishedIsNotBooleanOrNull(): void
    {
        $user = UserFactory::random();
        $post = PostFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation UpdatePost($id: ID!, $title: String!, $excerpt: String, $text: String, $published: Boolean) {
                updatePost(input: { id: $id, title: $title, excerpt: $excerpt, text: $text, published: $published }) {
                    post {
                        id
                        title
                        excerpt
                        text
                        publishedAt
                        createdAt
                        updatedAt
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
            'title' => 'Updated post title',
            'excerpt' => 'Updated post excerpt',
            'text' => 'Updated post text',
            'published' => 'true',
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'UpdatePost')->toArray(false);

        $this->assertGraphQlError($body, 'Variable "$published" got invalid value "true"; Boolean cannot represent a non boolean value: "true"');
    }

    public function testDeleteRemovesPost(): void
    {
        $user = UserFactory::random();
        $post = PostFactory::random(['user' => $user]);

        $client = self::createAuthenticatedClient($user);

        $query = <<<'GQL'
            mutation DeletePost($id: ID!) {
                deletePost(input: { id: $id }) {
                    post {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeletePost')->toArray(false);

        $this->assertNull(PostFactory::repository()->find($post->getId()));
        $this->assertGraphQlHasNoErrors($body);
    }

    public function testDeleteReturns404ForNonOwnerAsRegularUser(): void
    {
        [$user1, $user2] = UserFactory::randomSet(2);
        $post = PostFactory::random(['user' => $user2]);

        $client = self::createAuthenticatedClient($user1);

        $query = <<<'GQL'
            mutation DeletePost($id: ID!) {
                deletePost(input: { id: $id }) {
                    post {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeletePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 404);
        $this->assertGraphQlError($body, "Item \"{$this->getIriFromResource($post)}\" not found.");
        $this->assertGraphQlDebugMessage($body, "Item \"{$this->getIriFromResource($post)}\" not found.");
        $this->assertNull($body['data']['deletePost']);
    }

    public function testDeleteReturns403ForAnonymousUser(): void
    {
        $post = PostFactory::random();

        $client = self::createClient();

        $query = <<<'GQL'
            mutation DeletePost($id: ID!) {
                deletePost(input: { id: $id }) {
                    post {
                        id
                    }
                }
            }
        GQL;

        $variables = [
            'id' => $this->getIriFromResource($post),
        ];

        $body = $this->executeGraphQl($client, $query, $variables, 'DeletePost')->toArray(false);

        $this->assertGraphQlErrorStatus($body, 403);
        $this->assertGraphQlError($body, 'Access Denied.');
        $this->assertGraphQlDebugMessage($body, 'Access Denied.');
        $this->assertNull($body['data']['deletePost']);
    }
}
