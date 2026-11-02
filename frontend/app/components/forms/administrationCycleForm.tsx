import * as yup from "yup";
import Input from "~/components/forms/fields/input";
import Button from "~/components/elements/button";
import Alert from "~/components/notifications/alert";
import { useFieldArray, useForm, type FieldError, type FieldErrorsImpl, type Merge, type SubmitHandler } from "react-hook-form";
import { yupResolver } from "@hookform/resolvers/yup";
import { CombinedGraphQLErrors } from "@apollo/client";
import Loader from "../loaders/loader";
import { useMemo, useState } from "react";
import { AdministrationCycle, apolloClient, joinClasses } from "~/utils";
import { ChevronDownIcon, ChevronUpIcon, TrashIcon } from "@heroicons/react/20/solid";
import moment from "moment-timezone";

const createCycle = async (formData: any): Promise<AdministrationCycle> => {
    formData.schedule = formData.schedule.map((item: any) => item.value);
    formData.locations = formData.locations.map((item: any) => item.value);
    const { data } = await apolloClient.mutate({
        mutation: AdministrationCycle.CREATE_MUTATION,
        variables: formData,
    });

    return AdministrationCycle.fromDto(data?.createAdministrationCycle.administrationCycle);
}

const updateCycle = async (id: string, formData: any): Promise<AdministrationCycle> => {
    formData.schedule = formData.schedule.map((item: any) => item.value);
    formData.locations = formData.locations.map((item: any) => item.value);
    const { data } = await apolloClient.mutate({
        mutation: AdministrationCycle.UPDATE_MUTATION,
        variables: {
            id: AdministrationCycle.toIri(id),
            ...formData,
        }
    });

    return AdministrationCycle.fromDto(data?.updateAdministrationCycle.administrationCycle);
}

const schema = yup
    .object({
        name: yup.string().required("Name is required"),
        startingDate: yup.string().required("Starting date is required"),
        schedule: yup
            .array()
            .of(
                yup.object({
                    value: yup.string().required("Schedule is required"),
                }),
            )
            .min(1, "Add at least one schedule entry")
            .required(),
        locations: yup
            .array()
            .of(
                yup.object({
                    value: yup.string().required("Location is required"),
                }),
            )
            .min(1, "Add at least one location")
            .required(),
        startingIndex: yup
            .number()
            .integer("Starting index must be a whole number (e.g. 0, 1, 2, 3, 4, ...)")
            .min(0, "Starting index must be equal or greater than 0")
            .lessThan(yup.ref("locations.length"), "Starting index must be within the locations list")
            .required("Starting index is required"),
    })
    .required();

type AdministrationCycleFormInput = yup.InferType<typeof schema>;

interface AdministrationCycleFormInterface {
    prefill?: AdministrationCycle;
    onSuccess?: (administrationCycle: AdministrationCycle) => void;
    onFail?: (e?: any) => void;
}

