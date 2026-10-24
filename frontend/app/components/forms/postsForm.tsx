import * as yup from "yup";
import Input from "~/components/forms/fields/input";
import Button from "~/components/elements/button";
import Alert from "~/components/notifications/alert";
import { useForm, type SubmitHandler } from "react-hook-form";
import { yupResolver } from "@hookform/resolvers/yup";
import { CombinedGraphQLErrors } from "@apollo/client";
import Loader from "../loaders/loader";
import { useState } from "react";
import { apolloClient, emptyToUndefined, Post } from "~/utils";
import Toggle from "./fields/toggle";
import Textarea from "./fields/textarea";

const createPost = async (formData: any): Promise<Post> => {
    const { data } = await apolloClient.mutate({
        mutation: Post.CREATE_MUTATION,
        variables: formData,
    });

    return Post.fromDto(data?.createPost.post);
}

const updatePost = async (id: string, formData: any): Promise<Post> => {
    const { data } = await apolloClient.mutate({
        mutation: Post.UPDATE_MUTATION,
        variables: {
            id: Post.toIri(id),
            ...formData,
        }
    });

    return Post.fromDto(data?.updatePost.post);
}

const schema = yup
    .object({
        title: yup.string().trim().required("Title is required"),
        excerpt: yup.string().trim().transform(emptyToUndefined).optional(),
        text: yup.string().trim().transform(emptyToUndefined).optional(),
        published: yup.boolean(),
    })
    .required();

type PostsFormInput = yup.InferType<typeof schema>;

interface PostsFormInterface {
    prefill?: Post;
    onSuccess?: (post: Post) => void;
    onFail?: (e?: any) => void;
}

export default function PostsForm({ prefill, onSuccess, onFail }: PostsFormInterface) {
    const [loading, setLoading] = useState<boolean>(false);
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors },
    } = useForm<PostsFormInput>({
        resolver: yupResolver(schema),
        defaultValues: {
            title: prefill?.title ?? undefined,
            excerpt: prefill?.excerpt ?? undefined,
            text: prefill?.text ?? undefined,
            published: prefill?.publishedAt ? true : false,
        },
    });

    const onSubmit: SubmitHandler<PostsFormInput> = async (formData) => {
        setLoading(true);
        try {
            const post = prefill?.id ? await updatePost(prefill?.id, formData) : await createPost(formData);
            if (onSuccess) onSuccess(post);
        } catch (error: any) {
            if (CombinedGraphQLErrors.is(error)) {
                error.errors.forEach((graphQlError) => {
                    const violations: any = graphQlError?.extensions?.violations;
                    if (violations) {
                        violations.forEach((violation: any) => {
                            setError(violation.path, {
                                type: "custom",
                                message: violation.message,
                            });
                        });
                    } else {
                        setError("root", {
                            type: "custom",
                            message: graphQlError.message,
                        });
                    }
                });
            } else {
                setError("root", {
                    type: "custom",
                    message: "An unknown error occured. Please try again.",
                });
            }
            setLoading(false);
            if (onFail) onFail(error);
        }
    };

    if (loading) return <Loader />;

    return (
        <form className="space-y-6" onSubmit={handleSubmit(onSubmit)}>
            <div>
                <Input label="Title" {...register("title")} type="text" error={errors.title} />
            </div>

            <div>
                <Textarea label="Excerpt" {...register("excerpt")} rows={3} error={errors.excerpt} />
            </div>

            <div>
                <Textarea label="Text" {...register("text")} rows={14} error={errors.text} />
            </div>

            <div>
                <Toggle label="Publish" description="Makes the post public and viewable by everyone." {...register("published")} error={errors.published} />
            </div>

            {errors.root && (
                <div>
                    <Alert title={errors.root.message} type="error" />
                </div>
            )}

            <div className="flex gap-5">
                <Button type="submit" label={prefill ? "Update" : "Create"} size="lg" className="w-full" />
            </div>
        </form>
    );
}
