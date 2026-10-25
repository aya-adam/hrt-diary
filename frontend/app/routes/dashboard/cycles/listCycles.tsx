import { constants } from "~/utils";
import type { Route } from "./+types/listCycles";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Cycles` }, { name: "description", content: "HRT-Diary Cycles" }];
}

export default function ListCycles() {
    return (
        <div>
            <h1>Cycles</h1>
        </div>
    );
}
