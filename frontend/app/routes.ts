import { type RouteConfig, index, layout, prefix, route } from "@react-router/dev/routes";

export default [
    layout("components/shells/publicShell.tsx", [
        layout("components/layouts/landingPageLayout.tsx", [
            index("routes/home.tsx"),
        ]),
        layout("components/layouts/loginLayout.tsx", [
            route("signin", "routes/signin.tsx"),
            route("signup", "routes/signup.tsx"),
        ]),
    ]),
    layout("components/shells/protectedShell.tsx", [
        route("dashboard", "components/layouts/dashboardLayout.tsx", [
            index("routes/dashboard/dashboard.tsx"),
            route("diary", "routes/dashboard/posts.tsx"),
            route("labs", "routes/dashboard/labs.tsx"),
            route("charts", "routes/dashboard/charts.tsx"),
            route("cycles", "routes/dashboard/cycles.tsx"),
            route("events", "routes/dashboard/events.tsx"),
            ...prefix("account", [
                index("routes/dashboard/account/account.tsx"),
            ]),
        ]),
    ]),
] satisfies RouteConfig;
