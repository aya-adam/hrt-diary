import { type RouteConfig, index, layout, prefix, route } from "@react-router/dev/routes";

export default [
    layout("components/shells/publicShell.tsx", [
        layout("components/layouts/landingPageLayout.tsx", [index("routes/home.tsx")]),
        layout("components/layouts/loginLayout.tsx", [
            route("signin", "routes/signin.tsx"),
            route("signup", "routes/signup.tsx"),
        ]),
    ]),
    layout("components/shells/protectedShell.tsx", [
        ...prefix("dashboard", [
            layout("components/layouts/dashboardLayout.tsx", [
                index("routes/dashboard/dashboard.tsx"),
                route("diary", "routes/dashboard/posts.tsx"),
                route("labs", "routes/dashboard/labs.tsx"),
                route("charts", "routes/dashboard/charts.tsx"),
                route("cycles", "routes/dashboard/cycles.tsx"),
                route("events", "routes/dashboard/events.tsx"),
            ]),
            route("account", "components/layouts/dashboardAccountLayout.tsx", [
                index("routes/dashboard/account/account.tsx"),
                route("passwort-and-security", "routes/dashboard/account/passwortSecurity.tsx"),
                route("privacy-and-data", "routes/dashboard/account/privacyData.tsx"),
            ]),
        ]),
    ]),
] satisfies RouteConfig;
