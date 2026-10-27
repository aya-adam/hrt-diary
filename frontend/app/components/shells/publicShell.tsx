import { Outlet, redirect, useNavigation } from "react-router";
import { useAuthStore } from "~/stores/authStore";
import { apolloClient, Auth, getErrorMessage, joinClasses } from "~/utils";
import type { Route } from "./+types/publicShell";
import Alert from "../notifications/alert";
import Loader from "../loaders/loader";
import ProgressBar from "../loaders/progressBar";
import BasicWrapper from "../layouts/wrappers/basicWrapper";

export async function clientLoader() {
    const {
        userId,
        userIdentifier,
        accessTokenExpiresAt,
        setUserId,
        setUserIdentifier,
        setAccessTokenExpiresAt,
        clearAll,
    } = useAuthStore.getState();

    if (userId || userIdentifier || accessTokenExpiresAt) {
        try {
            const { data } = await apolloClient.mutate({ mutation: Auth.REFRESH_MUTATION });
            setUserId(data?.refreshAuth.auth.user?.id ?? null);
            setUserIdentifier(data?.refreshAuth.auth.user?.email ?? null);
            setAccessTokenExpiresAt(data?.refreshAuth.auth.accessTokenExpiresAt ?? null);
            return redirect("/dashboard");
        } catch (error) {
            clearAll();
        }
    }
}

export function HydrateFallback() {
    return (
        <BasicWrapper>
            <div className="min-h-full flex justify-center items-center">
                <Loader />
            </div>
        </BasicWrapper>
    );
}

export function ErrorBoundary({ error }: Route.ErrorBoundaryProps) {
    const { message, messageArr } = getErrorMessage(error);

    return (
        <BasicWrapper>
            <div className="mx-auto max-w-200">
                {messageArr && messageArr.length > 1 ? (
                    <Alert list={messageArr} type="error" />
                ) : (
                    <Alert type="error">
                        <span className="whitespace-pre-wrap">{message}</span>
                    </Alert>
                )}
            </div>
        </BasicWrapper>
    );
}

export default function PublicShell() {
    const navigation = useNavigation();
    const isNavigating = navigation.state === "loading";

    return (
        <>
            {isNavigating && <ProgressBar />}
            <div className={joinClasses("min-h-full", isNavigating ? "opacity-40 pointer-events-none" : "")}>
                <Outlet />
            </div>
        </>
    );
}
