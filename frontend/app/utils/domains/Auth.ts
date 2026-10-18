import { gql, type TypedDocumentNode } from "@apollo/client";
import { assertDefined } from "../assertions";

export type AuthDto = {
    message: string,
    accessTokenExpiresAt: string | null,
    userId: string | null,
    userIdentifier: string | null,
};

type AuthLoginMutationInput = {
    email: string,
    password: string,
};

type AuthLoginMutationResponse = {
    authLogin: {
        login: AuthDto,
    },
};

type AuthRefreshMutationResponse = {
    authTokenRefresh: {
        tokenRefresh: AuthDto,
    },
};

type AuthLogoutMutationResponse = {
    authLogout: {
        logout: {
            message: AuthDto['message'],
        },
    },
};

export class Auth {
    readonly message: string;
    readonly accessTokenExpiresAt?: number;
    readonly userId?: string;
    readonly userIdentifier?: string;

    static readonly LOGIN_MUTATION: TypedDocumentNode<AuthLoginMutationResponse, AuthLoginMutationInput> = gql`
        mutation AuthLogin($email: String!, $password: String!) {
            authLogin(input: { email: $email, password: $password }) {
                login {
                    message
                    accessTokenExpiresAt
                    userId
                    userIdentifier
                }
            }
        }
    `;

    static readonly REFRESH_MUTATION: TypedDocumentNode<AuthRefreshMutationResponse> = gql`
        mutation AuthTokenRefresh {
            authTokenRefresh(input: {  }) {
                tokenRefresh {
                    message
                    accessTokenExpiresAt
                    userId
                    userIdentifier
                }
            }
        }
    `;

    static readonly LOGOUT_MUTATION: TypedDocumentNode<AuthLogoutMutationResponse> = gql`
        mutation AuthLogout {
            authLogout(input: {  }) {
                logout {
                    message
                }
            }
        }
    `;

    constructor(message: string, accessTokenExpiresAt: string | null | undefined, userId: string | null | undefined, userIdentifier: string | null | undefined) {
        this.message = message;
        this.accessTokenExpiresAt = accessTokenExpiresAt ? Number(accessTokenExpiresAt) : undefined;
        this.userId = userId ?? undefined;
        this.userIdentifier = userIdentifier ?? undefined;
    };

    static fromDto(dto: AuthDto | undefined): Auth {
        assertDefined(dto, "AuthDto");

        return new Auth(
            dto.message,
            dto.accessTokenExpiresAt,
            dto.userId,
            dto.userIdentifier,
        );
    }
}