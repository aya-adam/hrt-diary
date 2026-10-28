import { isRouteErrorResponse, Links, Meta, Outlet, Scripts, ScrollRestoration } from "react-router";

import type { Route } from "./+types/root";
import { ToastContainer } from "react-toastify";
import "./app.css";
import { XMarkIcon } from "@heroicons/react/20/solid";
import { CheckCircleIcon, ExclamationCircleIcon, InformationCircleIcon, XCircleIcon } from "@heroicons/react/24/outline";
import moment from "moment-timezone";

moment.tz.setDefault(moment.tz.guess(true));

if (process.env.NODE_ENV !== "development") {
    console.log = () => { }
    console.error = () => { }
    console.debug = () => { }
}

export const links: Route.LinksFunction = () => [
    { rel: "preconnect", href: "https://fonts.googleapis.com" },
    {
        rel: "preconnect",
        href: "https://fonts.gstatic.com",
        crossOrigin: "anonymous",
    },
    {
        rel: "stylesheet",
        href: "https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap",
    },
];

export function Layout({ children }: { children: React.ReactNode }) {
    return (
        <html lang="en" className="h-full" suppressHydrationWarning>
            <head>
                <meta charSet="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <Meta />
                <Links />
            </head>
            <body className="h-full" suppressHydrationWarning>
                {children}
                <ToastContainer
                    closeButton={({ closeToast }) =>
                        <button
                            type="button"
                            onClick={() => closeToast()}
                            className="absolute top-1.5 right-1.5 inline-flex rounded-md text-gray-400 hover:text-gray-500 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:hover:text-white dark:focus:outline-indigo-500"
                        >
                            <span className="sr-only">Close</span>
                            <XMarkIcon aria-hidden="true" className="size-4" />
                        </button>
                    }
                    icon={({ type, isLoading }) => {
                        if (isLoading) {
                            return (
                                <svg className="size-6 animate-spin fill-transparent" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <circle className="opacity-25 stroke-current stroke-4" cx="12" cy="12" r="10"></circle>
                                    <path className="opacity-75 fill-current" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            );
                        }

                        switch (type) {
                            case "info":
                                return <InformationCircleIcon aria-hidden="true" className="size-6 text-blue-500 dark:text-blue-400" />;
                            case "success":
                                return <CheckCircleIcon aria-hidden="true" className="size-6 text-green-500 dark:text-green-400" />;
                            case "warning":
                                return <ExclamationCircleIcon aria-hidden="true" className="size-6 text-yellow-500 dark:text-yellow-400" />;
                            case "error":
                                return <XCircleIcon aria-hidden="true" className="size-6 text-red-500 dark:text-red-400" />;
                            default:
                                return null;
                        };
                    }}
                />
                <ScrollRestoration />
                <Scripts />
            </body>
        </html>
    );
}

export default function App() {
    return <Outlet />;
}

export function ErrorBoundary({ error }: Route.ErrorBoundaryProps) {
    let message = "Oops!";
    let details = "An unexpected error occurred.";
    let stack: string | undefined;

    if (isRouteErrorResponse(error)) {
        message = error.status === 404 ? "404" : "Error";
        details = error.status === 404 ? "The requested page could not be found." : error.statusText || details;
    } else if (import.meta.env.DEV && error && error instanceof Error) {
        details = error.message;
        stack = error.stack;
    }

    return (
        <main className="pt-16 p-4 container mx-auto">
            <h1>{message}</h1>
            <p>{details}</p>
            {stack && (
                <pre className="w-full p-4 overflow-x-auto">
                    <code>{stack}</code>
                </pre>
            )}
        </main>
    );
}
