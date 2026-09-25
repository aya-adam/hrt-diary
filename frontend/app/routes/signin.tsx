import { useState } from "react";
import type { Route } from "./+types/signin";
import { constants } from "~/utils";
import Loader from "~/components/loaders/loader";
import SignInForm from "~/components/forms/signInForm";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Sign In` }, { name: "description", content: "Sign in to HRT-Diary!" }];
}

export default function SignIn() {
    const [loading, setLoading] = useState<boolean>(false);

    const handleSuccess = () => {
        console.log("User successfully registered");
    };

    return (
        <div>
            <h1 className="mb-5">Sign In</h1>
            {loading === true ? <Loader /> : <SignInForm isLoading={(e) => setLoading(e)} onSuccess={handleSuccess} />}
        </div>
    );
}
