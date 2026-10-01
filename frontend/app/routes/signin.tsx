import type { Route } from "./+types/signin";
import { constants } from "~/utils";
import SignInForm from "~/components/forms/signInForm";
import { useLocation, useNavigate } from "react-router";

export function meta({}: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Sign In` }, { name: "description", content: "Sign in to HRT-Diary!" }];
}

export default function SignIn() {
    const navigate = useNavigate();

    const handleSuccess = (e: any) => {
        navigate("/dashboard");
    };

    return (
        <div>
            <h1 className="mb-5">Sign In</h1>
            <SignInForm onSuccess={(e) => handleSuccess(e)} prefill={{ email: useLocation().state?.email }} />
        </div>
    );
}
