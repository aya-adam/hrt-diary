import { constants } from "~/utils";
import type { Route } from "./+types/dashboard";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Dashboard` }, { name: "description", content: "HRT-Diary Dashboard" }];
}

export default function Dashboard() {
    return (
        <div>
            <h1>Dashboard</h1>
        </div>
    );
}