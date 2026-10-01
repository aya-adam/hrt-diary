import { useAuthStore } from "~/stores/authStore";

let refreshPromise: Promise<boolean> | null = null;

const REFRESH_SKEW_MS = 15_000;

export function isAccessTokenExpiringSoon(): boolean {
    const { accessTokenExpiresAt } = useAuthStore.getState();
    if (accessTokenExpiresAt === null) {
        return true;
    }
    return Date.now() >= accessTokenExpiresAt - REFRESH_SKEW_MS;
}

export function runExclusiveRefresh(refresh: () => Promise<boolean>): Promise<boolean> {
    if (!refreshPromise) {
        refreshPromise = refresh().finally(() => {
            refreshPromise = null;
        });
    }
    return refreshPromise;
}
