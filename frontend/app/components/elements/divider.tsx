import type React from "react";
import { joinClasses } from "~/utils";

interface DividerInterface {
    className?: string;
}

export default function Divider({ className }: DividerInterface) {
    return <hr className={joinClasses("text-gray-300 dark:text-gray-700", className ? className : "")} />;
}
