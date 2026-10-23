import { gql, type TypedDocumentNode } from "@apollo/client";
import { assertDefined } from "../assertions";
import { User, type UserDto } from "./User";

export type AuthDto = {
    message: string,
    accessTokenExpiresAt: string | null,
    user: UserDto | null,
};

type AuthLoginMutationInput = {
    email: string,
    password: string,
};

type AuthLoginMutationResponse = {
    getAuth: {
        auth: AuthDto,
    },
};

type AuthRefreshMutationResponse = {
    refreshAuth: {
        auth: AuthDto,
    },
};

type AuthLogoutMutationResponse = {
    revokeAuth: {
        auth: {
            message: AuthDto['message'],
        },
    },
};

export class Auth {
    readonly message: string;
    readonly accessTokenExpiresAt?: number;
    readonly user?: User;

    static readonly LOGIN_MUTATION: TypedDocumentNode<AuthLoginMutationResponse, AuthLoginMutationInput> = gql`
        mutation GetAuth($email: String!, $password: String!) {
            getAuth(input: { email: $email, password: $password }) {
                auth {
                    message
                    accessTokenExpiresAt
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        }
    `;

    static readonly REFRESH_MUTATION: TypedDocumentNode<AuthRefreshMutationResponse> = gql`
        mutation RefreshAuth {
            refreshAuth(input: {  }) {
                auth {
                    message
                    accessTokenExpiresAt
                    user {
                        id
                        email
                        roles
                        createdAt
                        updatedAt
                    }
                }
            }
        }
    `;

    static readonly LOGOUT_MUTATION: TypedDocumentNode<AuthLogoutMutationResponse> = gql`
        mutation RevokeAuth {
            revokeAuth(input: {  }) {
                auth {
                    message
                }
            }
        }
    `;

    constructor(message: string, accessTokenExpiresAt: string | null | undefined, user: UserDto | null | undefined) {
        this.message = message;
        this.accessTokenExpiresAt = accessTokenExpiresAt ? Number(accessTokenExpiresAt) : undefined;
        this.user = user ? User.fromDto(user) : undefined;
    };

    static fromDto(dto: AuthDto | undefined): Auth {
        assertDefined(dto, "AuthDto");

        return new Auth(
            dto.message,
            dto.accessTokenExpiresAt,
            dto.user,
        );
    }
}