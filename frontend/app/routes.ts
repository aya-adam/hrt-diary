import { type RouteConfig, index, layout, prefix, route } from "@react-router/dev/routes";

export default [
    layout("components/layouts/simplePublicLayout.tsx", [
        index("routes/home.tsx"),
        route("signin", "routes/signin.tsx"),
        route("signup", "routes/signup.tsx"),
    ]),
    ...prefix("dashboard", [
        layout("components/layouts/dashboardLayout.tsx", [index("routes/dashboard/dashboard.tsx")]),
    ]),
] satisfies RouteConfig;
