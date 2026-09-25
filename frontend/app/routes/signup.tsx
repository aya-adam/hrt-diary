import { useState } from "react";
import type { Route } from "./+types/signup";
import { constants } from "~/utils";
import Loader from "~/components/loaders/loader";
import SignUpForm from "~/components/forms/signUpForm";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Sign Up` }, { name: "description", content: "Sign up to HRT-Diary!" }];
}

export default function SignUp() {
    const [loading, setLoading] = useState<boolean>(false);

    const handleSuccess = () => {
        console.log("User successfully registered");
    };

    return (
        <div>
            <h1 className="mb-5">Sign Up</h1>
            {loading === true ? (
                <Loader />
            ) : (
                <SignUpForm isLoading={(e) => setLoading(e)} onSuccess={handleSuccess} />
            )}
        </div>
    );
}