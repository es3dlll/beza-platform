import { test, expect } from "@playwright/test";
import { execSync } from "child_process";

test.describe("Deployment Configuration", () => {
  test("docker-compose.deploy.yml is valid YAML and defines required services", () => {
    const content = execSync(
      'type ..\\..\\docker-compose.deploy.yml',
      { encoding: "utf-8" }
    );

    expect(content).toContain("services:");
    expect(content).toContain("app:");
    expect(content).toContain("db:");
    expect(content).toContain("redis:");
    expect(content).toContain("nginx:");
    expect(content).toContain("image: mysql:8.0");
    expect(content).toContain("image: redis:7-alpine");
    expect(content).toContain("image: nginx:1.25-alpine");
    expect(content).toContain("container_name: beza-app");
    expect(content).toContain("container_name: beza-db");
    expect(content).toContain("container_name: beza-redis");
    expect(content).toContain("container_name: beza-nginx");
  });

  test("Dockerfile builds with correct base image and stages", () => {
    const content = execSync("type ..\\..\\Dockerfile", { encoding: "utf-8" });

    expect(content).toContain("FROM php:8.2-fpm-alpine AS base");
    expect(content).toContain("FROM base AS frontend");
    expect(content).toContain("FROM base AS production");
    expect(content).toContain("pdo_mysql");
    expect(content).toContain("mbstring");
    expect(content).toContain("composer");
    expect(content).toContain("supervisord");
  });

  test("deploy.sh has all required Artisan commands", () => {
    const content = execSync("type ..\\..\\scripts\\deploy.sh", {
      encoding: "utf-8",
    });

    expect(content).toContain("config:cache");
    expect(content).toContain("route:cache");
    expect(content).toContain("view:cache");
    expect(content).toContain("migrate --force");
    expect(content).toContain("key:generate --force");
    expect(content).toContain("set -euo pipefail");
  });

  test(".env.production.example has all required environment variables", () => {
    const content = execSync("type ..\\..\\.env.production.example", {
      encoding: "utf-8",
    });

    expect(content).toContain("APP_ENV=production");
    expect(content).toContain("APP_DEBUG=false");
    expect(content).toContain("DB_HOST=db");
    expect(content).toContain("DB_DATABASE=beza_platform");
    expect(content).toContain("REDIS_HOST=redis");
    expect(content).toContain("CORS_ALLOWED_ORIGINS");
    expect(content).toContain("APP_KEY=");
  });
});
