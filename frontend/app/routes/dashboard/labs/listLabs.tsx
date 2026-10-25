import { constants } from "~/utils";
import type { Route } from "./+types/listLabs";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Labs` }, { name: "description", content: "HRT-Diary Labs" }];
}

export default function ListLabs() {
    return (
        <div>
            <h1>Labs</h1>
        </div>
    );
}
