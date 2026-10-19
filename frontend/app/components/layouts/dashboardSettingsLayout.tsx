import { Link, Outlet, useLocation } from "react-router";
import DashboardWrapper from "./wrappers/dashboardWrapper";
import { isCurrentPage } from "~/utils";

const accountNavigation = [
    { name: 'Profile', href: '/dashboard/settings/profile' },
    { name: 'Range references', href: '/dashboard/settings/range-references' },
    { name: 'Password & Security', href: '/dashboard/settings/password-and-security' },
    { name: 'Privacy & Data', href: '/dashboard/settings/privacy-and-data' },
]

export default function DashboardSettingsLayout() {
    const locationPathname = useLocation().pathname;

    return (
        <DashboardWrapper>
            <div className="lg:pl-72">
                <main>
                    <h1 className="sr-only">Settings</h1>

                    <header className="border-b border-gray-200 dark:border-white/5">
                        {/* Secondary navigation */}
                        <nav className="flex overflow-x-auto py-4">
                            <ul
                                role="list"
                                className="flex min-w-full flex-none gap-x-6 px-4 text-sm/6 font-semibold text-gray-500 sm:px-6 lg:px-8 dark:text-gray-400"
                            >
                                {accountNavigation.map((item) => (
                                    <li key={item.name}>
                                        <Link to={item.href} className={isCurrentPage(locationPathname, item.href, true) ? 'text-indigo-600 dark:text-indigo-400' : ''}>
                                            {item.name}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    </header>

                    <div className="px-4 py-10 sm:px-6 lg:px-8">
                        <Outlet />
                    </div>

                </main>
            </div>
        </DashboardWrapper>
    );
}
