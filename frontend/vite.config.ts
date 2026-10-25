import { reactRouter } from "@react-router/dev/vite";
import tailwindcss from "@tailwindcss/vite";
import { execSync } from "node:child_process";
import { existsSync, readFileSync } from "node:fs";
import { defineConfig, loadEnv } from "vite";

export default defineConfig(({ command, mode }) => {
  const env = loadEnv(mode, process.cwd(), "");
  let server = undefined;

  if (command === "serve") {
    const host = env.DEV_HOST || "localhost";
    const certFile = `${host}.pem`;
    const keyFile = `${host}-key.pem`;

    if (!existsSync(certFile) || !existsSync(keyFile)) {
      execSync(`mkcert ${host}`, { stdio: "inherit" });
    }

    server = {
      host: host,
      https: {
        cert: readFileSync(certFile),
        key: readFileSync(keyFile),
      },
    };
  }

  return {
    plugins: [tailwindcss(), reactRouter()],
    server: server,
    resolve: {
      tsconfigPaths: true,
    },
  }
});
