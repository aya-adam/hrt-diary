import { SetContextLink } from "@apollo/client/link/context";
import { isAccessTokenExpiringSoon, runExclusiveRefresh } from "./authState";
import { useAuthStore } from "~/stores/authStore";
import { Auth } from "../domains/Auth";
import { print } from "graphql";

const GRAPHQL_ENDPOINT = import.meta.env.VITE_GRAPHQL_ENDPOINT_URL;

const SKIP_OPERATIONS = new Set(["GetAuth", "RefreshAuth", "CheckPasswordStrength", "CreateUser"]);

export async function performRefresh(): Promise<boolean> {
    const response = await fetch(GRAPHQL_ENDPOINT, {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            query: print(Auth.REFRESH_MUTATION),
            operationName: "RefreshAuth",
        }),
    });

    if (!response.ok) {
        useAuthStore.getState().clearAll();
        return false;
    }

    const { data, errors } = await response.json();
    const payload = data?.refreshAuth?.auth;

    if (errors?.length || !payload?.accessTokenExpiresAt) {
        useAuthStore.getState().clearAll();
        return false;
    }

    useAuthStore.getState().setUserId(payload.user.id);
    useAuthStore.getState().setUserIdentifier(payload.user.email);
    useAuthStore.getState().setAccessTokenExpiresAt(payload.accessTokenExpiresAt);

    return true;
}

export const refreshLink = new SetContextLink(async (prevContext, operation) => {
    if (SKIP_OPERATIONS.has(operation.operationName ?? "")) {
        return prevContext;
    }

    if (isAccessTokenExpiringSoon()) {
        await runExclusiveRefresh(performRefresh);
        // A failed refresh isn't handled here; the request proceeds and the
        // server rejects it, which errorLink then reacts to.
    }

    return prevContext;
});
