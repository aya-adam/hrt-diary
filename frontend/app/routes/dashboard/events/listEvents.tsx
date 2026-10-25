import { constants } from "~/utils";
import type { Route } from "./+types/listEvents";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Events` }, { name: "description", content: "HRT-Diary Events" }];
}

export default function ListEvents() {
    return (
        <div>
            <h1>Events</h1>
        </div>
    );
}
