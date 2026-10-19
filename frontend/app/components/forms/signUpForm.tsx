import * as yup from "yup";
import Input from "~/components/forms/fields/input";
import Button from "~/components/elements/button";
import Alert from "~/components/notifications/alert";
import { useForm, useWatch, type SubmitHandler } from "react-hook-form";
import { yupResolver } from "@hookform/resolvers/yup";
import { CombinedGraphQLErrors } from "@apollo/client";
import Loader from "../loaders/loader";
import { useEffect, useState } from "react";
import { apolloClient, useDebounce, User } from "~/utils";
import PasswordStrength from "../notifications/passwordStrength";

const schema = yup
    .object({
        email: yup.string().required("Email is required").email("Please enter a valid remail"),
        password: yup.string().required("Password is required"),
        passwordConfirmation: yup
            .string()
            .required("Password confirmation is required")
            .oneOf([yup.ref("password")], "Password must match"),
    })
    .required();

interface SignUpFormInput {
    email: string;
    password: string;
    passwordConfirmation: string;
}

interface SignUpFormInterface {
    prefill?: { email?: string; password?: string; passwordConfirmation?: string };
    onSuccess?: (user: User) => void;
    onFail?: (e?: any) => void;
}

export default function SignUpForm({ prefill, onSuccess, onFail }: SignUpFormInterface) {
    const [loading, setLoading] = useState<boolean>(false);
    const [passwordStrength, setPasswordStrength] = useState<number>();
    const [passwordStrengthMessage, setPasswordStrengthMessage] = useState<string>();
    const [passwordStrengthError, setPasswordStrengthError] = useState<string>();
    const {
        control,
        register,
        handleSubmit,
        setError,
        formState: { errors },
    } = useForm<SignUpFormInput>({
        resolver: yupResolver(schema),
        defaultValues: {
            email: prefill?.email,
            password: prefill?.password,
            passwordConfirmation: prefill?.passwordConfirmation,
        },
    });

    const password = useWatch({ control, name: "password" });
    const debouncedPassword = useDebounce(password, 800);

    const onSubmit: SubmitHandler<SignUpFormInput> = async (formData) => {
        setLoading(true);
        try {
            const { data } = await apolloClient.mutate({
                mutation: User.CREATE_MUTATION,
                variables: {
                    email: formData.email,
                    password: formData.password,
                },
            });
            if (onSuccess) onSuccess(User.fromDto(data?.createUser.user));
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

    useEffect(() => {
        if (!debouncedPassword) {
            setPasswordStrength(undefined);
            setPasswordStrengthMessage(undefined);
            return;
        }

        const controller = new AbortController();

        const checkPasswordStrength = async () => {
            try {
                const { data } = await apolloClient.mutate({
                    mutation: User.CHECK_PW_STRENGTH_MUTATION,
                    variables: { password: debouncedPassword },
                });
                setPasswordStrength(data?.checkPasswordStrength.passwordStrength.estimatedStrength);
                setPasswordStrengthMessage(data?.checkPasswordStrength.passwordStrength.message);
            } catch (error) {
                setPasswordStrengthError("An error occured while trying to determine the strength of the password.");
                setPasswordStrength(undefined);
                setPasswordStrengthMessage(undefined);
            }
        };


        checkPasswordStrength();

        return () => controller.abort();
    }, [debouncedPassword]);

    if (loading) return <Loader />;

    return (
        <form className="space-y-6" onSubmit={handleSubmit(onSubmit)}>
            <div>
                <Input label="Email" {...register("email")} type="text" error={errors.email} />
            </div>

            <div>
                <Input label="Password" {...register("password")} type="password" error={errors.password} />
            </div>

            {undefined !== passwordStrength && (
                <div>
                    <PasswordStrength strength={passwordStrength} message={passwordStrengthMessage} error={passwordStrengthError} />
                </div>
            )}

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
