import { Link, useLocation, useNavigate } from "react-router";
import React, { useState } from "react";
import { useAuthStore } from "~/stores/authStore";
import { Dialog, DialogBackdrop, DialogPanel, TransitionChild } from "@headlessui/react";
import {
    ArrowPathIcon,
    Bars3Icon,
    BeakerIcon,
    BookOpenIcon,
    CalendarDaysIcon,
    ChartBarIcon,
    Cog8ToothIcon,
    HomeIcon,
    PowerIcon,
    UserCircleIcon,
    XMarkIcon,
} from "@heroicons/react/24/outline";
import { apolloClient, Auth, isCurrentPage, joinClasses } from "~/utils";
import Loader from "~/components/loaders/loader";
import BasicWrapper from "./basicWrapper";

const mainNavigation = [
    { name: "Dashboard", href: "/dashboard", icon: HomeIcon, pathMatchExact: true },
    { name: "Diary Entries", href: "/dashboard/diary", icon: BookOpenIcon, pathMatchExact: false },
    { name: "Labs", href: "/dashboard/labs", icon: BeakerIcon, pathMatchExact: false },
    { name: "Charts", href: "/dashboard/charts", icon: ChartBarIcon, pathMatchExact: false },
    { name: "Cycles", href: "/dashboard/cycles", icon: ArrowPathIcon, pathMatchExact: false },
    { name: "Events", href: "/dashboard/events", icon: CalendarDaysIcon, pathMatchExact: false },
    { name: "Settings", href: "/dashboard/settings/profile", icon: Cog8ToothIcon, pathMatchExact: false },
];

