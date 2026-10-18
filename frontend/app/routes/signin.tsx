import type { Route } from "./+types/signin";
import { constants } from "~/utils";
import SignInForm from "~/components/forms/signInForm";
import { Link, useLocation, useNavigate } from "react-router";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Sign In` }, { name: "description", content: "Sign in to HRT-Diary!" }];
}

export default function SignIn() {
    const navigate = useNavigate();

    const handleSuccess = () => {
        navigate("/dashboard");
    };

    return (
        <>
            <div className="sm:mx-auto sm:w-full sm:max-w-sm">
                <img
                    alt="Your Company"
                    src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600"
                    className="mx-auto h-10 w-auto dark:hidden"
                />
                <img
                    alt="Your Company"
                    src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500"
                    className="mx-auto h-10 w-auto not-dark:hidden"
                />
                <h2 className="mt-10 text-center text-2xl/9 font-bold tracking-tight text-gray-900 dark:text-white">
                    Sign in to your account
                </h2>
            </div>

            <div className="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
                <SignInForm onSuccess={handleSuccess} prefill={{ email: useLocation().state?.email }} />
                <p className="mt-10 text-center text-sm/6 text-gray-500 dark:text-gray-400">
                    Not an account yet?{" "}
                    <Link
                        to="/signup"
                        className="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        Create your account
                    </Link>
                </p>
            </div>
        </>
    );
}
