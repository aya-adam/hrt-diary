import { forwardRef, type InputHTMLAttributes } from "react";
import type { FieldError } from "react-hook-form";
import { ExclamationCircleIcon } from "@heroicons/react/16/solid";
import { joinClasses } from "~/utils";

interface InputInterface extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: FieldError;
}

const Input = forwardRef<HTMLInputElement, InputInterface>(({ label, error, ...rest }, ref) => {
    return (
        <>
            {label && (
                <label htmlFor={rest.name} className="block text-sm/6 font-medium text-gray-900 dark:text-white">
                    {label}
                </label>
            )}
            <div className={joinClasses("mt-2", error ? "grid grid-cols-1" : "")}>
                <input
                    ref={ref}
                    id={rest.name}
                    {...rest}
                    className={joinClasses(
                        "block w-full rounded-md bg-white dark:bg-white/5 py-1.5 text-base outline-1 -outline-offset-1 focus:outline-2 focus:-outline-offset-2 sm:text-sm/6",
                        error
                            ? "col-start-1 row-start-1 pr-10 pl-3 text-red-900 dark:text-red-400 outline-red-300 dark:outline-red-500/50 placeholder:text-red-300 focus:outline-red-600 sm:pr-9"
                            : "px-3 text-gray-900 dark:text-white outline-gray-300 dark:outline-white/10 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-indigo-600 dark:focus:outline-indigo-500",
                    )}
                />
                {error && (
                    <ExclamationCircleIcon
                        aria-hidden="true"
                        className="pointer-events-none col-start-1 row-start-1 mr-3 size-5 self-center justify-self-end text-red-500 sm:size-4"
                    />
                )}
            </div>
            {error && <p className="mt-2 text-sm text-red-600">{error.message}</p>}
        </>
    );
});

Input.displayName = "Input";

export default Input;
