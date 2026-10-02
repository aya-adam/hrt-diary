import type { Route } from "./+types/signup";
import { constants } from "~/utils";
import SignUpForm from "~/components/forms/signUpForm";
import { Link, useNavigate } from "react-router";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Sign Up` }, { name: "description", content: "Sign up to HRT-Diary!" }];
}

export default function SignUp() {
    const navigate = useNavigate();

    const handleSuccess = (e: any) => {
        navigate("/signin", { state: { email: e.createUser.user.email } });
    };

    return (
        <>
            <div className="sm:mx-auto sm:w-full sm:max-w-sm">
                <img
                    alt="Your Company"
                    src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600"
                    className="mx-auto h-10 w-auto"
                />
                <h2 className="mt-10 text-center text-2xl/9 font-bold tracking-tight text-gray-900">
                    Create your account
                </h2>
            </div>

            <div className="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
                <SignUpForm onSuccess={(e) => handleSuccess(e)} />
                <p className="mt-10 text-center text-sm/6 text-gray-500">
                    Already have an account?{' '}
                    <Link to="/signin" className="font-semibold text-indigo-600 hover:text-indigo-500">
                        Sign In
                    </Link>
                </p>
            </div>
        </>
    );
}
