export const emptyToUndefined = (value: unknown, originalValue: unknown) => {
    return typeof originalValue === "string" && originalValue.trim() === ""
        ? undefined
        : value;
};