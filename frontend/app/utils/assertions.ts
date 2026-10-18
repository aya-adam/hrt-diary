export function assertDefined<T>(
    value: T | undefined,
    name: string = "Value"
): asserts value is T {
    if (value === undefined) {
        throw new TypeError(`${name} is undefined.`);
    }
}