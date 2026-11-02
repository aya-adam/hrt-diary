import { AdministrationCycle, apolloClient, constants, Event } from "~/utils";
import type { Route } from "./+types/overview";
import BasicWrapper from "~/components/layouts/wrappers/basicWrapper";
import Button from "~/components/elements/button";
import { ChevronLeftIcon, ChevronRightIcon, WindowIcon } from "@heroicons/react/24/outline";
import { useEffect, useMemo, useState } from "react";
import moment from "moment-timezone";
import Badge from "~/components/elements/badge";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Overview` }, { name: "description", content: "HRT-Diary overview!" }];
}

export async function clientLoader() {
    const [cycleData, eventData] = await Promise.all([
        apolloClient.query({ query: AdministrationCycle.COLLECTION_QUERY }),
        apolloClient.query({ query: Event.COLLECTION_QUERY }),
    ]);

    const cycles: AdministrationCycle[] = cycleData.data?.administrationCycles ? cycleData.data.administrationCycles.map((c) => AdministrationCycle.fromDto(c)) : [];
    const events: Event[] = eventData.data?.events ? eventData.data.events.map((e) => Event.fromDto(e)) : [];

    return { cycles, events }
}

const getFullDayDifference = (from: moment.Moment, to: moment.Moment): string => {
    const commonTime = { hour: 0, minute: 0, second: 0, millisecond: 0 };
    from.set(commonTime)
    to.set(commonTime)
    console.log("from", from);
    console.log("to", to);
    return to.from(from);
}

const getNextDateFromRepeatInterval = (event: Event, threshold: moment.Moment) => {
    const date: moment.Moment = event.date.clone();
    if (event.repeatInterval) {
        while (date.isBefore(threshold, "day")) {
            date.add(event.repeatInterval);
        }
    }
    return date;
};

const checkEventConditions = (
    event: Event,
    displayDate: moment.Moment,
): { isToday: boolean; displayAdvanced: boolean; isCritical: boolean } => {
    // Get new date respecting intervals
    const date: moment.Moment = getNextDateFromRepeatInterval(event, displayDate);

    // Check if today
    const isToday = date.isSame(displayDate, "day");

    // Check if has advanced notification set AND is critical
    let displayAdvanced = false;
    if (event.earlyNotification) {
        const advancedNotificationDate: moment.Moment = date.clone().subtract(event.earlyNotification);
        displayAdvanced =
            displayDate.isSameOrAfter(advancedNotificationDate, "day") && displayDate.isSameOrBefore(date, "day");
    }

    return { isToday, displayAdvanced, isCritical: event.critical };
};

export default function Overview({ loaderData }: Route.ComponentProps) {
    const { cycles, events } = loaderData;
    const [offset, setOffset] = useState<number>(0);

    useEffect(() => {
        console.log("cycles", cycles);
        console.log("events", events);
    }, []);

    const displayDate: moment.Moment = useMemo(() => {
        return moment().hour(0).minute(0).second(0).millisecond(0).add(offset, "days");
    }, [offset]);

    const todaysEvents: Event[] = useMemo(() => {
        return events.filter((e) => {
            const { isToday } = checkEventConditions(e, displayDate);
            return isToday;
        })
    }, [displayDate, events]);

    const upcomingEvents: Event[] = useMemo(() => {
        return events.filter((e) => {
            const { displayAdvanced, isToday } = checkEventConditions(e, displayDate);
            return displayAdvanced && !isToday;
        })
    }, [displayDate, events]);

    return (
        <BasicWrapper>
            <div className="flex flex-col gap-y-6">
                <div className="pb-10 md:flex md:items-center md:justify-between">
                    <div className="min-w-0 flex-1">
                        <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                            HRT-Diary Overview
                        </h2>
                    </div>
                    <div className="mt-4 flex md:mt-0 md:ml-4">
                        <Button label="Dashboard" leadingIcon={WindowIcon} to="/dashboard" />
                    </div>
                </div>
                <div className="flex flex-col gap-y-3">
                    <div className="flex flex-row justify-between overflow-hidden rounded-md border border-indigo-600 dark:border-indigo-500">
                        <Button btnIcon={ChevronLeftIcon} onClick={() => setOffset((prev) => prev - 1)} className="rounded-none!" />
                        <div className="grow text-center bg-white dark:bg-gray-800/50">
                            {displayDate.format("DD.MM.YYYY")}
                        </div>
                        <Button btnIcon={ChevronRightIcon} onClick={() => setOffset((prev) => prev + 1)} className="rounded-none!" />
                    </div>
                    {offset !== 0 && (
                        <div>
                            <Button label="Reset Date" onClick={() => setOffset(0)} className="w-full" />
                        </div>
                    )}
                </div>
                <div className="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800/50 dark:shadow-none dark:outline dark:-outline-offset-1 dark:outline-white/10">
                    <div className="py-1.5 px-3 text-white bg-indigo-600 dark:bg-indigo-500">
                        Today's Events
                    </div>
                    {todaysEvents.length > 0 ? (
                        <ul
                            role="list"
                            className="divide-y divide-gray-100 bg-white dark:divide-white/5 dark:bg-gray-800/50"
                        >
                            {todaysEvents.map((e, i) => (
                                <li key={e.id} className="inline-flex gap-x-1 py-1.5 px-3 text-sm/6 font-semibold text-gray-900 dark:text-white">{e.critical && <span className="text-red-500 dark:text-red-600 font-bold">Critical:</span>}{e.name}</li>
                            ))}
                        </ul>
                    ) : (
                        <div className="py-1.5 px-3 text-sm/6 italic text-gray-500 dark:text-gray-400">
                            None
                        </div>
                    )}
                    <div className="py-1.5 px-3 text-white bg-indigo-600 dark:bg-indigo-500">
                        Upcoming Events
                    </div>
                    {upcomingEvents.length > 0 ? (
                        <ul
                            role="list"
                            className="divide-y divide-gray-100 bg-white dark:divide-white/5 dark:bg-gray-800/50"
                        >
                            {upcomingEvents.map((e, i) => (
                                <li key={e.id} className="flex gap-x-1 py-1.5 px-3 text-sm/6 font-semibold text-gray-900 dark:text-white">
                                    {e.critical && <span className="text-red-500 dark:text-red-600 font-bold">Critical:</span>}
                                    {e.name}
                                    <span className="text-gray-500 dark:text-gray-400">- {getFullDayDifference(displayDate, getNextDateFromRepeatInterval(e, displayDate))}</span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="py-1.5 px-3 text-sm/6 italic text-gray-500 dark:text-gray-400">
                            None
                        </div>
                    )}
                </div>
                {cycles.length > 0 && (
                    <div className="flex flex-col gap-3">
                        {cycles.map((cycle) => (
                            <div className="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800/50 dark:shadow-none dark:outline dark:-outline-offset-1 dark:outline-white/10">
                                <div className="py-1.5 px-3 text-center">
                                    Cycle: {cycle.name}
                                </div>
                                <div className="flex justify-between divide-x divide-indigo-600 dark:divide-indigo-500">
                                    {cycle.schedule.map((item, index, arr) => (
                                        <div className="w-full grid grid-rows-2">
                                            <div className="py-1.5 px-3 text-center text-white bg-indigo-600 dark:bg-indigo-500">{item}</div>
                                            <div className="py-1.5 px-3 text-center">{cycle.getLocationByDate(displayDate, arr.length, index)}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </BasicWrapper>
    );
}