import type React from "react";

export default function BasicWrapper({ children }: React.PropsWithChildren) {
    return <div className="h-full p-10">{children}</div>;
}
