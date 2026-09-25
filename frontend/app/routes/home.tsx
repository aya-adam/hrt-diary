import { constants } from "~/utils";
import type { Route } from "./+types/home";

export function meta({}: Route.MetaArgs) {
    return [{ title: constants.PAGE_TITLE }, { name: "description", content: "Welcome to HRT-Diary!" }];
}

export default function Home() {
    return (
        <div>
            <h1>HRT-Diary</h1>
        </div>
    );
}
