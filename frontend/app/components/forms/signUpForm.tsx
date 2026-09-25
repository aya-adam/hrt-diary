import * as yup from "yup";
import Input from "~/components/forms/fields/input";
import Button from "~/components/elements/button";
import Alert from "~/components/notifications/alert";
import { useForm, type SubmitHandler } from "react-hook-form";
import { yupResolver } from "@hookform/resolvers/yup";

const schema = yup
    .object({
        email: yup.string().required("Email is required").email("Please enter a valid remail"),
        password: yup.string().required("Password is required"),
        passwordConfirmation: yup.string().required("Password confirmation is required").oneOf([yup.ref("password")], "Password must match"),
    })
    .required();

interface SignUpFormInput {
    email: string;
    password: string;
    passwordConfirmation: string;
}

interface SignUpFormInterface {
    isLoading: (e: boolean) => void;
    onSuccess?: () => void;
    onFail?: () => void;
}

export default function SignUpForm({ isLoading, onSuccess, onFail }: SignUpFormInterface) {
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors },
    } = useForm<SignUpFormInput>({ resolver: yupResolver(schema) });

    const onSubmit: SubmitHandler<SignUpFormInput> = async (data) => {
        // Create user
    };

    return (
        <form className="space-y-6" onSubmit={handleSubmit(onSubmit)}>
            <div>
                <Input label="Email" {...register("email")} type="text" error={errors.email} />
            </div>

            <div>
                <Input label="Password" {...register("password")} type="password" error={errors.password} />
            </div>

            <div>
                <Input label="Confirm Password" {...register("passwordConfirmation")} type="password" error={errors.passwordConfirmation} />
            </div>

            {errors.root && (
                <div>
                    <Alert title={errors.root.message} type="error" />
                </div>
            )}

            <div className="flex gap-5">
                <Button type="submit" label="Sign Up" size="lg" />
            </div>
        </form>
    );
}