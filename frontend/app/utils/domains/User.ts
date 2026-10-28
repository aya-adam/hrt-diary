import { gql, type TypedDocumentNode } from "@apollo/client";
import type { Moment } from "moment-timezone";
import moment from "moment-timezone";
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

type UserPasswordUpdateMutationInput = {
    id: UserItemIdentifierInput['id'],
    oldPassword: string,
    newPassword: string,
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

type UserPasswordUpdateMutationResponse = {
    updatePasswordUser: {
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

type CheckPasswordStrengthMutationInput = {
    password: string,
};

type CheckPasswordStrengthMutationResponse = {
    checkPasswordStrength: {
        passwordStrength: {
            estimatedStrength: number,
            message: string,
        },
    },
};

const USER_IRI_PREFIX = "/users/";

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

    static readonly UPDATE_PASSWORD_MUTATION: TypedDocumentNode<UserPasswordUpdateMutationResponse, UserPasswordUpdateMutationInput> = gql`
        mutation UpdatePasswordUser($id: ID!, $oldPassword: String!, $newPassword: String!) {
            updatePasswordUser(input: { id: $id, oldPassword: $oldPassword, newPassword: $newPassword }) {
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

    static readonly CHECK_PW_STRENGTH_MUTATION: TypedDocumentNode<CheckPasswordStrengthMutationResponse, CheckPasswordStrengthMutationInput> = gql`
        mutation CheckPasswordStrength($password: String!) {
            checkPasswordStrength(input: { password: $password }) {
                passwordStrength {
                    estimatedStrength
                    message
                }
            }
        }
    `;

    constructor(id: string, email: string, roles: string[], createdAt: string, updatedAt: string) {
        this.id = User.toUuid(id);
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

    static toUuid(id: string): string {
        return id.replace(USER_IRI_PREFIX, "");
    }

    static toIri(uuid: string): string {
        return uuid.startsWith(USER_IRI_PREFIX) ? uuid : `${USER_IRI_PREFIX}${uuid}`;
    }
}