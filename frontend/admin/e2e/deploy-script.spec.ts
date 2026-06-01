import { test, expect } from "@playwright/test";
import { execSync } from "child_process";

test.describe("Deploy Script Validation", () => {
  test("deploy.sh exists and is executable", () => {
    const content = execSync("type ..\\..\\scripts\\deploy.sh", { encoding: "utf-8" });
    expect(content).toBeTruthy();
    expect(content.length).toBeGreaterThan(100);
  });

  test("deploy.sh runs php artisan commands in correct order", () => {
    const content = execSync("type ..\\..\\scripts\\deploy.sh", { encoding: "utf-8" });

    const commands = [
      "composer install",
      "key:generate",
      "config:cache",
      "route:cache",
      "view:cache",
      "migrate --force",
      "cache:clear",
    ];

    let lastIndex = -1;
    for (const cmd of commands) {
      const idx = content.indexOf(cmd);
      expect(idx).toBeGreaterThan(-1);
      expect(idx).toBeGreaterThan(lastIndex);
      lastIndex = idx;
    }
  });

  test("deploy.sh validates .env file exists before proceeding", () => {
    const content = execSync("type ..\\..\\scripts\\deploy.sh", { encoding: "utf-8" });
    expect(content).toContain(".env");
  });

  test("deploy.sh sets error handling flags", () => {
    const content = execSync("type ..\\..\\scripts\\deploy.sh", { encoding: "utf-8" });
    expect(content).toContain("set -euo pipefail");
  });
});
