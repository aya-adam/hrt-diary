import { forwardRef, useState, type InputHTMLAttributes } from "react";
import type { FieldError } from "react-hook-form";
import { ExclamationCircleIcon } from "@heroicons/react/16/solid";
import { joinClasses } from "~/utils";
import { EyeIcon, EyeSlashIcon } from "@heroicons/react/20/solid";

interface PasswordInputInterface extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: FieldError;
}

const PasswordInput = forwardRef<HTMLInputElement, PasswordInputInterface>(({ label, className, error, ...rest }, ref) => {
    const [displayPassword, setDisplayPassword] = useState<boolean>(false);

    const toggleDisplayPassword = () => {
        setDisplayPassword((prev) => !prev);
    };

    return (
        <>
            {label && (
                <label htmlFor="email" className="block text-sm/6 font-medium text-gray-900 dark:text-white">
                    {label}
                </label>
            )}
            <div className="mt-2 grid grid-cols-1">
                <input
                    ref={ref}
                    type={displayPassword ? "text" : "password"}
                    {...rest}
                    className={joinClasses(
                        "block col-start-1 row-start-1 w-full rounded-md bg-white pl-3 py-1.5 text-base outline-1 -outline-offset-1 focus:outline-2 focus:-outline-offset-2 sm:text-sm/6 dark:bg-white/5 dark:text-white dark:outline-white/10 dark:placeholder:text-gray-500 dark:focus:outline-indigo-500",
                        error
                            ? "pr-15 text-red-900 outline-red-300 placeholder:text-red-300 focus:outline-red-600"
                            : "pr-9 text-gray-900 outline-gray-300 placeholder:text-gray-400 focus:outline-indigo-600",
                    )}
                />
                {displayPassword ? (
                    <EyeSlashIcon
                        title="Hide password"
                        aria-hidden="true"
                        className={joinClasses(
                            "cursor-pointer col-start-1 row-start-1 size-5 self-center justify-self-end text-indigo-600 dark:text-indigo-500 hover:text-indigo-500 dark:hover:text-indigo-400 sm:size-4",
                            error ? "mr-9" : "mr-3",
                        )}
                        onClick={toggleDisplayPassword}
                        />
                    ) : (
                        <EyeIcon
                        title="Show password"
                        aria-hidden="true"
                        className={joinClasses(
                            "cursor-pointer col-start-1 row-start-1 size-5 self-center justify-self-end text-indigo-600 dark:text-indigo-500 hover:text-indigo-500 dark:hover:text-indigo-400 sm:size-4",
                            error ? "mr-9" : "mr-3",
                        )}
                        onClick={toggleDisplayPassword}
                    />
                )}
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

PasswordInput.displayName = "PasswordInput";

export default PasswordInput;
