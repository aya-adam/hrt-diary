import * as yup from "yup";
import Input from "~/components/forms/fields/input";
import Button from "~/components/elements/button";
import Alert from "~/components/notifications/alert";
import { useForm, type SubmitHandler } from "react-hook-form";
import { yupResolver } from "@hookform/resolvers/yup";
import { CombinedGraphQLErrors } from "@apollo/client";
import Loader from "../loaders/loader";
import { useState } from "react";
import { apolloClient, CREATE_USER_MUTATION } from "~/utils";

const schema = yup
    .object({
        email: yup.string().required("Email is required").email("Please enter a valid remail"),
        plainPassword: yup.string().required("Password is required"),
        passwordConfirmation: yup
            .string()
            .required("Password confirmation is required")
            .oneOf([yup.ref("plainPassword")], "Password must match"),
    })
    .required();

interface SignUpFormInput {
    email: string;
    plainPassword: string;
    passwordConfirmation: string;
}

interface SignUpFormInterface {
    prefill?: { email?: string, plainPassword?: string, passwordConfirmation?: string };
    onSuccess?: (e?: any) => void;
    onFail?: (e?: any) => void;
}

export default function SignUpForm({ prefill, onSuccess, onFail }: SignUpFormInterface) {
    const [loading, setLoading] = useState<boolean>(false);
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors },
    } = useForm<SignUpFormInput>({
        resolver: yupResolver(schema),
        defaultValues: {
            email: prefill?.email,
            plainPassword: prefill?.plainPassword,
            passwordConfirmation: prefill?.passwordConfirmation,
        },
    });

    const onSubmit: SubmitHandler<SignUpFormInput> = async (formData) => {
        setLoading(true);
        try {
            const { data } = await apolloClient.mutate({
                mutation: CREATE_USER_MUTATION,
                variables: {
                    email: formData.email,
                    plainPassword: formData.plainPassword,
                },
            });
            if (onSuccess) onSuccess(data);
        } catch (error: any) {
            if (CombinedGraphQLErrors.is(error)) {
                error.errors.forEach((graphQlError) => {
                    const violations: any = graphQlError?.extensions?.violations;
                    if (violations) {
                        violations.forEach((violation: any) => {
                            setError(violation.path, {
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

    if (loading) return <Loader />

    return (
        <form className="space-y-6" onSubmit={handleSubmit(onSubmit)}>
            <div>
                <Input label="Email" {...register("email")} type="text" error={errors.email} />
            </div>

            <div>
                <Input label="Password" {...register("plainPassword")} type="password" error={errors.plainPassword} />
            </div>

            <div>
                <Input
                    label="Confirm Password"
                    {...register("passwordConfirmation")}
                    type="password"
                    error={errors.passwordConfirmation}
                />
            </div>

            {errors.root && (
                <div>
                    <Alert title={errors.root.message} type="error" />
                </div>
            )}

            <div className="flex gap-5">
                <Button type="submit" label="Sign Up" size="lg" className="w-full" />
            </div>
        </form>
    );
}