export default function DashboardWrapper({ children }: React.PropsWithChildren) {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [loading, setLoading] = useState<boolean>(false);
    const { userIdentifier } = useAuthStore()
    const navigate = useNavigate();
    const locationPathname = useLocation().pathname;

    const handleLogout = async () => {
        setLoading(true);
        try {
            await apolloClient.mutate({ mutation: Auth.LOGOUT_MUTATION });
            useAuthStore.getState().clearAll();
            navigate("/signin");
        } catch (error: any) {
            throw new Error("An unknown error occured. Please try again.");
        }
    };

    if (loading)
        return (
            <BasicWrapper>
                <div className="min-h-full flex justify-center items-center">
                    <Loader />
                </div>
            </BasicWrapper>
        );

    return (
        <div>
            <Dialog open={sidebarOpen} onClose={setSidebarOpen} className="relative z-50 lg:hidden">
                <DialogBackdrop
                    transition
                    className="fixed inset-0 bg-gray-900/80 transition-opacity duration-300 ease-linear data-closed:opacity-0"
                />

                <div className="fixed inset-0 flex">
                    <DialogPanel
                        transition
                        className="relative mr-16 flex w-full max-w-xs flex-1 transform transition duration-300 ease-in-out data-closed:-translate-x-full"
                    >
                        <TransitionChild>
                            <div className="absolute top-0 left-full flex w-16 justify-center pt-5 duration-300 ease-in-out data-closed:opacity-0">
                                <button type="button" onClick={() => setSidebarOpen(false)} className="-m-2.5 p-2.5">
                                    <span className="sr-only">Close sidebar</span>
                                    <XMarkIcon aria-hidden="true" className="size-6 text-white" />
                                </button>
                            </div>
                        </TransitionChild>

                        {/* Sidebar component */}
                        <div className="relative flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-2 ring-1 ring-white/10 dark:before:pointer-events-none dark:before:absolute dark:before:inset-0 dark:before:bg-black/10">
                            <div className="relative flex h-16 shrink-0 items-center">
                                <img
                                    alt="Your Company"
                                    src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600"
                                    className="h-8 w-auto dark:hidden"
                                />
                                <img
                                    alt="Your Company"
                                    src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500"
                                    className="h-8 w-auto not-dark:hidden"
                                />
                            </div>
                            <nav className="flex flex-1 flex-col">
                                <ul role="list" className="flex flex-1 flex-col gap-y-7">
                                    <li>
                                        <ul role="list" className="-mx-2 space-y-1">
                                            {mainNavigation.map((item) => (
                                                <li key={item.name}>
                                                    <Link
                                                        to={item.href}
                                                        className={joinClasses(
                                                            isCurrentPage(locationPathname, item.href, item.pathMatchExact)
                                                                ? "bg-white/5 text-white"
                                                                : "text-gray-400 hover:bg-white/5 hover:text-white",
                                                            "group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold",
                                                        )}
                                                    >
                                                        <item.icon aria-hidden="true" className="size-6 shrink-0" />
                                                        {item.name}
                                                    </Link>
                                                </li>
                                            ))}
                                            <li>
                                                <button
                                                    className="w-full text-gray-400 hover:bg-white/5 hover:text-white group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold cursor-pointer"
                                                    onClick={handleLogout}
                                                >
                                                    <PowerIcon aria-hidden="true" className="size-6 shrink-0" />
                                                    Sign Out
                                                </button>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </DialogPanel>
                </div>
            </Dialog>

            {/* Static sidebar for desktop */}
            <div className="hidden bg-gray-900 lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
                {/* Sidebar component */}
                <div className="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 dark:border-white/10 dark:bg-black/10 px-6">
                    <div className="flex h-16 shrink-0 items-center">
                        <img
                            alt="Your Company"
                            src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600"
                            className="h-8 w-auto dark:hidden"
                        />
                        <img
                            alt="Your Company"
                            src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500"
                            className="h-8 w-auto not-dark:hidden"
                        />
                    </div>
                    <nav className="flex flex-1 flex-col">
                        <ul role="list" className="flex flex-1 flex-col gap-y-7">
                            <li>
                                <ul role="list" className="-mx-2 space-y-1">
                                    {mainNavigation.map((item) => (
                                        <li key={item.name}>
                                            <Link
                                                to={item.href}
                                                className={joinClasses(
                                                    isCurrentPage(locationPathname, item.href, item.pathMatchExact)
                                                        ? "bg-white/5 text-white"
                                                        : "text-gray-400 hover:bg-white/5 hover:text-white",
                                                    "group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold",
                                                )}
                                            >
                                                <item.icon aria-hidden="true" className="size-6 shrink-0" />
                                                {item.name}
                                            </Link>
                                        </li>
                                    ))}
                                    <li>
                                        <button
                                            className="w-full text-gray-400 hover:bg-white/5 hover:text-white group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold cursor-pointer"
                                            onClick={handleLogout}
                                        >
                                            <PowerIcon aria-hidden="true" className="size-6 shrink-0" />
                                            Sign Out
                                        </button>
                                    </li>
                                </ul>
                            </li>
                            <li className="-mx-6 mt-auto">
                                <Link
                                    to="/dashboard/account"
                                    className="flex items-center gap-x-4 px-6 py-3 text-sm/6 font-semibold text-white hover:bg-white/5"
                                >
                                    <UserCircleIcon
                                        aria-hidden="true"
                                        className="size-8 rounded-full bg-gray-800 outline -outline-offset-1 outline-white/10"
                                    />
                                    <span className="sr-only">Your profile</span>
                                    <span aria-hidden="true">{userIdentifier}</span>
                                </Link>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <div className="sticky top-0 z-40 flex items-center gap-x-6 bg-gray-900 px-4 py-4 shadow-sm dark:after:pointer-events-none dark:after:absolute dark:after:inset-0 dark:after:border-b dark:after:border-white/10 dark:after:bg-black/10 dark:shadow-none sm:px-6 lg:hidden">
                <button
                    type="button"
                    onClick={() => setSidebarOpen(true)}
                    className="-m-2.5 p-2.5 text-gray-400 hover:text-white lg:hidden"
                >
                    <span className="sr-only">Open sidebar</span>
                    <Bars3Icon aria-hidden="true" className="size-6" />
                </button>
                <div className="flex-1 text-sm/6 font-semibold text-white">Dashboard</div>
                <a href="#">
                    <span className="sr-only">Your profile</span>
                    <UserCircleIcon
                        aria-hidden="true"
                        className="size-8 rounded-full text-white bg-gray-800 outline -outline-offset-1 outline-white/10"
                    />
                </a>
            </div>

            {children}
        </div>
    );
}
