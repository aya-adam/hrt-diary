import { joinClasses } from "~/utils";
import {
    CheckCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XCircleIcon,
} from "@heroicons/react/20/solid";
import type React from "react";

export type SupportedErrorTypes = "info" | "success" | "warning" | "error";

interface MessageInterface extends React.PropsWithChildren {
    title?: string;
    list?: string[];
    type?: SupportedErrorTypes;
}

export default function Alert({ title, list = [], type = "info", children }: MessageInterface) {
    return (
        <div
            className={joinClasses(
                "rounded-2xl shadow-xs p-4",
                type === "info" ? "bg-blue-50 dark:bg-blue-500/10 dark:outline dark:outline-blue-500/20" : "",
                type === "success" ? "bg-green-50 dark:bg-green-500/10 dark:outline dark:outline-green-500/20" : "",
                type === "warning" ? "bg-yellow-50 dark:bg-yellow-500/10 dark:outline dark:outline-yellow-500/15" : "",
                type === "error" ? "bg-red-50 dark:bg-red-500/15 dark:outline dark:outline-red-500/25" : "",
            )}
        >
            <div className="flex">
                <div className="shrink-0">
                    {type === "info" && <InformationCircleIcon aria-hidden="true" className="size-5 text-blue-400" />}
                    {type === "success" && <CheckCircleIcon aria-hidden="true" className="size-5 text-green-400" />}
                    {type === "warning" && (
                        <ExclamationTriangleIcon
                            aria-hidden="true"
                            className="size-5 text-yellow-400 dark:text-yellow-300"
                        />
                    )}
                    {type === "error" && <XCircleIcon aria-hidden="true" className="size-5 text-red-400" />}
                </div>
                <div className="flex flex-col gap-2 ml-3">
                    {title && (
                        <h3
                            className={joinClasses(
                                "text-sm font-bold",
                                type === "info" ? "text-blue-800 dark:text-blue-400" : "",
                                type === "success" ? "text-green-800 dark:text-green-200" : "",
                                type === "warning" ? "text-yellow-800 dark:text-yellow-100" : "",
                                type === "error" ? "text-red-800 dark:text-red-200" : "",
                            )}
                        >
                            {title}
                        </h3>
                    )}
                    {children && (
                        <div
                            className={joinClasses(
                                "text-sm",
                                type === "info" ? "text-blue-700 dark:text-blue-300" : "",
                                type === "success" ? "text-green-700 dark:text-green-200/85" : "",
                                type === "warning" ? "text-yellow-700 dark:text-yellow-100/80" : "",
                                type === "error" ? "text-red-700 dark:text-red-200/80" : "",
                            )}
                        >
                            {children}
                        </div>
                    )}
                    {list && list.length > 0 && (
                        <div
                            className={joinClasses(
                                "text-sm",
                                type === "info" ? "text-blue-700 dark:text-blue-300" : "",
                                type === "success" ? "text-green-700 dark:text-green-200/85" : "",
                                type === "warning" ? "text-yellow-700 dark:text-yellow-100/80" : "",
                                type === "error" ? "text-red-700 dark:text-red-200/80" : "",
                            )}
                        >
                            <ul role="list" className="list-disc space-y-1 pl-5">
                                {list.map((listItem: string, index: number) => <li key={index}>{listItem}</li>)}
                            </ul>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
