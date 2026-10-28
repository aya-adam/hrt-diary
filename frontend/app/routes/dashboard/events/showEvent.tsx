import { ChevronLeftIcon, PencilIcon } from "@heroicons/react/24/outline";
import Button from "~/components/elements/button";
import { apolloClient, constants, Event, splitButtonToast } from "~/utils";
import type { Route } from "./+types/showEvent";
import { TrashIcon } from "@heroicons/react/20/solid";
import { toast } from "react-toastify";
import { useNavigate } from "react-router";

export function meta({ }: Route.MetaArgs) {
    return [
        { title: `${constants.PAGE_TITLE} | Event` },
        { name: "description", content: "Display HRT-Diary Event" },
    ];
}

export async function clientLoader({ params }: Route.LoaderArgs) {
    const { data } = await apolloClient.query({
        query: Event.ITEM_QUERY,
        variables: {
            id: Event.toIri(params.eventUuid),
        }
    });
    const event: Event = Event.fromDto(data?.event);
    return { event };
}

export default function ShowEvent({ loaderData }: Route.ComponentProps) {
    const { event } = loaderData;
    const navigate = useNavigate();

    const deleteEvent = async () => {
        const proceed = await splitButtonToast({
            position: "top-center",
            data: {
                title: "Delete event",
                text: "You're about to delete this event. This action cannot be reversed. Do you want to proceed?",
                confirmBtnText: "Delete",
                rejectBtnText: "Cancel",
            },
        });

        if (proceed) {
            try {
                await toast.promise(apolloClient.mutate({
                    mutation: Event.DELETE_MUTATION,
                    variables: { id: Event.toIri(event.id) },
                }), {
                    pending: "Deleting event...",
                    success: {
                        render() {
                            return "Event succcessfully deleted";
                        }
                    },
                    error: "An error occured while trying to delete event",
                })
                navigate("/dashboard/events");
            } catch (error) {
                // Do nothing
            }
        }
    };

    return (
        <div className="flex flex-col gap-10">
            <div className="md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        {event.name}
                    </h2>
                </div>
                <div className="mt-4 flex gap-3 md:mt-0 md:ml-4">
                    <Button label="All" leadingIcon={ChevronLeftIcon} to="/dashboard/events" />
                    <Button label="Edit" leadingIcon={PencilIcon} to={`/dashboard/events/edit/${event.id}`} />
                    <Button label="Delete" leadingIcon={TrashIcon} style="danger" onClick={() => deleteEvent()} />
                </div>
            </div>
            <div className="flex gap-6">
                <span>Created: {event.createdAt.format("DD.MM.YYYY HH:mm")}</span>
                <span>Updated: {event.updatedAt.format("DD.MM.YYYY HH:mm")}</span>
            </div>
            <div className="flex flex-col gap-3">
                <div>
                    Date: {event.date.format("DD.MM.YYYY HH:mm")}
                </div>
                <div>
                    Repeat Interval: {event.repeatInterval?.humanize() ?? "-"}
                </div>
                <div>
                    Early Notification: {event.earlyNotification?.humanize() ?? "-"}
                </div>
            </div>
        </div>
    );
}