import { ChevronLeftIcon, PencilIcon } from "@heroicons/react/24/outline";
import Button from "~/components/elements/button";
import { apolloClient, constants, Post, splitButtonToast } from "~/utils";
import type { Route } from "./+types/showPost";
import { TrashIcon } from "@heroicons/react/20/solid";
import { toast } from "react-toastify";
import { useNavigate } from "react-router";

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

const mockPromise = (success: boolean): Promise<void> => {
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            if (success) {
                resolve();
            } else {
                reject();
            }
        }, 5000);
    });
}

export default function ShowPost({ loaderData }: Route.ComponentProps) {
    const { post } = loaderData;
    const navigate = useNavigate();

    const deletePost = async () => {
        const proceed = await splitButtonToast({
            position: "top-center",
            data: {
                title: "Delete entry",
                text: "You're about to delete this entry. This action cannot be reversed. Do you want to proceed?",
                confirmBtnText: "Delete",
                rejectBtnText: "Cancel",
            },
        });

        if (proceed) {
            toast.promise(apolloClient.mutate({
                mutation: Post.DELETE_MUTATION,
                variables: { id: Post.toIri(post.id) },
            }), {
                pending: "Deleting diary entry...",
                success: {
                    render() {
                        navigate("/dashboard/diary");
                        return "Diary entry succcessfully deleted";
                    }
                },
                error: "An error occured while trying to delete diary entry",
            })
        }
    };

    return (
        <div className="flex flex-col gap-10">
            <div className="md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        {post.title}
                    </h2>
                </div>
                <div className="mt-4 flex gap-3 md:mt-0 md:ml-4">
                    <Button label="All" leadingIcon={ChevronLeftIcon} to="/dashboard/diary" />
                    <Button label="Edit" leadingIcon={PencilIcon} to={`/dashboard/diary/edit/${post.id}`} />
                    <Button label="Delete" leadingIcon={TrashIcon} style="danger" onClick={() => deletePost()} />
                </div>
            </div>
            <div className="flex gap-6">
                <span>Created: {post.createdAt.format("DD.MM.YYYY HH:mm")}</span>
                <span>Updated: {post.updatedAt.format("DD.MM.YYYY HH:mm")}</span>
            </div>
            <div className="flex flex-col gap-3">
                <p className="wrap-break-word">{post.excerpt}</p>
                <p className="wrap-break-word">{post.text}</p>
            </div>
            <div>
                Labs here ...
            </div>
        </div>
    );
}