import { constants } from "~/utils";
import type { Route } from "./+types/privacyData";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Privacy & Data` }, { name: "description", content: "HRT-Diary Account" }];
}

export default function PrivacyData() {
    return (
        <div>
            <h1>Privacy & Data</h1>
        </div>
    );
}
