import { forwardRef, type InputHTMLAttributes } from "react";
import type { FieldError } from "react-hook-form";
import { ExclamationCircleIcon } from "@heroicons/react/16/solid";
import { joinClasses } from "~/utils";

interface InputInterface extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    description?: string;
    error?: FieldError;
}

const Toggle = forwardRef<HTMLInputElement, InputInterface>(({ label, description, className, error, ...rest }, ref) => {
    return (
        <>
            <div className="flex items-center justify-between">
                {(label || description) && (
                    <span className="flex grow flex-col">
                        {label && (
                            <label id={`${rest.name}-label`} className="text-sm/6 font-medium text-gray-900 dark:text-white">
                                {label}
                            </label>
                        )}
                        {description && (
                            <span id={`${rest.name}-description`} className="text-sm text-gray-500 dark:text-gray-400">
                                {description}
                            </span>
                        )}
                    </span>
                )}
                <div
                    className={joinClasses(
                        "group relative inline-flex w-11 shrink-0 rounded-full bg-gray-200 p-0.5 inset-ring outline-offset-2 outline-indigo-600 transition-colors duration-200 ease-in-out has-checked:bg-indigo-600 has-focus-visible:outline-2 dark:bg-white/5 dark:outline-indigo-500 dark:has-checked:bg-indigo-500",
                        error
                            ? "inset-ring-red-300 dark:inset-ring-red-900"
                            : "inset-ring-gray-900/5 dark:inset-ring-white/10",
                    )}
                >
                    <span className="size-5 rounded-full bg-white shadow-xs ring-1 ring-gray-900/5 transition-transform duration-200 ease-in-out group-has-checked:translate-x-5" />
                    <input
                        ref={ref}
                        type="checkbox"
                        {...rest}
                        aria-labelledby={label ? `${rest.name}-label` : undefined}
                        aria-describedby={description ? `${rest.name}-description` : undefined}
                        className="absolute inset-0 size-full appearance-none focus:outline-hidden"
                    />
                </div>
            </div>
            {error && <p className="mt-2 text-sm text-red-600">{error.message}</p>}
        </>
    );
});

Toggle.displayName = "Toggle";

export default Toggle;
