import * as yup from "yup";
import Input from "~/components/forms/fields/input";
import Button from "~/components/elements/button";
import Alert from "~/components/notifications/alert";
import { useForm, type SubmitHandler } from "react-hook-form";
import { yupResolver } from "@hookform/resolvers/yup";
import Loader from "../loaders/loader";
import { CombinedGraphQLErrors } from "@apollo/client";
import { apolloClient, LOGIN_MUTATION } from "~/utils";
import { useState } from "react";
import { useAuthStore } from "~/stores/authStore";

const schema = yup
    .object({
        email: yup.string().required("Email is required").email("Please enter a valid remail"),
        password: yup.string().required("Password is required"),
    })
    .required();

interface SignInFormInput {
    email: string;
    password: string;
}

interface SignInFormInterface {
    prefill?: { email?: string, password?: string };
    onSuccess?: (e?: any) => void;
    onFail?: (e?: any) => void;
}

export default function SignInForm({ prefill, onSuccess, onFail }: SignInFormInterface) {
    const { setAccessTokenExpiresAt, setUserId, setUserIdentifier } = useAuthStore();
    const [loading, setLoading] = useState<boolean>(false);
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors },
    } = useForm<SignInFormInput>({
        resolver: yupResolver(schema),
        defaultValues: {
            email: prefill?.email,
            password: prefill?.password,
        },
    });

    const onSubmit: SubmitHandler<SignInFormInput> = async (formData) => {
        setLoading(true);
        try {
            const { data } = await apolloClient.mutate({
                mutation: LOGIN_MUTATION,
                variables: {
                    email: formData.email,
                    password: formData.password,
                },
            });
            setUserId(data?.authLogin.login.userId ?? null);
            setUserIdentifier(data?.authLogin.login.userIdentifier ?? null);
            setAccessTokenExpiresAt(data?.authLogin.login.accessTokenExpiresAt ?? null);
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
                <Input label="Password" {...register("password")} type="password" error={errors.password} />
            </div>

            {errors.root && (
                <div>
                    <Alert title={errors.root.message} type="error" />
                </div>
            )}

            <div className="flex gap-5">
                <Button type="submit" label="Sign In" size="lg" />
            </div>
        </form>
    );
}
