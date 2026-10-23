import { ErrorLink } from "@apollo/client/link/error";
import {
    CombinedGraphQLErrors,
    CombinedProtocolErrors,
    LocalStateError,
    ServerError,
    ServerParseError,
    UnconventionalError,
} from "@apollo/client/errors";
import { from } from "rxjs";
import { mergeMap } from "rxjs/operators";
import { runExclusiveRefresh } from "./authState";
import { performRefresh } from "./refreshLink";

export const errorLink = new ErrorLink(({ error, operation, forward }) => {
    const isAuthError =
        CombinedGraphQLErrors.is(error) && error.errors.some((graphQLError) => graphQLError.extensions?.status === 401);

    if (!isAuthError || operation.operationName === "RefreshAuth") {
        return; // not retrying: let the error propagate as-is
    }

    return from(runExclusiveRefresh(performRefresh)).pipe(mergeMap(() => forward(operation)));
});

export const getErrorMessage = (error: unknown) => {
    let message: string | undefined = undefined;
    let messageArr: string[] = [];

    if (CombinedGraphQLErrors.is(error)) {
        error.errors.forEach((graphQlError) => {
            messageArr.push(graphQlError.message);
        });
        message = messageArr.join("\n");
    } else if (CombinedProtocolErrors.is(error)) {
        error.errors.forEach((protocolError) => {
            messageArr.push(protocolError.message);
        });
        message = messageArr.join("\n");
    } else if (LocalStateError.is(error)) {
        if (error.path) {
            messageArr.push(`An error occured at field path: ${error.path.join(".")}`);
        }
        message = messageArr.join("\n");
    } else if (ServerError.is(error)) {
        message = `Server error (${error.statusCode}).`;
        messageArr.push(message);
    } else if (ServerParseError.is(error)) {
        message = `Failed to parse response from "". Server status ${error.statusCode}.`;
        messageArr.push(message);
    } else if (UnconventionalError.is(error)) {
        if (typeof error.cause === "symbol") {
            message = `A symbol was thrown: ${error.cause.toString()}`;
        } else if (typeof error.cause === "object") {
            message = "An object was thrown.";
        } else {
            message = "Unexpected value thrown.";
        }
        messageArr.push(message);
    } else if (error instanceof Error) {
        message = String(error.message) ?? "An unknown error occured.";
        messageArr.push(message);
    } else {
        message = "An unknown error occured.";
        messageArr.push(message);
    }

    return { message, messageArr };
};
