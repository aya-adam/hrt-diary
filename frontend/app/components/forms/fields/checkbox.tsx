import { forwardRef, type InputHTMLAttributes } from "react";
import type { FieldError } from "react-hook-form";

interface CheckboxInterface extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    description?: string;
    error?: FieldError;
}

const Checkbox = forwardRef<HTMLInputElement, CheckboxInterface>(({ label, description, error, ...rest }, ref) => {
    return (
        <>
            <div className="flex gap-3">
                <div className="flex h-6 shrink-0 items-center">
                    <div className="group grid size-4 grid-cols-1">
                        <input
                            ref={ref}
                            id={rest.id || rest.name}
                            {...rest}
                            className="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 checked:border-indigo-600 dark:checked:border-indigo-500 checked:bg-indigo-600 dark:checked:bg-indigo-500 indeterminate:border-indigo-600 dark:indeterminate:border-indigo-500 indeterminate:bg-indigo-600 dark:indeterminate:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:focus-visible:outline-indigo-500 disabled:border-gray-300 dark:disabled:border-white/5 disabled:bg-gray-100 dark:disabled:bg-white/10 disabled:checked:bg-gray-100 dark:disabled:checked:bg-white/10 forced-colors:appearance-auto"
                        />
                        <svg
                            fill="none"
                            viewBox="0 0 14 14"
                            className="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25 dark:group-has-disabled:stroke-white/25"
                        >
                            <path
                                d="M3 8L6 11L11 3.5"
                                strokeWidth={2}
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                className="opacity-0 group-has-checked:opacity-100"
                            />
                            <path
                                d="M3 7H11"
                                strokeWidth={2}
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                className="opacity-0 group-has-indeterminate:opacity-100"
                            />
                        </svg>
                    </div>
                </div>
                <div className="text-sm/6">
                    <label htmlFor={rest.id || rest.name} className="font-medium text-gray-900 dark:text-white">
                        {label}
                    </label>
                    {description && (
                        <p id={`comments-${rest.id || rest.name}}`} className="text-gray-500 dark:text-gray-400">
                            {description}
                        </p>
                    )}
                </div>
            </div>
            {error && <p className="mt-2 text-sm text-red-600">{error.message}</p>}
        </>
    );
});

Checkbox.displayName = "Checkbox";

export default Checkbox;
