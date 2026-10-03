import { Outlet } from "react-router";
import DashboardWrapper from "./wrappers/dashboardWrapper";

export default function DashboardLayout() {
    return (
        <DashboardWrapper>
            <main className="py-10 lg:pl-72">
                <div className="px-4 sm:px-6 lg:px-8">
                    <Outlet />
                </div>
            </main>
        </DashboardWrapper>
    );
}
