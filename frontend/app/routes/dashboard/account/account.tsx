import { constants } from "~/utils";
import type { Route } from "./+types/account";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Account` }, { name: "description", content: "HRT-Diary Account" }];
}

export default function Account() {
    return (
        <div>
            <h1>Account</h1>
        </div>
    );
}
