import { ChevronLeftIcon } from "@heroicons/react/24/outline";
import Button from "~/components/elements/button";
import { AdministrationCycle, constants } from "~/utils";
import type { Route } from "./+types/createCycle";
import { useNavigate } from "react-router";
import { toast } from "react-toastify";
import AdministrationCycleForm from "~/components/forms/administrationCycleForm";

export function meta({ }: Route.MetaArgs) {
    return [
        { title: `${constants.PAGE_TITLE} | Create Diary Entry` },
        { name: "description", content: "Create a new diary entry" },
    ];
}

export default function CreateCycle() {
    const navigate = useNavigate();

    const onSuccess = (cycle: AdministrationCycle) => {
        toast.success("Cycle successfully created", {
            toastId: "cycleSuccessfullyCreatedToast_1",
        })
        navigate(`/dashboard/cycles/${cycle.id}`);
    };

    return (
        <div>
            <div className="pb-10 md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        Create Administration Cycle
                    </h2>
                </div>
                <div className="mt-4 flex md:mt-0 md:ml-4">
                    <Button label="Cancel" leadingIcon={ChevronLeftIcon} to="/dashboard/cycles" />
                </div>
            </div>
            <AdministrationCycleForm onSuccess={onSuccess} />
        </div>
    );
}