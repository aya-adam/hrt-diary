import { useEffect, useState } from "react";

export function useDebounce<T>(value: T, delay = 800): T {
    const [debounced, setDebounced] = useState(value);

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(value), delay);
        return () => clearTimeout(timer); // resets on every change
    }, [value, delay]);

    return debounced;
}