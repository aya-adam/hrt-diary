import { toast, type ToastContentProps, type ToastOptions } from "react-toastify";

type SplitButtonToastData = {
    title: string,
    text: string,
    confirmBtnText: string,
    rejectBtnText: string,
}

function SplitButtonToast({closeToast, data}: ToastContentProps<SplitButtonToastData>) {
    return (
        <div className="flex w-full divide-x divide-gray-200 dark:divide-white/10">
            <div className="flex w-0 flex-1 items-center p-4">
                <div className="w-full">
                    <p className="text-sm font-medium text-gray-900 dark:text-white">{data.title}</p>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {data.text}
                    </p>
                </div>
            </div>
            <div className="flex">
                <div className="flex flex-col divide-y divide-gray-200 dark:divide-white/10">
                    <div className="flex h-0 flex-1">
                        <button
                            type="button"
                            onClick={() => {
                                closeToast(true)
                            }}
                            className="flex w-full items-center justify-center rounded-none rounded-tr-lg px-4 py-3 text-sm font-medium text-indigo-600 hover:text-indigo-500 focus:z-10 focus:outline-2 focus:outline-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 dark:focus:outline-indigo-400"
                        >
                            {data.confirmBtnText}
                        </button>
                    </div>
                    <div className="flex h-0 flex-1">
                        <button
                            type="button"
                            onClick={() => {
                                closeToast(false)
                            }}
                            className="flex w-full items-center justify-center rounded-none rounded-br-lg px-4 py-3 text-sm font-medium text-gray-700 hover:text-gray-500 focus:outline-2 focus:outline-indigo-500 dark:text-gray-300 dark:hover:text-white dark:focus:outline-indigo-400"
                        >
                            {data.rejectBtnText}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    )
}

export const splitButtonToast = (options?: ToastOptions<SplitButtonToastData>): Promise<boolean> => {
    return new Promise((resolve) => {
        toast(SplitButtonToast, {
            toastId: options?.toastId ?? "splitButtonToast",
            autoClose: options?.autoClose ?? false,
            closeButton: options?.closeButton ?? false,
            className: options?.className ?? "p-0!",
            data: options?.data ?? {
                title: "Title",
                text: "Description text...",
                confirmBtnText: "Confirm",
                rejectBtnText: "Cancel",
            },
            ...options,
            onClose(confirmed) {
                resolve(confirmed === true);
            },
        });
    });
};