export default function AdministrationCycleForm({ prefill, onSuccess, onFail }: AdministrationCycleFormInterface) {
    const [loading, setLoading] = useState<boolean>(false);
    const {
        register,
        control,
        handleSubmit,
        setError,
        formState: { errors },
    } = useForm<AdministrationCycleFormInput>({
        resolver: yupResolver(schema),
        defaultValues: {
            name: prefill?.name ?? undefined,
            startingDate: prefill?.startingDate.format("YYYY-MM-DD") ?? moment().format("YYYY-MM-DD"),
            schedule: prefill?.schedule.map((value) => ({ value })) ?? [],
            locations: prefill?.locations.map((value) => ({ value })) ?? [],
            startingIndex: prefill?.startingIndex ?? 0,
        },
    });
    const scheduleArr = useFieldArray({ control, name: "schedule" });
    const locationsArr = useFieldArray({ control, name: "locations" });

    const onSubmit: SubmitHandler<AdministrationCycleFormInput> = async (formData) => {
        setLoading(true);
        try {
            const cycle = prefill?.id ? await updateCycle(prefill?.id, formData) : await createCycle(formData);
            if (onSuccess) onSuccess(cycle);
        } catch (error: any) {
            if (CombinedGraphQLErrors.is(error)) {
                error.errors.forEach((graphQlError) => {
                    const violations: any = graphQlError?.extensions?.violations;
                    if (violations) {
                        violations.forEach((violation: any) => {
                            const regex = /[\[\]]/g;
                            const path = typeof violation.path === "string" && null !== violation.path.match(regex) ? `${violation.path.replace(regex, ".")}value` : violation.path;
                            setError(path, {
                                type: "custom",
                                message: violation.message,
                            });
                        });
                    } else {
                        setError("root", {
                            type: "custom",
                            message: graphQlError.message,
                        });
                    }
                });
            } else {
                setError("root", {
                    type: "custom",
                    message: "An unknown error occured. Please try again.",
                });
            }
            setLoading(false);
            if (onFail) onFail(error);
        }
    };

    const scheduleSingleFieldErrors = useMemo((): Merge<FieldError, (Merge<FieldError, FieldErrorsImpl<{ value: string; }>> | undefined)[]> | undefined => {
        return (errors.schedule && typeof errors.schedule === "object" && Array.isArray(errors.schedule))
            ? errors.schedule
            : undefined;
    }, [errors.schedule]);

    const scheduleFieldErrors = useMemo((): FieldError | undefined => {
        return (errors.schedule && typeof errors.schedule === "object" && !Array.isArray(errors.schedule))
            ? errors.schedule as FieldError
            : undefined;
    }, [errors.schedule]);
    
    const locationSingleFieldErrors = useMemo((): Merge<FieldError, (Merge<FieldError, FieldErrorsImpl<{ value: string; }>> | undefined)[]> | undefined => {
        return (errors.locations && typeof errors.locations === "object" && Array.isArray(errors.locations))
            ? errors.locations
            : undefined;
    }, [errors.locations]);

    const locationFieldErrors = useMemo((): FieldError | undefined => {
        return (errors.locations && typeof errors.locations === "object" && !Array.isArray(errors.locations))
            ? errors.locations as FieldError
            : undefined;
    }, [errors.locations]);

    if (loading) return <Loader />;

    return (
        <form className="space-y-6" onSubmit={handleSubmit(onSubmit)}>
            <div>
                <Input label="Name" {...register("name")} type="text" error={errors.name} />
            </div>

            <div>
                <Input label="Starting Date" {...register("startingDate")} type="date" error={errors.startingDate} />
            </div>

            <div>
                <label className="block text-sm/6 font-medium text-gray-900 dark:text-white">Schedule</label>
                {scheduleArr.fields.map((field, index) => (
                    <div key={field.id} className="flex items-center gap-2">
                        <div className="flex-1">
                            <Input
                                {...register(`schedule.${index}.value` as const)}
                                type="text"
                                error={scheduleSingleFieldErrors?.[index]?.value}
                            />
                        </div>
                        <div className="flex flex-col mt-2 gap-1">
                            <button
                                type="button"
                                onClick={() => scheduleArr.move(index, index - 1)}
                                disabled={scheduleArr.fields.length === 1 || index === 0}
                                className={joinClasses(
                                    "flex justify-center rounded-md bg-indigo-500 px-3 py-0.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500",
                                    scheduleArr.fields.length > 1 && index > 0
                                        ? "opacity-100 cursor-pointer"
                                        : "opacity-30 cursor-not-allowed",
                                )}
                            >
                                <ChevronUpIcon aria-hidden="true" className="size-3" />
                            </button>
                            <button
                                type="button"
                                onClick={() => scheduleArr.move(index, index + 1)}
                                disabled={scheduleArr.fields.length === 1 || index === scheduleArr.fields.length - 1}
                                className={joinClasses(
                                    "flex justify-center rounded-md bg-indigo-500 px-3 py-0.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500",
                                    scheduleArr.fields.length > 1 && index < scheduleArr.fields.length - 1
                                        ? "opacity-100 cursor-pointer"
                                        : "opacity-30 cursor-not-allowed",
                                )}
                            >
                                <ChevronDownIcon aria-hidden="true" className="size-3" />
                            </button>
                        </div>
                        <button
                            type="button"
                            onClick={() => scheduleArr.remove(index)}
                            disabled={scheduleArr.fields.length === 1}
                            className={joinClasses(
                                "flex justify-center rounded-md bg-indigo-500 px-3 py-2.5 mt-2 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500",
                                scheduleArr.fields.length > 1
                                    ? "opacity-100 cursor-pointer"
                                    : "opacity-30 cursor-not-allowed",
                            )}
                        >
                            <TrashIcon aria-hidden="true" className="size-4" />
                        </button>
                    </div>
                ))}
                <button
                    type="button"
                    onClick={() => scheduleArr.append({ value: "" })}
                    className={joinClasses(
                        "block w-full rounded-lg border-2 border-dashed p-1.5 mt-2 text-center focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:focus:outline-indigo-500 cursor-pointer",
                        scheduleFieldErrors ? "border-red-300 dark:border-red-500/25 hover:border-red-400 hover:dark:border-red-500/35" : "border-gray-300 dark:border-white/15 hover:border-gray-400 dark:hover:border-white/25",
                    )}
                >
                    <span className="block text-sm font-semibold text-gray-900 dark:text-white">
                        Add schedule entry
                    </span>
                </button>
                {scheduleFieldErrors && (
                    <p className="mt-2 text-sm text-red-600">{scheduleFieldErrors.message}</p>
                )}
            </div>
            
            <div>
                <label className="block text-sm/6 font-medium text-gray-900 dark:text-white">Locations</label>
                {locationsArr.fields.map((field, index) => (
                    <div key={field.id} className="flex items-center gap-2">
                        <div className="flex-1">
                            <Input
                                {...register(`locations.${index}.value` as const)}
                                type="text"
                                error={locationSingleFieldErrors?.[index]?.value}
                            />
                        </div>
                        <div className="flex flex-col mt-2 gap-1">
                            <button
                                type="button"
                                onClick={() => locationsArr.move(index, index - 1)}
                                disabled={locationsArr.fields.length === 1 || index === 0}
                                className={joinClasses(
                                    "flex justify-center rounded-md bg-indigo-500 px-3 py-0.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500",
                                    locationsArr.fields.length > 1 && index > 0
                                        ? "opacity-100 cursor-pointer"
                                        : "opacity-30 cursor-not-allowed",
                                )}
                            >
                                <ChevronUpIcon aria-hidden="true" className="size-3" />
                            </button>
                            <button
                                type="button"
                                onClick={() => locationsArr.move(index, index + 1)}
                                disabled={locationsArr.fields.length === 1 || index === locationsArr.fields.length - 1}
                                className={joinClasses(
                                    "flex justify-center rounded-md bg-indigo-500 px-3 py-0.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500",
                                    locationsArr.fields.length > 1 && index < locationsArr.fields.length - 1
                                        ? "opacity-100 cursor-pointer"
                                        : "opacity-30 cursor-not-allowed",
                                )}
                            >
                                <ChevronDownIcon aria-hidden="true" className="size-3" />
                            </button>
                        </div>
                        <button
                            type="button"
                            onClick={() => locationsArr.remove(index)}
                            disabled={locationsArr.fields.length === 1}
                            className={joinClasses(
                                "flex justify-center rounded-md bg-indigo-500 px-3 py-2.5 mt-2 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500",
                                locationsArr.fields.length > 1
                                    ? "opacity-100 cursor-pointer"
                                    : "opacity-30 cursor-not-allowed",
                            )}
                        >
                            <TrashIcon aria-hidden="true" className="size-4" />
                        </button>
                    </div>
                ))}
                <button
                    type="button"
                    onClick={() => locationsArr.append({ value: "" })}
                    className={joinClasses(
                        "block w-full rounded-lg border-2 border-dashed p-1.5 mt-2 text-center focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:focus:outline-indigo-500 cursor-pointer",
                        locationFieldErrors ? "border-red-300 dark:border-red-500/25 hover:border-red-400 hover:dark:border-red-500/35" : "border-gray-300 dark:border-white/15 hover:border-gray-400 dark:hover:border-white/25",
                    )}
                >
                    <span className="block text-sm font-semibold text-gray-900 dark:text-white">
                        Add location
                    </span>
                </button>
                {locationFieldErrors && (
                    <p className="mt-2 text-sm text-red-600">{locationFieldErrors.message}</p>
                )}
            </div>

            <div>
                <Input label="Starting Index" {...register("startingIndex")} type="number" error={errors.startingIndex} />
            </div>

            {errors.root && (
                <div>
                    <Alert title={errors.root.message} type="error" />
                </div>
            )}

            <div className="flex gap-5">
                <Button type="submit" label={prefill ? "Update" : "Create"} size="lg" className="w-full" />
            </div>
        </form>
    );
}
