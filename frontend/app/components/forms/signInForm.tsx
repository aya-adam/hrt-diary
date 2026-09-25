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
    })
    .required();

interface SignInFormInput {
    email: string;
    password: string;
}

interface SignInFormInterface {
    isLoading: (e: boolean) => void;
    onSuccess?: () => void;
    onFail?: () => void;
}

export default function SignInForm({ isLoading, onSuccess, onFail }: SignInFormInterface) {
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors },
    } = useForm<SignInFormInput>({ resolver: yupResolver(schema) });

    const onSubmit: SubmitHandler<SignInFormInput> = async (data) => {
        // Authenticate user
    };

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