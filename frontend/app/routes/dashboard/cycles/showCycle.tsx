import { ChevronLeftIcon, PencilIcon } from "@heroicons/react/24/outline";
import Button from "~/components/elements/button";
import { AdministrationCycle, apolloClient, constants, splitButtonToast } from "~/utils";
import type { Route } from "./+types/showCycle";
import { TrashIcon } from "@heroicons/react/20/solid";
import { toast } from "react-toastify";
import { useNavigate } from "react-router";
import Badge from "~/components/elements/badge";
import { useAuthStore } from "~/stores/authStore";

export function meta({ }: Route.MetaArgs) {
    return [
        { title: `${constants.PAGE_TITLE} | Cycle Detail` },
        { name: "description", content: "Cycle detail" },
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

export default function ShowCycle({ loaderData }: Route.ComponentProps) {
    const { cycle } = loaderData;
    const navigate = useNavigate();
    const { userId } = useAuthStore.getState();

    const deleteCycle = async () => {
        const proceed = await splitButtonToast({
            position: "top-center",
            data: {
                title: "Delete cycle",
                text: "You're about to delete this cycle. This action cannot be reversed. Do you want to proceed?",
                confirmBtnText: "Delete",
                rejectBtnText: "Cancel",
            },
        });

        if (proceed) {
            try {
                await toast.promise(apolloClient.mutate({
                    mutation: AdministrationCycle.DELETE_MUTATION,
                    variables: { id: AdministrationCycle.toIri(cycle.id) },
                }), {
                    pending: "Deleting administration cycle...",
                    success: {
                        render() {
                            return "Administration cycle succcessfully deleted";
                        }
                    },
                    error: "An error occured while trying to delete administration cycle",
                })
                navigate("/dashboard/cycles");
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
                        {cycle.name}
                    </h2>
                </div>
                <div className="mt-4 flex gap-3 md:mt-0 md:ml-4">
                    <Button label="All" leadingIcon={ChevronLeftIcon} to="/dashboard/cycles" />
                    <Button label="Edit" leadingIcon={PencilIcon} to={`/dashboard/cycles/edit/${cycle.id}`} />
                    <Button label="Delete" leadingIcon={TrashIcon} style="danger" onClick={() => deleteCycle()} />
                </div>
            </div>
            <div className="flex gap-6">
                <span>Created: {cycle.createdAt.format("DD.MM.YYYY HH:mm")}</span>
                <span>Updated: {cycle.updatedAt.format("DD.MM.YYYY HH:mm")}</span>
            </div>
            <div className="flex flex-col gap-3">
                <div>
                    Start Date: {cycle.startingDate.format("DD.MM.YYYY")}
                </div>
                <div>
                    Locations
                    <ol className="list-decimal list-inside">
                        {cycle.locations.map((item, index) => <li>{item}{cycle.startingIndex === index && <>{" "}<Badge>Start</Badge></>}</li>)}
                    </ol>
                </div>
            </div>
        </div>
    );
}