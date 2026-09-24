import { reactRouter } from "@react-router/dev/vite";
import tailwindcss from "@tailwindcss/vite";
import { readFileSync } from "node:fs";
import { defineConfig } from "vite";

export default defineConfig(({ command }) => ({
  plugins: [tailwindcss(), reactRouter()],
  server:
    command === "serve"
      ? {
        https: {
          key: readFileSync("./localhost-key.pem"),
          cert: readFileSync("./localhost.pem"),
        },
      }
      : undefined,
  resolve: {
    tsconfigPaths: true,
  },
}));
