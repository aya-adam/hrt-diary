import { AdministrationCycle, apolloClient, constants, type AdministrationCycleDto } from "~/utils";
import type { Route } from "./+types/listCycles";
import Button from "~/components/elements/button";
import { PlusIcon } from "@heroicons/react/24/outline";
import Alert from "~/components/notifications/alert";
import { Link } from "react-router";
import moment from "moment-timezone";
import Divider from "~/components/elements/divider";
import Badge from "~/components/elements/badge";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Cycles` }, { name: "description", content: "HRT-Diary Cycles" }];
}

export async function clientLoader() {
    const { data } = await apolloClient.query({ query: AdministrationCycle.COLLECTION_QUERY });
    const administrationCycles: AdministrationCycle[] = data?.administrationCycles ? data?.administrationCycles.map((ac: AdministrationCycleDto) => AdministrationCycle.fromDto(ac)) : [];
    return { administrationCycles };
}

export default function ListCycles({ loaderData }: Route.ComponentProps) {
    const { administrationCycles } = loaderData;

    return (
        <div>
            <div className="pb-10 md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        Administration Cycles
                    </h2>
                </div>
                <div className="mt-4 flex md:mt-0 md:ml-4">
                    <Button label="New" leadingIcon={PlusIcon} to="/dashboard/cycles/create" />
                </div>
            </div>
            {administrationCycles.length > 0
                ? (
                    <div className="grid grid-cols-1 gap-4">
                        {administrationCycles.map((administrationCycle) => (
                            <Link
                                to={`/dashboard/cycles/${administrationCycle.id}`}
                                key={administrationCycle.id}
                                className="flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-xs focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-indigo-600 hover:border-gray-400 dark:border-white/10 dark:bg-gray-800/50 dark:shadow-none dark:focus-within:outline-indigo-500 dark:hover:border-white/25"
                            >
                                <div className="w-full space-y-3">
                                    <h2 className="font-semibold">{administrationCycle.name}</h2>
                                    <div>
                                        Start Date: {administrationCycle.startingDate.format("DD.MM.YYYY")}
                                    </div>
                                    <div>
                                        Locations:
                                        <div className="ml-3 inline-flex gap-3">
                                            {administrationCycle.locations.map((item, index) => <Badge>{item}{administrationCycle.startingIndex === index && <>{" (Start)"}</>}</Badge>)}
                                        </div>
                                    </div>
                                    <Divider />
                                    <div className="flex justify-between gap-1.5">
                                        <span className="flex-1 text-right text-sm">{moment.duration(administrationCycle.createdAt.diff(moment())).humanize(true)}</span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                )
                : (
                    <Alert>No administration cycles found</Alert>
                )}
        </div>
    );
}
