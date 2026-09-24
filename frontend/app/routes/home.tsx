import type { Route } from "./+types/home";

export function meta({ }: Route.MetaArgs) {
    return [{ title: "HRT-Diary" }, { name: "description", content: "Welcome to HRT-Diary!" }];
}

export default function Home() {
    return (
        <div>Index view</div>
    );
}
