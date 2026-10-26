import { ChevronLeftIcon } from "@heroicons/react/24/outline";
import Button from "~/components/elements/button";
import { constants, Post } from "~/utils";
import type { Route } from "./+types/createPost";
import PostsForm from "~/components/forms/postsForm";
import { useNavigate } from "react-router";
import { toast } from "react-toastify";

export function meta({ }: Route.MetaArgs) {
    return [
        { title: `${constants.PAGE_TITLE} | Create Diary Entry` },
        { name: "description", content: "Create a new diary entry" },
    ];
}

export default function CreatePost() {
    const navigate = useNavigate();

    const onSuccess = (post: Post) => {
        toast.success("Diary entry successfully created", {
            toastId: "postSuccessfullyCreatedToast_1",
        })
        navigate(`/dashboard/diary/${post.id}`);
    };

    return (
        <div>
            <div className="pb-10 md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        Create Entry
                    </h2>
                </div>
                <div className="mt-4 flex md:mt-0 md:ml-4">
                    <Button label="Cancel" leadingIcon={ChevronLeftIcon} to="/dashboard/diary" />
                </div>
            </div>
            <PostsForm onSuccess={onSuccess} />
        </div>
    );
}