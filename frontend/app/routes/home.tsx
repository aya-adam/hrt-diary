import { constants } from "~/utils";
import type { Route } from "./+types/home";
import { redirect } from "react-router";

export function meta({}: Route.MetaArgs) {
    return [{ title: constants.PAGE_TITLE }, { name: "description", content: "Welcome to HRT-Diary!" }];
}

export function clientLoader() {
    throw redirect("/signin");
}

export default function Home() {
    return (
        <div>
            <h1>HRT-Diary</h1>
        </div>
    );
}
