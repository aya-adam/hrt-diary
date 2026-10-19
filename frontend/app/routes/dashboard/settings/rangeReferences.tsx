import { constants } from "~/utils";
import type { Route } from "./+types/rangeReferences";

export function meta({ }: Route.MetaArgs) {
    return [{ title: `${constants.PAGE_TITLE} | Account` }, { name: "description", content: "HRT-Diary Account" }];
}

export default function RangeReferences() {
    return (
        <div className="flex flex-col gap-y-16">
            <div>
                <h2 className="text-base/7 font-semibold text-gray-900 dark:text-white">Range references</h2>
                <p className="mt-1 text-sm/6 text-gray-500 dark:text-gray-400">Create your own hormone range references to automatically display, when a lab result is too low or too high.</p>

                <ul
                    role="list"
                    className="mt-6 divide-y divide-gray-100 border-t border-gray-200 text-sm/6 dark:divide-white/5 dark:border-white/5"
                >
                    <li className="flex justify-between gap-x-6 py-6">
                        <div className="font-medium text-gray-900 dark:text-white">E (Monotherapy)</div>
                        <button
                            type="button"
                            className="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            Update
                        </button>
                    </li>
                    <li className="flex justify-between gap-x-6 py-6">
                        <div className="font-medium text-gray-900 dark:text-white">Testo (Monotherapy)</div>
                        <button
                            type="button"
                            className="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            Update
                        </button>
                    </li>
                </ul>

                <div className="flex border-t border-gray-100 pt-6 dark:border-white/5">
                    <button
                        type="button"
                        className="text-sm/6 font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        <span aria-hidden="true">+</span> Add another reference
                    </button>
                </div>
            </div>
        </div>
    );
}
