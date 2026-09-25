import type React from "react";

export default function Badge({ children }: React.PropsWithChildren) {
    return (
        <span className="inline-flex items-center rounded-full bg-indigo-100 dark:bg-indigo-400/10 px-2 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-400 dark:inset-ring dark:inset-ring-indigo-400/30">
            {children}
        </span>
    );
}
