import { apolloClient, constants, User } from "~/utils";
import type { Route } from "./+types/passwordSecurity";
import PasswordForm from "~/components/forms/passwordForm";
import { useAuthStore } from "~/stores/authStore";
import { toast } from "react-toastify";
import { useState } from "react";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Passwort & Security` }, { name: "description", content: "HRT-Diary Account" }];
}

export async function clientLoader() {
    const { userId } = useAuthStore.getState();

    if (null === userId) {
        throw new TypeError("User ID is null.");
    }

    const { data } = await apolloClient.query({
        query: User.ITEM_QUERY,
        variables: { id: User.toIri(userId) }
    });
    const user: User = User.fromDto(data?.user);
    return { user };
}

export default function PasswordSecurity({ loaderData }: Route.ComponentProps) {
    const { user } = loaderData;
    const [formVersion, setFormVersion] = useState<number>(0)

    const onSuccess = () => {
        setFormVersion((v) => v + 1); // Reset the PasswordForm component
        toast("Password successfully updated", { type: "success" });
    }

    return (
        <div className="flex flex-col gap-y-16">
            <div>
                <h2 className="text-base/7 font-semibold text-gray-900 dark:text-white">Passwort & Security</h2>
                <p className="mt-1 text-sm/6 text-gray-500 dark:text-gray-400">Update your user password used for signing in.</p>
                <div className="mt-6">
                    <PasswordForm key={formVersion} user={user} onSuccess={onSuccess} />
                </div>
            </div>
        </div>
    );
}
