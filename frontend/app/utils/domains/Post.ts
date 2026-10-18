import { gql, type TypedDocumentNode } from "@apollo/client";
import type { Moment } from "moment";
import moment from "moment";
import { assertDefined } from "../assertions";

export type PostDto = {
    id: string,
    title: string,
    excerpt: string | null,
    text: string | null,
    publishedAt: string | null,
    createdAt: string,
    updatedAt: string,
};

type PostItemIdentifierInput = {
    id: string,
};

type PostCreateMutationInput = {
    title: string,
    excerpt?: string,
    text?: string,
    published?: boolean,
};

type PostUpdateMutationInput = {
    id: PostItemIdentifierInput['id'],
    title?: string,
    excerpt?: string,
    text?: string,
    published?: boolean,
};

type PostCollectionQueryResponse = {
    posts: PostDto[],
};

type PostItemQueryResponse = {
    post: PostDto,
};

type PostCreateMutationResponse = {
    createPost: {
        post: PostDto,
    },
};

type PostUpdateMutationResponse = {
    updatePost: {
        post: PostDto,
    },
};

type PostDeleteMutationResponse = {
    deletePost: {
        post: {
            id: PostDto['id'],
        },
    },
};

export class Post {
    readonly id: string;
    readonly title: string;
    readonly excerpt?: string;
    readonly text?: string;
    readonly publishedAt?: Moment;
    readonly createdAt: Moment;
    readonly updatedAt: Moment;

    static readonly COLLECTION_QUERY: TypedDocumentNode<PostCollectionQueryResponse> = gql`
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
    `;

    static readonly ITEM_QUERY: TypedDocumentNode<PostItemQueryResponse, PostItemIdentifierInput> = gql`
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
    `;

    static readonly CREATE_MUTATION: TypedDocumentNode<PostCreateMutationResponse, PostCreateMutationInput> = gql`
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
    `;

    static readonly UPDATE_MUTATION: TypedDocumentNode<PostUpdateMutationResponse, PostUpdateMutationInput> = gql`
        mutation UpdatePost($id: ID!, $title: String, $excerpt: String, $text: String, $published: Boolean) {
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
    `;

    static readonly DELETE_MUTATION: TypedDocumentNode<PostDeleteMutationResponse, PostItemIdentifierInput> = gql`
        mutation UpdatePost($id: ID!) {
            updatePost(input: { id: $id }) {
                post {
                    id
                }
            }
        }
    `;

    constructor(id: string, title: string, excerpt: string | null | undefined, text: string | null | undefined, publishedAt: string | null | undefined, createdAt: string, updatedAt: string) {
        this.id = id;
        this.title = title;
        this.excerpt = excerpt ?? undefined;
        this.text = text ?? undefined;
        this.publishedAt = publishedAt ? moment(publishedAt) : undefined;
        this.createdAt = moment(createdAt);
        this.updatedAt = moment(updatedAt);
    };

    static fromDto(dto: PostDto | undefined): Post {
        assertDefined(dto, 'PostDto');

        return new Post(
            dto.id,
            dto.title,
            dto.excerpt,
            dto.text,
            dto.publishedAt,
            dto.createdAt,
            dto.updatedAt,
        );
    }
}