import { apolloClient, constants, Post, type PostDto } from "~/utils";
import Alert from "~/components/notifications/alert";
import Button from "~/components/elements/button";
import { PlusIcon } from "@heroicons/react/24/outline";
import Badge from "~/components/elements/badge";
import Divider from "~/components/elements/divider";
import moment from "moment";
import type { Route } from "./+types/listPosts";
import { Link } from "react-router";

export function meta({ }: Route.MetaArgs) {
    return [
        { title: `${constants.PAGE_TITLE} | Diary Entries` },
        { name: "description", content: "HRT-Diary Entries" },
    ];
}

export async function clientLoader() {
    const { data } = await apolloClient.query({ query: Post.COLLECTION_QUERY });
    const posts: Post[] = data?.posts ? data?.posts.map((post: PostDto) => Post.fromDto(post)) : [];
    return { posts };
}

export default function ListPosts({ loaderData }: Route.ComponentProps) {
    const { posts } = loaderData;

    return (
        <div>
            <div className="pb-10 md:flex md:items-center md:justify-between">
                <div className="min-w-0 flex-1">
                    <h2 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        Diary Entries
                    </h2>
                </div>
                <div className="mt-4 flex md:mt-0 md:ml-4">
                    <Button label="New" leadingIcon={PlusIcon} to="/dashboard/diary/create" />
                </div>
            </div>
            {posts.length > 0
                ? (
                    <div className="grid grid-cols-1 gap-4">
                        {posts.map((post) => (
                            <Link
                                to={`/dashboard/diary/${post.id}`}
                                key={post.id}
                                className="flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-xs focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-indigo-600 hover:border-gray-400 dark:border-white/10 dark:bg-gray-800/50 dark:shadow-none dark:focus-within:outline-indigo-500 dark:hover:border-white/25"
                            >
                                <div className="w-full space-y-3">
                                    <h2 className="font-semibold">{post.title} {post.publishedAt && <Badge>Published</Badge>}</h2>
                                    <p className="line-clamp-3">{post.excerpt ?? post.text}</p>
                                    <Divider />
                                    <div className="flex justify-between gap-1.5">
                                        <span className="flex-1 text-right text-sm">{moment.duration(post.createdAt.diff(moment())).humanize(true)}</span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                )
                : (
                    <Alert>No diary entires found</Alert>
                )}
        </div>
    );
}
