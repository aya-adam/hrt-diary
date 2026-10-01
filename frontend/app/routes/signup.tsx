import type { Route } from "./+types/signup";
import { constants } from "~/utils";
import SignUpForm from "~/components/forms/signUpForm";
import { useNavigate } from "react-router";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Sign Up` }, { name: "description", content: "Sign up to HRT-Diary!" }];
}

export default function SignUp() {
    const navigate = useNavigate();

    const handleSuccess = (e: any) => {
        navigate("/signin", { state: { email: e.createUser.user.email } });
    };

    return (
        <div>
            <h1 className="mb-5">Sign Up</h1>
            <SignUpForm onSuccess={(e) => handleSuccess(e)} />
        </div>
    );
}
