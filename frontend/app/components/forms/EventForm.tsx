import * as yup from "yup";
import Input from "~/components/forms/fields/input";
import Button from "~/components/elements/button";
import Alert from "~/components/notifications/alert";
import { useForm, type SubmitHandler } from "react-hook-form";
import { yupResolver } from "@hookform/resolvers/yup";
import { CombinedGraphQLErrors } from "@apollo/client";
import Loader from "../loaders/loader";
import { useState } from "react";
import { apolloClient, Event } from "~/utils";
import moment from "moment-timezone";
import Toggle from "./fields/toggle";

const dateIntervalDefault = {
    years: 0,
    months: 0,
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0,
}

const dateTimeStringToMoment = (dateTime: string, format: string = "YYYY-MM-DD HH:mm") => {
    const date = moment(dateTime, format, true);
    if (!date.isValid) throw new Error("DateTime string could not be parsed to a moment object");
    return date;
};

const createEvent = async (formData: any): Promise<Event> => {
    const { data } = await apolloClient.mutate({
        mutation: Event.CREATE_MUTATION,
        variables: formData,
    });

    return Event.fromDto(data?.createEvent.event);
}

const updateEvent = async (id: string, formData: any): Promise<Event> => {
    const { data } = await apolloClient.mutate({
        mutation: Event.UPDATE_MUTATION,
        variables: {
            id: Event.toIri(id),
            ...formData,
        }
    });

    return Event.fromDto(data?.updateEvent.event);
}

const intervalValueSchema = (max: number) => {
    return yup
        .number()
        .typeError("Must be a number")
        .integer()
        .min(0, "Must be equal or greater than 0")
        .max(max, `Must not be greater than ${max}`)
        .required("This field is required");
};

const schema = yup
    .object({
        name: yup.string().required("Name is required"),
        eventDate: yup.string().required("Date is required"),
        eventTime: yup.string().required("Date is required"),
        repeatInterval: yup
            .object({
                years: intervalValueSchema(99),
                months: intervalValueSchema(11),
                days: intervalValueSchema(31),
                hours: intervalValueSchema(23),
                minutes: intervalValueSchema(59),
                seconds: intervalValueSchema(59),
            }),
        earlyNotification: yup
            .object({
                years: intervalValueSchema(99),
                months: intervalValueSchema(11),
                days: intervalValueSchema(31),
                hours: intervalValueSchema(23),
                minutes: intervalValueSchema(59),
                seconds: intervalValueSchema(59),
            }),
        critical: yup.bool().required(),
    })
    .required();

type EventFormInput = yup.InferType<typeof schema>;

interface EventFormInterface {
    prefill?: Event;
    onSuccess?: (event: Event) => void;
    onFail?: (e?: any) => void;
}

