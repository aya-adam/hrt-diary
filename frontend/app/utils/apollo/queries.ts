import { gql, type TypedDocumentNode } from "@apollo/client";

/************ Login Mutation ************/

type LoginMutationResponse = {
    authLogin: {
        login: {
            userId: string;
            userIdentifier: string;
            accessTokenExpiresAt: string;
        },
    },
}

type LoginMutationInput = {
    email: string;
    password: string;
}

export const LOGIN_MUTATION: TypedDocumentNode<LoginMutationResponse, LoginMutationInput> = gql`
    mutation AuthLogin($email: String!, $password: String!) {
        authLogin(input: { email: $email, password: $password }) {
            login {
                userId
                userIdentifier
                accessTokenExpiresAt
            }
        }
    }
`;

/************ Token Refresh Mutation ************/

type TokenRefreshMutationResponse = {
    authTokenRefresh: {
        tokenRefresh: {
            userId: string;
            userIdentifier: string;
            accessTokenExpiresAt: string;
        },
    },
}

export const REFRESH_MUTATION: TypedDocumentNode<TokenRefreshMutationResponse> = gql`
    mutation AuthTokenRefresh {
        authTokenRefresh(input: {  }) {
            tokenRefresh {
                userId
                userIdentifier
                accessTokenExpiresAt
            }
        }
    }
`;

/************ Logout Mutation ************/

type LogoutMutationResponse = {
    authLogout: {
        logout: {
            message: string;
        },
    },
}

export const LOGOUT_MUTATION: TypedDocumentNode<LogoutMutationResponse> = gql`
    mutation AuthLogout {
        authLogout(input: {  }) {
            logout {
                message
            }
        }
    }
`;

/************ Create User Mutation ************/

type CreateUserMutationResponse = {
    createUser: {
        user: {
            id: string;
            email: string;
            createdAt: string;
            updatedAt: string;
        },
    },
}

type CreateUserMutationInput = {
    email: string;
    plainPassword: string;
}

export const CREATE_USER_MUTATION: TypedDocumentNode<CreateUserMutationResponse, CreateUserMutationInput> = gql`
    mutation CreateUser($email: String!, $plainPassword: String!) {
        createUser(input: { email: $email, plainPassword: $plainPassword }) {
            user {
                id
                email
                createdAt
                updatedAt
            }
        }
    }
`;