import { Link, Outlet, useLocation } from "react-router";
import { joinClasses } from "~/utils";

const navigation = [
    { label: "Dashboard", href: "/dashboard" },
];

const handleLogout = () => {
    console.log("logout");
    document.location.href = "/";
}

export default function DashboardLayout() {
    const locationPathname = useLocation().pathname;

    return (
        <div className="mx-auto px-4 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-5xl">
                <div className="my-5">
                    <ul className="list-none flex flex-row flex-nowrap gap-5">
                        {navigation.map((item: any) => (
                            <Link
                                key={item.href}
                                to={item.href}
                                className={joinClasses(
                                    locationPathname === item.href ? "underline" : "",
                                )}
                            >
                                {item.label}
                            </Link>
                        ))}
                        <button className="cursor-pointer" onClick={handleLogout}>Log Out</button>
                    </ul>
                </div>
                <hr className="my-5 text-gray-300 dark:text-gray-800" />
                <div className="my-5">
                    <Outlet />
                </div>
            </div>
        </div>
    );
}