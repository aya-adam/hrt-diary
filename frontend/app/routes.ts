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
                route("labs", "routes/dashboard/labs.tsx"),
                route("charts", "routes/dashboard/charts.tsx"),
                route("cycles", "routes/dashboard/cycles.tsx"),
                route("events", "routes/dashboard/events.tsx"),
                ...prefix("diary", [
                    index("routes/dashboard/posts/listPosts.tsx"),
                    route("create", "routes/dashboard/posts/createPost.tsx"),
                    route(":postUuid", "routes/dashboard/posts/showPost.tsx"),
                    route("edit/:postUuid", "routes/dashboard/posts/editPost.tsx"),
                ]),
            ]),
            route("settings", "components/layouts/dashboardSettingsLayout.tsx", [
                route("profile", "routes/dashboard/settings/profile.tsx"),
                route("range-references", "routes/dashboard/settings/rangeReferences.tsx"),
                route("password-and-security", "routes/dashboard/settings/passwordSecurity.tsx"),
                route("privacy-and-data", "routes/dashboard/settings/privacyData.tsx"),
            ]),
        ]),
    ]),
] satisfies RouteConfig;
