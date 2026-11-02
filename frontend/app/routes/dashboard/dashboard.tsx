import { constants } from "~/utils";
import type { Route } from "./+types/dashboard";
import Button from "~/components/elements/button";
import { WindowIcon } from "@heroicons/react/24/outline";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Dashboard` }, { name: "description", content: "HRT-Diary Dashboard" }];
}

export default function Dashboard() {
    return (
        <div>
            <div className="pb-10 md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        Dashboard
                    </h2>
                </div>
                <div className="mt-4 flex md:mt-0 md:ml-4">
                    <Button label="View Overview Page" leadingIcon={WindowIcon} to="/overview" />
                </div>
            </div>
        </div>
    );
}
