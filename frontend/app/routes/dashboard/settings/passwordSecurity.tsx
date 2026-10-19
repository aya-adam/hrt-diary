import { constants } from "~/utils";
import type { Route } from "./+types/passwordSecurity";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Passwort & Security` }, { name: "description", content: "HRT-Diary Account" }];
}

export default function PasswordSecurity() {
    return (
        <div>
            <h1>Passwort & Security</h1>
        </div>
    );
}
