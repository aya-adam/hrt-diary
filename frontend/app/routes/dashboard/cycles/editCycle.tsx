import { ChevronLeftIcon } from "@heroicons/react/24/outline";
import Button from "~/components/elements/button";
import { AdministrationCycle, apolloClient, constants } from "~/utils";
import type { Route } from "./+types/editCycle";
import { useNavigate } from "react-router";
import { toast } from "react-toastify";
import AdministrationCycleForm from "~/components/forms/administrationCycleForm";

export function meta({ }: Route.MetaArgs) {
    return [
        { title: `${constants.PAGE_TITLE} | Edit Cycle` },
        { name: "description", content: "Edit a cycle" },
    ];
}

export async function clientLoader({ params }: Route.LoaderArgs) {
    const { data } = await apolloClient.query({
        query: AdministrationCycle.ITEM_QUERY,
        variables: {
            id: AdministrationCycle.toIri(params.cycleUuid),
        }
    });
    const cycle: AdministrationCycle = AdministrationCycle.fromDto(data?.administrationCycle);
    return { cycle };
}

export default function EditCycle({ loaderData }: Route.ComponentProps) {
    const { cycle } = loaderData;
    const navigate = useNavigate();

    const onSuccess = (c: AdministrationCycle) => {
        toast.success("Administration-Cycle successfully updated", {
            toastId: `cycleSuccessfullyUpdatedToast_${c.id}`,
        })
        navigate(`/dashboard/cycles/${c.id}`);
    };

    return (
        <div>
            <div className="pb-10 md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        Edit Administration Cycle
                    </h2>
                </div>
                <div className="mt-4 flex md:mt-0 md:ml-4">
                    <Button label="Cancel" leadingIcon={ChevronLeftIcon} to={`/dashboard/cycles/${cycle.id}`} />
                </div>
            </div>
            <AdministrationCycleForm prefill={cycle} onSuccess={onSuccess} />
        </div>
    );
}