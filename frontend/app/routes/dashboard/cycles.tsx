import { constants } from "~/utils";
import type { Route } from "./+types/cycles";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Cycles` }, { name: "description", content: "HRT-Diary Cycles" }];
}

export default function Cycles() {
    return (
        <div>
            <h1>Cycles</h1>
        </div>
    );
}
