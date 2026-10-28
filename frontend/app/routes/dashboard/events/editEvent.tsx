import { apolloClient, constants, Event } from "~/utils";
import type { Route } from "./+types/editEvent";
import { useNavigate } from "react-router";
import { toast } from "react-toastify";
import Button from "~/components/elements/button";
import { ChevronLeftIcon } from "@heroicons/react/24/outline";
import EventForm from "~/components/forms/EventForm";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Edit Event` }, { name: "description", content: "Edit an HRT-Diary Event" }];
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

export default function EditEvent({ loaderData }: Route.ComponentProps) {
    const { event } = loaderData;
    const navigate = useNavigate();

    const onSuccess = (event: Event) => {
        toast.success("Event updated", {
            toastId: `eventSuccessfullyUpdatedToast_${event.id}`,
        })
        navigate(`/dashboard/events/${event.id}`);
    };

    return (
        <div>
            <div className="pb-10 md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        Edit Event
                    </h2>
                </div>
                <div className="mt-4 flex md:mt-0 md:ml-4">
                    <Button label="Cancel" leadingIcon={ChevronLeftIcon} to="/dashboard/events" />
                </div>
            </div>
            <EventForm prefill={event} onSuccess={onSuccess} />
        </div>
    );
}
