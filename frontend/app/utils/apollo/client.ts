import { HttpLink, InMemoryCache, ApolloLink, ApolloClient } from "@apollo/client";
import { refreshLink } from "./refreshLink";
import { errorLink } from "./errorLink";

const httpLink = new HttpLink({
    uri: import.meta.env.VITE_GRAPHQL_ENDPOINT_URL,
    credentials: "include",
});

export const apolloClient = new ApolloClient({
    link: ApolloLink.from([errorLink, refreshLink, httpLink]),
    cache: new InMemoryCache(),
});
