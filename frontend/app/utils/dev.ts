export const mockPromise = (throwError: boolean = false, duration: number = 3000): Promise<string> => {
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            !throwError ? resolve("Success") : reject("Error");
        }, duration);
    });
}