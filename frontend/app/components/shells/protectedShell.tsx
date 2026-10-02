import { Outlet, redirect, useNavigate, useNavigation } from "react-router";
import { useAuthStore } from "~/stores/authStore";
import { apolloClient, getErrorMessage, joinClasses, REFRESH_MUTATION } from "~/utils";
import type { Route } from "./+types/protectedShell";
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
        clearAll
    } = useAuthStore.getState();

    if (!userId || !userIdentifier || !accessTokenExpiresAt) {
        try {
            const { data } = await apolloClient.mutate({ mutation: REFRESH_MUTATION });
            setUserId(data?.authTokenRefresh.tokenRefresh.userId ?? null);
            setUserIdentifier(data?.authTokenRefresh.tokenRefresh.userIdentifier ?? null);
            setAccessTokenExpiresAt(data?.authTokenRefresh.tokenRefresh.accessTokenExpiresAt ?? null);
        } catch (error) {
            clearAll();
            throw redirect("/signin");
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
            {(messageArr && messageArr.length > 1)
                ? (
                    <Alert list={messageArr} type="error" />
                )
                : (
                    <Alert type="error"><span className="whitespace-pre-wrap">{message}</span></Alert>
                )}
        </BasicWrapper>
    );
}

export default function ProtectedShell() {
    const navigation = useNavigation();
    const isNavigating = navigation.state === "loading";

    return (
        <>
            {isNavigating && <ProgressBar />}
            <div
                className={joinClasses(
                    "h-full",
                    isNavigating ? "opacity-30 pointer-events-none" : "",
                )}
            >
                <Outlet />
            </div>
        </>
    )
}