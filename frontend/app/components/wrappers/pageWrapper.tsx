import type React from "react";
import { useState } from "react";
import { Link, useLocation, useNavigate } from "react-router";
import { useAuthStore } from "~/stores/authStore";
import { apolloClient, joinClasses, LOGOUT_MUTATION } from "~/utils";
import Loader from "../loaders/loader";
import Alert from "../notifications/alert";

interface PageWrapperInterface extends React.PropsWithChildren {
    isNavigating?: boolean;
    withLogoutLink?: boolean;
    navItems?: { label: string, href: string }[];
}

export default function PageWrapper({ isNavigating = false, withLogoutLink = false, navItems = [], children }: PageWrapperInterface) {
    const [loading, setLoading] = useState<boolean>(false);
    const [error, setError] = useState<string | undefined>();
    const navigate = useNavigate();
    const locationPathname = useLocation().pathname;

    const handleLogout = async () => {
        setLoading(true);
        try {
            await apolloClient.mutate({ mutation: LOGOUT_MUTATION });
            useAuthStore.getState().clearAll();
            navigate("/signin");
        } catch (error: any) {
            setError("An unknown error occured. Please try again.");
            console.log("error", error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <Loader />

    return (
        <div className="mx-auto px-4 sm:px-6 lg:px-8">
            <div
                className={joinClasses(
                    "mx-auto max-w-5xl",
                    isNavigating ? "opacity-30 pointer-events-none" : "",
                )}
            >
                {(navItems.length > 0 || withLogoutLink) && (
                    <>
                        <div className="my-5">
                            <ul className="list-none flex flex-row flex-nowrap gap-5">
                                {navItems.map((item: any) => (
                                    <Link
                                        key={item.href}
                                        to={item.href}
                                        className={joinClasses(locationPathname === item.href ? "underline" : "")}
                                    >
                                        {item.label}
                                    </Link>
                                ))}
                                {withLogoutLink && (
                                    <button className="cursor-pointer" onClick={handleLogout}>
                                        Log Out
                                    </button>
                                )}
                            </ul>
                        </div>
                        <hr className="my-5 text-gray-300 dark:text-gray-800" />
                    </>
                )}
                {error
                    ? (
                        <div className="my-5">
                            <Alert type="error">{error}</Alert>
                        </div>
                    )
                    : (
                        <div className="my-5">
                            {children}
                        </div>
                    )}
            </div>
        </div>
    )
}