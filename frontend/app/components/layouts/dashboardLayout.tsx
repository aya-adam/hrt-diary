import { Outlet, redirect, useNavigation } from "react-router";
import { apolloClient, getErrorMessage, REFRESH_MUTATION } from "~/utils";
import Loader from "../loaders/loader";
import Alert from "../notifications/alert";
import { useAuthStore } from "~/stores/authStore";
import type { Route } from "./+types/dashboardLayout";
import PageWrapper from "../wrappers/pageWrapper";

const navItems = [{ label: "Dashboard", href: "/dashboard" }];

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

export function ErrorBoundary({ error }: Route.ErrorBoundaryProps) {
    const { message, messageArr } = getErrorMessage(error);

    return (
        <PageWrapper withLogoutLink>
            {(messageArr && messageArr.length > 1)
            ? (
                <Alert list={messageArr} type="error" />
            )
        : (
            <Alert type="error"><span className="whitespace-pre-wrap">{message}</span></Alert>
        )}
        </PageWrapper>
    );
}

export function HydrateFallback() {
    return <Loader />;
}

export default function DashboardLayout() {
    const navigation = useNavigation();
    const isNavigating = navigation.state === "loading";

    return (
        <PageWrapper navItems={navItems} isNavigating={isNavigating} withLogoutLink>
            <Outlet />
        </PageWrapper>
    );
}
