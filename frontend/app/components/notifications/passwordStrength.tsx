import { joinClasses } from "~/utils";
import Alert from "./alert";

interface PasswordStrengthInterface {
    strength?: number;
    message?: string;
    error?: string;
}

export default function PasswordStrength({ strength, message, error }: PasswordStrengthInterface) {
    const barClassesArr: string[] = [];
    let barWidth: string = "0%";

    switch (strength) {
        case 0:
            barClassesArr.push("bg-red-500 dark:bg-red-400");
            barWidth = "20%";
            break;

        case 1:
            barClassesArr.push("bg-orange-500 dark:bg-orange-400");
            barWidth = "40%";
            break;

        case 2:
            barClassesArr.push("bg-yellow-500 dark:bg-yellow-400");
            barWidth = "60%";
            break;

        case 3:
            barClassesArr.push("bg-lime-500 dark:bg-lime-400");
            barWidth = "80%";
            break;

        case 4:
            barClassesArr.push("bg-green-500 dark:bg-green-400");
            barWidth = "100%";
            break;
    }

    if (error) {
        return (
            <Alert type="error">{error}</Alert>
        );
    }

    return (
        <div className="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800/50 dark:shadow-none dark:outline dark:-outline-offset-1 dark:outline-white/10">
            <div className="flex flex-col gap-2 px-4 py-5 sm:p-6">
                <span className="text-sm font-semibold">Password strength</span>
                <div className="overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                    <div
                        style={{ width: barWidth }}
                        className={joinClasses(
                            "h-2 rounded-full",
                            barClassesArr.filter(Boolean).join(" "),
                        )}
                    />
                </div>
                <p className="text-sm">{message}</p>
            </div>
        </div>
    );
}