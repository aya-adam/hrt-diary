import { gql, type TypedDocumentNode } from "@apollo/client";
import type { Moment } from "moment";
import moment from "moment";
import { assertDefined } from "../assertions";

export type UserDto = {
    id: string,
    email: string,
    roles: string[],
    createdAt: string,
    updatedAt: string,
};

type UserItemIdentifierInput = {
    id: string,
};

type UserCreateMutationInput = {
    email: string,
    password: string,
};

type UserUpdateMutationInput = {
    id: UserItemIdentifierInput['id'],
    email?: string,
    password?: string,
};

type UserCollectionQueryResponse = {
    users: UserDto[],
};

type UserItemQueryResponse = {
    user: UserDto,
};

type UserCreateMutationResponse = {
    createUser: {
        user: UserDto,
    },
};

type UserUpdateMutationResponse = {
    updateUser: {
        user: UserDto,
    },
};

type UserDeleteMutationResponse = {
    deleteUser: {
        user: {
            id: UserDto['id'],
        },
    },
};

export class User {
    readonly id: string;
    readonly email: string;
    readonly roles: string[];
    readonly createdAt: Moment;
    readonly updatedAt: Moment;

    static readonly COLLECTION_QUERY: TypedDocumentNode<UserCollectionQueryResponse> = gql`
        query Users {
            users {
                id
                email
                roles
                createdAt
                updatedAt
            }
        }
    `;

    static readonly ITEM_QUERY: TypedDocumentNode<UserItemQueryResponse, UserItemIdentifierInput> = gql`
        query User($id: ID!) {
            user(id: $id) {
                id
                email
                roles
                createdAt
                updatedAt
            }
        }
    `;

    static readonly CREATE_MUTATION: TypedDocumentNode<UserCreateMutationResponse, UserCreateMutationInput> = gql`
        mutation CreateUser($email: String!, $password: String!) {
            createUser(input: { email: $email, password: $password }) {
                user {
                    id
                    email
                    roles
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly UPDATE_MUTATION: TypedDocumentNode<UserUpdateMutationResponse, UserUpdateMutationInput> = gql`
        mutation UpdateUser($id: ID!, $email: String, $password: String) {
            updateUser(input: { id: $id, email: $email, password: $password }) {
                user {
                    id
                    email
                    roles
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly DELETE_MUTATION: TypedDocumentNode<UserDeleteMutationResponse, UserItemIdentifierInput> = gql`
        mutation UpdateUser($id: ID!) {
            updateUser(input: { id: $id }) {
                user {
                    id
                }
            }
        }
    `;

    constructor(id: string, email: string, roles: string[], createdAt: string, updatedAt: string) {
        this.id = id;
        this.email = email;
        this.roles = roles;
        this.createdAt = moment(createdAt);
        this.updatedAt = moment(updatedAt);
    };

    static fromDto(dto: UserDto | undefined): User {
        assertDefined(dto, "UserDto");

        return new User(
            dto.id,
            dto.email,
            dto.roles,
            dto.createdAt,
            dto.updatedAt,
        );
    }
}