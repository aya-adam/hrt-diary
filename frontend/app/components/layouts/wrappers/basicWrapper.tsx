import type React from "react";

export default function BasicWrapper({ children }: React.PropsWithChildren) {
    return (
        <div className="h-full">
            {children}
        </div>
    )
}