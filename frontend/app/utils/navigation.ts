export function isCurrentPage(currentLocation: string, path: string, exact: boolean = false): boolean {
    return exact
        ? currentLocation === path
        : currentLocation.startsWith(path);
}