export default function EventForm({ prefill, onSuccess, onFail }: EventFormInterface) {
    const [loading, setLoading] = useState<boolean>(false);
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors },
    } = useForm<EventFormInput>({
        resolver: yupResolver(schema),
        defaultValues: {
            name: prefill?.name ?? undefined,
            eventDate: prefill?.date.format("YYYY-MM-DD") ?? undefined,
            eventTime: prefill?.date.format("HH:mm") ?? undefined,
            repeatInterval: prefill?.getRepeatIntervalAsObject() ?? dateIntervalDefault,
            earlyNotification: prefill?.getEarlyNotificationAsObject() ?? dateIntervalDefault,
            critical: prefill?.critical ?? false,
        },
    });

    const onSubmit: SubmitHandler<EventFormInput> = async (formData) => {
        const repeatInterval = moment.duration(formData.repeatInterval).toISOString();
        const earlyNotification = moment.duration(formData.earlyNotification).toISOString();
        const parsedFormData = {
            name: formData.name,
            date: dateTimeStringToMoment(`${formData.eventDate} ${formData.eventTime}`).format("YYYY-MM-DDTHH:mm:ssZ"),
            repeatInterval: repeatInterval !== "P0D" ? repeatInterval : undefined,
            earlyNotification: earlyNotification !== "P0D" ? earlyNotification : undefined,
            critical: formData.critical,
        };
        setLoading(true);
        try {
            const event = prefill?.id ? await updateEvent(prefill?.id, parsedFormData) : await createEvent(parsedFormData);
            if (onSuccess) onSuccess(event);
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

    if (loading) return <Loader />;

    return (
        <form className="space-y-6" onSubmit={handleSubmit(onSubmit)}>
            <div>
                <Input label="Name" {...register("name")} type="text" error={errors.name} />
            </div>

            <div>
                <label htmlFor="repeatInterval" className="block text-sm/6 font-medium text-gray-900 dark:text-white">
                    Event date/time
                </label>
                <div className="flex flex-row gap-2">
                    <div className="w-3/5 xs:w-auto xs:min-w-50">
                        <Input label="Date" subLabel {...register("eventDate")} type="date" error={errors.eventDate} />
                    </div>
                    <div className="w-2/5 xs:w-auto xs:min-w-30">
                        <Input label="Time" subLabel {...register("eventTime")} type="time" error={errors.eventTime} />
                    </div>
                </div>
            </div>

            <div>
                <label htmlFor="repeatInterval" className="block text-sm/6 font-medium text-gray-900 dark:text-white">
                    Repeat Interval
                </label>
                <div className="grid grid-cols-3 gap-2">
                    <div>
                        <Input label="Year/s" subLabel {...register("repeatInterval.years")} type="number" step={1} error={errors.repeatInterval?.years} />
                    </div>
                    <div>
                        <Input label="Month/s" subLabel {...register("repeatInterval.months")} type="number" step={1} error={errors.repeatInterval?.months} />
                    </div>
                    <div>
                        <Input label="Day/s" subLabel {...register("repeatInterval.days")} type="number" step={1} error={errors.repeatInterval?.days} />
                    </div>
                    <div>
                        <Input label="Hour/s" subLabel {...register("repeatInterval.hours")} type="number" step={1} error={errors.repeatInterval?.hours} />
                    </div>
                    <div>
                        <Input label="Minute/s" subLabel {...register("repeatInterval.minutes")} type="number" step={1} error={errors.repeatInterval?.minutes} />
                    </div>
                    <div>
                        <Input label="Second/s" subLabel {...register("repeatInterval.seconds")} type="number" step={1} error={errors.repeatInterval?.seconds} />
                    </div>
                </div>
            </div>

            <div>
                <label htmlFor="repeatInterval" className="block text-sm/6 font-medium text-gray-900 dark:text-white">
                    Early Notification
                </label>
                <div className="grid grid-cols-3 gap-2">
                    <div>
                        <Input label="Year/s" subLabel {...register("earlyNotification.years")} type="number" step={1} error={errors.earlyNotification?.years} />
                    </div>
                    <div>
                        <Input label="Month/s" subLabel {...register("earlyNotification.months")} type="number" step={1} error={errors.earlyNotification?.months} />
                    </div>
                    <div>
                        <Input label="Day/s" subLabel {...register("earlyNotification.days")} type="number" step={1} error={errors.earlyNotification?.days} />
                    </div>
                    <div>
                        <Input label="Hour/s" subLabel {...register("earlyNotification.hours")} type="number" step={1} error={errors.earlyNotification?.hours} />
                    </div>
                    <div>
                        <Input label="Minute/s" subLabel {...register("earlyNotification.minutes")} type="number" step={1} error={errors.earlyNotification?.minutes} />
                    </div>
                    <div>
                        <Input label="Second/s" subLabel {...register("earlyNotification.seconds")} type="number" step={1} error={errors.earlyNotification?.seconds} />
                    </div>
                </div>
            </div>

            <div>
                <Toggle label="Critical" description="If the event is marked as critical, it will always be displayed on top of the overview page." {...register("critical")} error={errors.critical} />
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
