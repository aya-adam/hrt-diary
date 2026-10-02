import { constants } from "~/utils";
import type { Route } from "./+types/charts";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Charts` }, { name: "description", content: "HRT-Diary Charts" }];
}

export default function Charts() {
    return (
        <div>
            <h1>Charts</h1>
        </div>
    );
}
