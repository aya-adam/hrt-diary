import { joinClasses } from "~/utils";

export default function Loader({ className }: { className?: string }) {
    return (
        <div
            id="loader"
            className={joinClasses(
                "my-20",
                className ? className : "",
            )}
        >
            <div>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    );
}
