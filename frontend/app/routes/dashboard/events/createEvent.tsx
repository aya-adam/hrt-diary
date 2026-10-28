import { constants, Event } from "~/utils";
import type { Route } from "./+types/createEvent";
import Button from "~/components/elements/button";
import { ChevronLeftIcon } from "@heroicons/react/24/outline";
import EventForm from "~/components/forms/EventForm";
import { useNavigate } from "react-router";
import { toast } from "react-toastify";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Create Event` }, { name: "description", content: "Create an HRT-Diary Event" }];
}

export default function CreateEvents() {
    const navigate = useNavigate();

    const onSuccess = (event: Event) => {
        toast.success("Event created", {
            toastId: `eventSuccessfullyCreatedToast_${event.id}`,
        })
        navigate(`/dashboard/events/${event.id}`);
    };

    return (
        <div>
            <div className="pb-10 md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        Create Event
                    </h2>
                </div>
                <div className="mt-4 flex md:mt-0 md:ml-4">
                    <Button label="Cancel" leadingIcon={ChevronLeftIcon} to="/dashboard/events" />
                </div>
            </div>
            <EventForm onSuccess={onSuccess} />
        </div>
    );
}
