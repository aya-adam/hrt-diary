import { constants } from "~/utils";
import type { Route } from "./+types/posts";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Diary Entries` }, { name: "description", content: "HRT-Diary Entries" }];
}

export default function Posts() {
    return (
        <div>
            <h1>Diary Entries</h1>
        </div>
    );
}
