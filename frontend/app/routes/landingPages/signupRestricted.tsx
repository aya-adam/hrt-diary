import { Link } from "react-router";
import Alert from "~/components/notifications/alert";

export default function SignupRestricted() {
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
                    Hang tight, we’re still building!
                </h2>
            </div>

            <div className="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
                <Alert>Sign-up isn’t open to the public just yet. For now, accounts are created directly by our administrators.</Alert>
                <p className="mt-10 text-center text-sm/6 text-gray-500 dark:text-gray-400">
                    Already have an account?{" "}
                    <Link
                        to="/signin"
                        className="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        Sign in as usual
                    </Link>
                </p>
            </div>
        </>
    )
}