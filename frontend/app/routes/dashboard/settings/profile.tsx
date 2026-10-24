import { apolloClient, constants, User } from "~/utils";
import type { Route } from "./+types/profile";
import { useAuthStore } from "~/stores/authStore";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Account` }, { name: "description", content: "HRT-Diary Account" }];
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

export default function Profile({ loaderData }: Route.ComponentProps) {
    const { user } = loaderData;

    return (
        <div className="flex flex-col gap-y-16">
            <div>
                <h2 className="text-base/7 font-semibold text-gray-900 dark:text-white">Profile</h2>

                <dl className="mt-6 divide-y divide-gray-100 border-t border-gray-200 text-sm/6 dark:divide-white/5 dark:border-white/5">
                    <div className="py-6 sm:flex">
                        <dt className="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6 dark:text-white">
                            Email address
                        </dt>
                        <dd className="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                            <div className="text-gray-900 dark:text-gray-300">{user.email}</div>
                            <button
                                type="button"
                                className="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                Update
                            </button>
                        </dd>
                    </div>
                    <div className="py-6 sm:flex">
                        <dt className="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6 dark:text-white">Account created</dt>
                        <dd className="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                            <div className="text-gray-900 dark:text-gray-300">{user.createdAt.toLocaleString()}</div>
                        </dd>
                    </div>
                    <div className="py-6 sm:flex">
                        <dt className="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6 dark:text-white">Account last updated</dt>
                        <dd className="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                            <div className="text-gray-900 dark:text-gray-300">{user.createdAt.toLocaleString()}</div>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    );
}
