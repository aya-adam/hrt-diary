import type { ComponentType, SVGProps } from "react";
import { Link } from "react-router";
import { joinClasses } from "~/utils";

interface ButtonInterface {
    label: string;
    className?: string;
    type?: "button" | "submit" | "reset";
    style?: "primary" | "secondary" | "soft";
    rounded?: boolean;
    size?: "xs" | "sm" | "md" | "lg" | "xl" | "2xl";
    leadingIcon?: ComponentType<SVGProps<SVGSVGElement>>;
    trailingIcon?: ComponentType<SVGProps<SVGSVGElement>>;
    active?: boolean;
    to?: string | { pathname?: string; search?: string; hash?: string };
    onClick?: () => void;
}

export default function Button({
    label,
    className,
    type = "button",
    style = "primary",
    rounded = false,
    size = "md",
    leadingIcon: LeadingIcon,
    trailingIcon: TrailingIcon,
    active = false,
    to,
    onClick,
}: ButtonInterface) {
    const classesArr: string[] = [];
    const btnClassesArr: string[] = [];

    // General classes
    classesArr.push(
        joinClasses(
            "font-semibold shadow-xs dark:shadow-none",
            LeadingIcon || TrailingIcon ? "inline-flex items-center justify-center" : "",
        ),
    );

    switch (size) {
        case "xs":
            classesArr.push(rounded ? "rounded-full" : "rounded-sm");
            classesArr.push("px-2 py-1 text-xs");
            if (LeadingIcon || TrailingIcon) classesArr.push("gap-x-1");
            btnClassesArr.push("size-3");
            break;

        case "sm":
            classesArr.push(rounded ? "rounded-full" : "rounded-sm");
            classesArr.push("px-2 py-1 text-sm");
            if (LeadingIcon || TrailingIcon) classesArr.push("gap-x-1");
            btnClassesArr.push("size-4");
            break;

        case "md":
            classesArr.push(rounded ? "rounded-full" : "rounded-md");
            classesArr.push("px-2.5 py-1.5 text-sm");
            if (LeadingIcon || TrailingIcon) classesArr.push("gap-x-1.5");
            btnClassesArr.push("size-4");
            break;

        case "lg":
            classesArr.push(rounded ? "rounded-full" : "rounded-md");
            classesArr.push("px-3 py-2 text-sm");
            if (LeadingIcon || TrailingIcon) classesArr.push("gap-x-1.5");
            btnClassesArr.push("size-5");
            break;

        case "xl":
            classesArr.push(rounded ? "rounded-full" : "rounded-md");
            classesArr.push("px-3.5 py-2.5 text-sm");
            if (LeadingIcon || TrailingIcon) classesArr.push("gap-x-1.5");
            btnClassesArr.push("size-5");
            break;

        case "2xl":
            classesArr.push(rounded ? "rounded-full" : "rounded-md");
            classesArr.push("px-4 py-3 text-md");
            if (LeadingIcon || TrailingIcon) classesArr.push("gap-x-1.5");
            btnClassesArr.push("size-6");
            break;
    }

    switch (style) {
        case "primary":
            classesArr.push(
                "bg-indigo-600 dark:bg-indigo-500 text-white hover:bg-indigo-500 dark:hover:bg-indigo-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:focus-visible:outline-indigo-500",
                active ? "underline" : "",
            );
            break;

        case "secondary":
            classesArr.push(
                "bg-white dark:bg-white/10 text-gray-900 dark:text-white inset-ring inset-ring-gray-300 dark:inset-ring-white/5 hover:bg-gray-50 dark:hover:bg-white/20",
                active ? "underline" : "",
            );
            break;

        case "soft":
            classesArr.push(
                "bg-indigo-50 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-500/30",
                active ? "underline" : "",
            );
            break;
    }

    if (to) {
        return (
            <Link
                to={to}
                className={joinClasses(
                    classesArr.filter(Boolean).join(" "),
                    className ? className : "",
                    "cursor-pointer",
                )}
            >
                {LeadingIcon && (
                    <LeadingIcon
                        aria-hidden="true"
                        className={joinClasses("-ml-0.5", btnClassesArr.filter(Boolean).join(" "))}
                    />
                )}
                {label}
                {TrailingIcon && (
                    <TrailingIcon
                        aria-hidden="true"
                        className={joinClasses("-mr-0.5", btnClassesArr.filter(Boolean).join(" "))}
                    />
                )}
            </Link>
        );
    }

    if (onClick) {
        return (
            <button
                type={type}
                className={joinClasses(
                    classesArr.filter(Boolean).join(" "),
                    className ? className : "",
                    "cursor-pointer",
                )}
                onClick={onClick}
            >
                {LeadingIcon && (
                    <LeadingIcon
                        aria-hidden="true"
                        className={joinClasses("-ml-0.5", btnClassesArr.filter(Boolean).join(" "))}
                    />
                )}
                {label}
                {TrailingIcon && (
                    <TrailingIcon
                        aria-hidden="true"
                        className={joinClasses("-mr-0.5", btnClassesArr.filter(Boolean).join(" "))}
                    />
                )}
            </button>
        );
    }

    return (
        <button type={type} className={joinClasses(classesArr.filter(Boolean).join(" "), className ? className : "")}>
            {LeadingIcon && (
                <LeadingIcon
                    aria-hidden="true"
                    className={joinClasses("-ml-0.5", btnClassesArr.filter(Boolean).join(" "))}
                />
            )}
            {label}
            {TrailingIcon && (
                <TrailingIcon
                    aria-hidden="true"
                    className={joinClasses("-mr-0.5", btnClassesArr.filter(Boolean).join(" "))}
                />
            )}
        </button>
    );
}
