import { ChevronLeftIcon } from "@heroicons/react/24/outline";
import Button from "~/components/elements/button";
import { apolloClient, constants, Post } from "~/utils";
import type { Route } from "./+types/editPost";
import { useNavigate } from "react-router";
import { toast } from "react-toastify";
import PostsForm from "~/components/forms/postsForm";

export function meta({ }: Route.MetaArgs) {
    return [
        { title: `${constants.PAGE_TITLE} | Edit Diary Entry` },
        { name: "description", content: "Edit a diary entry" },
    ];
}

export async function clientLoader({ params }: Route.LoaderArgs) {
    const { data } = await apolloClient.query({
        query: Post.ITEM_QUERY,
        variables: {
            id: Post.toIri(params.postUuid),
        }
    });
    const post: Post = Post.fromDto(data?.post);
    return { post };
}

export default function EditPost({ loaderData }: Route.ComponentProps) {
    const { post } = loaderData;
    const navigate = useNavigate();

    const onSuccess = (post: Post) => {
        toast.success("Diary entry successfully updated", {
            toastId: `postSuccessfullyUpdatedToast_${post.id}`,
        })
        navigate(`/dashboard/diary/${post.id}`);
    };

    return (
        <div>
            <div className="pb-10 md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        Edit Entry
                    </h2>
                </div>
                <div className="mt-4 flex md:mt-0 md:ml-4">
                    <Button label="Cancel" leadingIcon={ChevronLeftIcon} to={`/dashboard/diary/${post.id}`} />
                </div>
            </div>
            <PostsForm prefill={post} onSuccess={onSuccess} />
        </div>
    );
}