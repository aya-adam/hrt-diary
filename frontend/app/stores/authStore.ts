import { create } from "zustand";
import { persist } from "zustand/middleware";
import { User } from "~/utils";

type AuthStoreState = {
    userId: string | null;
    userIdentifier: string | null;
    accessTokenExpiresAt: number | null;
};

type AuthStoreActions = {
    setUserId: (userId: AuthStoreState["userId"]) => void;
    setUserIdentifier: (userIdentifier: AuthStoreState["userIdentifier"]) => void;
    setAccessTokenExpiresAt: (accessTokenExpiresAt: string | null) => void;
    clearUserId: () => void;
    clearUserIdentifier: () => void;
    clearAccessTokenExpiresAt: () => void;
    clearAll: () => void;
};

type AuthStore = AuthStoreState & AuthStoreActions;

export const useAuthStore = create<AuthStore>()(
    persist(
        (set) => ({
            userId: null,
            userIdentifier: null,
            accessTokenExpiresAt: null,
            setUserId: (userId) => set({ userId: userId ? User.toUuid(userId) : null }),
            setUserIdentifier: (userIdentifier) => set({ userIdentifier }),
            setAccessTokenExpiresAt: (accessTokenExpiresAt) =>
                set({ accessTokenExpiresAt: accessTokenExpiresAt ? new Date(accessTokenExpiresAt).getTime() : null }),
            clearUserId: () => set({ userId: null }),
            clearUserIdentifier: () => set({ userIdentifier: null }),
            clearAccessTokenExpiresAt: () => set({ accessTokenExpiresAt: null }),
            clearAll: () => set({ userId: null, userIdentifier: null, accessTokenExpiresAt: null }),
        }),
        {
            name: "auth-store",
        },
    ),
);
