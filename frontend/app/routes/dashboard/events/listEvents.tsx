import { apolloClient, constants, Event, type EventDto } from "~/utils";
import type { Route } from "./+types/listEvents";
import Button from "~/components/elements/button";
import { PlusIcon } from "@heroicons/react/24/outline";
import Alert from "~/components/notifications/alert";
import { Link } from "react-router";
import Divider from "~/components/elements/divider";
import moment from "moment-timezone";
import Badge from "~/components/elements/badge";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Events` }, { name: "description", content: "HRT-Diary Events" }];
}

export async function clientLoader() {
    const { data } = await apolloClient.query({
        query: Event.COLLECTION_QUERY,
    });
    const events: Event[] = data?.events.map((event: EventDto) => Event.fromDto(event)) ?? [];
    return { events }
}

export default function ListEvents({ loaderData }: Route.ComponentProps) {
    const { events } = loaderData;

    return (
        <div>
            <div className="pb-10 md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        Events
                    </h2>
                </div>
                <div className="mt-4 flex md:mt-0 md:ml-4">
                    <Button label="New" leadingIcon={PlusIcon} to="/dashboard/events/create" />
                </div>
            </div>
            {events.length > 0
                ? (
                    <div className="grid grid-cols-1 gap-4">
                        {events.map((event) => (
                            <Link
                                to={`/dashboard/events/${event.id}`}
                                key={event.id}
                                className="flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-xs focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-indigo-600 hover:border-gray-400 dark:border-white/10 dark:bg-gray-800/50 dark:shadow-none dark:focus-within:outline-indigo-500 dark:hover:border-white/25"
                            >
                                <div className="w-full space-y-3">
                                    <h2 className="font-semibold">{event.name}{event.critical && <span className="ml-2"><Badge>Critical</Badge></span>}</h2>
                                    <div>
                                        Date: {event.date.format("DD.MM.YYYY HH:mm")}
                                    </div>
                                    <div>
                                        Repeat Interval: {event.repeatInterval?.humanize() ?? "-"}
                                    </div>
                                    <div>
                                        Early Notification: {event.earlyNotification?.humanize() ?? "-"}
                                    </div>
                                    <Divider />
                                    <div className="flex justify-between gap-1.5">
                                        <span className="flex-1 text-right text-sm">{moment.duration(event.createdAt.diff(moment())).humanize(true)}</span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                )
                : (
                    <Alert>No events found</Alert>
                )}
        </div>
    );
}
