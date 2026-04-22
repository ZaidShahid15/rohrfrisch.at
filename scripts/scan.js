#!/usr/bin/env node

import fs from "node:fs/promises";
import path from "node:path";
import process from "node:process";

const DEFAULT_TIMEOUT_MS = 30000;
const DEFAULT_POST_IDLE_MS = 2500;
const DEFAULT_SCROLL_STEPS = 8;
const VALID_TYPES = new Set(["image", "script", "css", "xhr", "font", "media", "document", "other"]);

function printUsage() {
    console.log(`Usage:
  node scripts/scan.js <url> [--output result.json] [--timeout 30000] [--post-idle 2500] [--scroll-steps 8] [--headed]

Examples:
  node scripts/scan.js https://example.com
  node scripts/scan.js https://example.com --output scan-result.json`);
}

function parseArgs(argv) {
    const args = {
        url: null,
        output: null,
        timeout: DEFAULT_TIMEOUT_MS,
        postIdle: DEFAULT_POST_IDLE_MS,
        scrollSteps: DEFAULT_SCROLL_STEPS,
        headed: false,
    };

    for (let index = 0; index < argv.length; index += 1) {
        const arg = argv[index];

        if (!arg.startsWith("--") && !args.url) {
            args.url = arg;
            continue;
        }

        if (arg === "--output") {
            args.output = argv[index + 1] ?? null;
            index += 1;
            continue;
        }

        if (arg === "--timeout") {
            args.timeout = Number(argv[index + 1] ?? DEFAULT_TIMEOUT_MS);
            index += 1;
            continue;
        }

        if (arg === "--post-idle") {
            args.postIdle = Number(argv[index + 1] ?? DEFAULT_POST_IDLE_MS);
            index += 1;
            continue;
        }

        if (arg === "--scroll-steps") {
            args.scrollSteps = Number(argv[index + 1] ?? DEFAULT_SCROLL_STEPS);
            index += 1;
            continue;
        }

        if (arg === "--headed") {
            args.headed = true;
            continue;
        }

        if (arg === "--help" || arg === "-h") {
            printUsage();
            process.exit(0);
        }

        throw new Error(`Unknown argument: ${arg}`);
    }

    if (!args.url) {
        printUsage();
        throw new Error("Missing required URL argument.");
    }

    if (!Number.isFinite(args.timeout) || args.timeout <= 0) {
        throw new Error(`Invalid timeout value: ${args.timeout}`);
    }

    if (!Number.isFinite(args.postIdle) || args.postIdle < 0) {
        throw new Error(`Invalid post-idle value: ${args.postIdle}`);
    }

    if (!Number.isFinite(args.scrollSteps) || args.scrollSteps < 0) {
        throw new Error(`Invalid scroll-steps value: ${args.scrollSteps}`);
    }

    return args;
}

function safeUrl(value, baseUrl = undefined) {
    try {
        return new URL(value, baseUrl);
    } catch {
        return null;
    }
}

function normalizeUrl(rawUrl, baseUrl = undefined) {
    const parsed = safeUrl(rawUrl, baseUrl);
    return parsed ? parsed.toString() : null;
}

function getDomain(urlString) {
    const parsed = safeUrl(urlString);
    return parsed?.hostname ?? null;
}

function isExternalToOrigin(urlString, baseOrigin) {
    const resourceUrl = safeUrl(urlString, baseOrigin);
    const originUrl = safeUrl(baseOrigin);

    if (!resourceUrl || !originUrl) {
        return false;
    }

    return resourceUrl.origin !== originUrl.origin;
}

function classifyByExtension(urlString) {
    const parsed = safeUrl(urlString);
    const pathname = parsed?.pathname?.toLowerCase() ?? "";

    if (/\.(png|jpe?g|gif|webp|svg|bmp|ico|avif|tiff?)(?:$|\?)/i.test(pathname)) {
        return "image";
    }

    if (/\.(css)(?:$|\?)/i.test(pathname)) {
        return "css";
    }

    if (/\.(mjs|cjs|js)(?:$|\?)/i.test(pathname)) {
        return "script";
    }

    if (/\.(woff2?|ttf|otf|eot)(?:$|\?)/i.test(pathname)) {
        return "font";
    }

    if (/\.(mp4|webm|ogg|mp3|wav|m4a|aac|flac|mov|m3u8)(?:$|\?)/i.test(pathname)) {
        return "media";
    }

    return "other";
}

function classifyFromRequest(request) {
    const resourceType = request.resourceType();

    switch (resourceType) {
        case "stylesheet":
            return "css";
        case "script":
            return "script";
        case "image":
            return "image";
        case "media":
            return "media";
        case "font":
            return "font";
        case "xhr":
        case "fetch":
        case "websocket":
        case "eventsource":
            return "xhr";
        case "document":
            return "document";
        default:
            return classifyByExtension(request.url());
    }
}

function classifyFromContentType(contentType, fallbackType = "other") {
    if (!contentType) {
        return fallbackType;
    }

    const normalized = contentType.toLowerCase();

    if (normalized.includes("text/css")) {
        return "css";
    }

    if (
        normalized.includes("javascript") ||
        normalized.includes("ecmascript") ||
        normalized.includes("application/x-javascript")
    ) {
        return "script";
    }

    if (normalized.startsWith("image/")) {
        return "image";
    }

    if (
        normalized.startsWith("font/") ||
        normalized.includes("woff") ||
        normalized.includes("truetype") ||
        normalized.includes("opentype")
    ) {
        return "font";
    }

    if (normalized.startsWith("audio/") || normalized.startsWith("video/")) {
        return "media";
    }

    if (
        normalized.includes("json") ||
        normalized.includes("xml") ||
        normalized.includes("graphql") ||
        normalized.includes("protobuf")
    ) {
        return "xhr";
    }

    return fallbackType;
}

function extractUrlsFromCssText(cssText, baseUrl) {
    const results = [];
    const regex = /url\((['"]?)(.*?)\1\)|@import\s+(?:url\((['"]?)(.*?)\3\)|(['"])(.*?)\5)/gi;
    let match;

    while ((match = regex.exec(cssText)) !== null) {
        const candidate = match[2] || match[4] || match[6];

        if (!candidate || candidate.startsWith("data:")) {
            continue;
        }

        const normalized = normalizeUrl(candidate.trim(), baseUrl);

        if (normalized) {
            results.push(normalized);
        }
    }

    return results;
}

async function autoScroll(page, steps) {
    if (steps === 0) {
        return;
    }

    await page.evaluate(async (scrollSteps) => {
        const delay = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));
        let previousHeight = -1;

        for (let index = 0; index < scrollSteps; index += 1) {
            window.scrollTo({ top: document.body.scrollHeight, behavior: "instant" });
            await delay(500);

            const currentHeight = document.body.scrollHeight;

            if (currentHeight === previousHeight) {
                break;
            }

            previousHeight = currentHeight;
        }

        window.scrollTo({ top: 0, behavior: "instant" });
    }, steps);
}

async function collectDomResources(page) {
    return page.evaluate(() => {
        const discovered = [];
        const push = (kind, rawUrl, source) => {
            if (!rawUrl || typeof rawUrl !== "string") {
                return;
            }

            const trimmed = rawUrl.trim();

            if (!trimmed || trimmed.startsWith("data:") || trimmed.startsWith("blob:")) {
                return;
            }

            discovered.push({ type: kind, url: trimmed, source });
        };

        const pushFromSrcset = (kind, value, source) => {
            if (!value) {
                return;
            }

            for (const part of value.split(",")) {
                const candidate = part.trim().split(/\s+/)[0];
                push(kind, candidate, source);
            }
        };

        document.querySelectorAll("img").forEach((node) => {
            push("image", node.currentSrc || node.src, "img");
            pushFromSrcset("image", node.srcset, "img[srcset]");
            push("image", node.getAttribute("data-src"), "img[data-src]");
            pushFromSrcset("image", node.getAttribute("data-srcset"), "img[data-srcset]");
        });

        document.querySelectorAll("picture source, source").forEach((node) => {
            pushFromSrcset("image", node.srcset, "source[srcset]");
            push("media", node.src, "source[src]");
        });

        document.querySelectorAll("video, audio").forEach((node) => {
            push("media", node.currentSrc || node.src, node.tagName.toLowerCase());
            push("image", node.poster, `${node.tagName.toLowerCase()}[poster]`);
        });

        document.querySelectorAll("link").forEach((node) => {
            const rel = (node.rel || "").toLowerCase();

            if (rel.includes("stylesheet")) {
                push("css", node.href, "link[rel=stylesheet]");
            } else if (rel.includes("preload") || rel.includes("prefetch")) {
                const asValue = (node.getAttribute("as") || "").toLowerCase();

                if (asValue === "font") {
                    push("font", node.href, "link[as=font]");
                } else if (asValue === "image") {
                    push("image", node.href, "link[as=image]");
                } else if (asValue === "script") {
                    push("script", node.href, "link[as=script]");
                } else if (asValue === "style") {
                    push("css", node.href, "link[as=style]");
                } else {
                    push("other", node.href, "link[rel=preload|prefetch]");
                }
            }
        });

        document.querySelectorAll("script[src]").forEach((node) => {
            push("script", node.src, "script[src]");
        });

        const cssCandidates = new Set();
        const elements = Array.from(document.querySelectorAll("*"));

        for (const element of elements) {
            const styleAttr = element.getAttribute("style");
            if (styleAttr) {
                cssCandidates.add(styleAttr);
            }

            const computedStyle = window.getComputedStyle(element);
            const backgroundImage = computedStyle.backgroundImage;
            const borderImageSource = computedStyle.borderImageSource;
            const content = computedStyle.content;
            const maskImage = computedStyle.maskImage;

            if (backgroundImage && backgroundImage !== "none") {
                cssCandidates.add(backgroundImage);
            }

            if (borderImageSource && borderImageSource !== "none") {
                cssCandidates.add(borderImageSource);
            }

            if (content && content !== "none") {
                cssCandidates.add(content);
            }

            if (maskImage && maskImage !== "none") {
                cssCandidates.add(maskImage);
            }
        }

        document.querySelectorAll("style").forEach((node) => {
            if (node.textContent) {
                cssCandidates.add(node.textContent);
            }
        });

        for (const styleSheet of Array.from(document.styleSheets)) {
            try {
                if (styleSheet.href) {
                    push("css", styleSheet.href, "document.styleSheets");
                }

                for (const rule of Array.from(styleSheet.cssRules || [])) {
                    if ("href" in rule && rule.href) {
                        push("css", rule.href, "@import");
                    }

                    if (rule.cssText) {
                        cssCandidates.add(rule.cssText);
                    }
                }
            } catch {
                // Cross-origin stylesheets can block cssRules access.
            }
        }

        return {
            baseUrl: document.baseURI,
            resources: discovered,
            cssTexts: Array.from(cssCandidates),
        };
    });
}

function buildSummary(resources) {
    const summary = {
        total: resources.length,
        external: 0,
        same_origin: 0,
        by_type: {},
        external_by_type: {},
    };

    for (const resource of resources) {
        summary.by_type[resource.type] = (summary.by_type[resource.type] ?? 0) + 1;

        if (resource.is_external) {
            summary.external += 1;
            summary.external_by_type[resource.type] = (summary.external_by_type[resource.type] ?? 0) + 1;
        } else {
            summary.same_origin += 1;
        }
    }

    return summary;
}

function printSummary(summary) {
    console.error(`Total resources: ${summary.total}`);
    console.error(`Same-origin: ${summary.same_origin}`);
    console.error(`External: ${summary.external}`);

    const orderedTypes = Object.entries(summary.by_type).sort((left, right) => right[1] - left[1]);

    for (const [type, count] of orderedTypes) {
        const externalCount = summary.external_by_type[type] ?? 0;
        console.error(`- ${type}: ${count} (${externalCount} external)`);
    }
}

async function main() {
    const args = parseArgs(process.argv.slice(2));
    const targetUrl = normalizeUrl(args.url);

    if (!targetUrl) {
        throw new Error(`Invalid URL: ${args.url}`);
    }

    let chromium;

    try {
        ({ chromium } = await import("playwright"));
    } catch (error) {
        if (error && typeof error === "object" && "code" in error && error.code === "ERR_MODULE_NOT_FOUND") {
            throw new Error(
                "Missing dependency: playwright. Run `npm.cmd install` and `npx.cmd playwright install chromium` first."
            );
        }

        throw error;
    }

    const browser = await chromium.launch({ headless: !args.headed });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 2200 },
        userAgent: "Mozilla/5.0 (compatible; ResourceScanner/1.0; +https://example.local)",
    });
    const page = await context.newPage();

    const requestDetails = new Map();
    const resourceMap = new Map();

    const recordResource = (rawUrl, type, metadata = {}) => {
        const normalized = normalizeUrl(rawUrl, metadata.baseUrl ?? targetUrl);

        if (!normalized) {
            return;
        }

        const finalType = VALID_TYPES.has(type) ? type : classifyByExtension(normalized);
        const existing = resourceMap.get(normalized);
        const entry = {
            type: finalType,
            url: normalized,
            domain: getDomain(normalized),
            is_external: isExternalToOrigin(normalized, targetUrl),
        };

        if (!existing) {
            resourceMap.set(normalized, entry);
            return;
        }

        if (existing.type === "other" && finalType !== "other") {
            existing.type = finalType;
        }
    };

    page.on("request", (request) => {
        const requestUrl = normalizeUrl(request.url(), targetUrl);

        if (!requestUrl) {
            return;
        }

        requestDetails.set(request, {
            startedAt: new Date().toISOString(),
            type: classifyFromRequest(request),
        });

        recordResource(requestUrl, classifyFromRequest(request));
    });

    page.on("response", (response) => {
        const request = response.request();
        const requestUrl = normalizeUrl(response.url(), targetUrl);

        if (!requestUrl) {
            return;
        }

        const requestInfo = requestDetails.get(request);
        const responseType = classifyFromContentType(response.headers()["content-type"], requestInfo?.type ?? "other");

        recordResource(requestUrl, responseType);
    });

    page.on("requestfailed", (request) => {
        const requestUrl = normalizeUrl(request.url(), targetUrl);

        if (requestUrl) {
            recordResource(requestUrl, classifyFromRequest(request));
        }
    });

    let navigationResponse;

    try {
        navigationResponse = await page.goto(targetUrl, {
            timeout: args.timeout,
            waitUntil: "domcontentloaded",
        });
    } catch (error) {
        await browser.close();
        throw error;
    }

    try {
        await page.waitForLoadState("networkidle", { timeout: args.timeout });
    } catch {
        // Some pages keep long-lived connections open; continue after timeout.
    }

    await autoScroll(page, args.scrollSteps);

    try {
        await page.waitForLoadState("networkidle", { timeout: args.timeout });
    } catch {
        // Continue and rely on the post-idle delay.
    }

    if (args.postIdle > 0) {
        await page.waitForTimeout(args.postIdle);
    }

    const domSnapshot = await collectDomResources(page);

    for (const item of domSnapshot.resources) {
        recordResource(item.url, item.type, { baseUrl: domSnapshot.baseUrl });
    }

    for (const cssText of domSnapshot.cssTexts) {
        for (const extractedUrl of extractUrlsFromCssText(cssText, domSnapshot.baseUrl)) {
            recordResource(extractedUrl, classifyByExtension(extractedUrl));
        }
    }

    const finalUrl = page.url();
    const redirects = [];
    let redirectRequest = navigationResponse?.request()?.redirectedFrom() ?? null;

    while (redirectRequest) {
        redirects.unshift(redirectRequest.url());
        redirectRequest = redirectRequest.redirectedFrom();
    }

    const resources = Array.from(resourceMap.values()).sort((left, right) => left.url.localeCompare(right.url));
    const result = {
        url: targetUrl,
        final_url: finalUrl,
        redirects,
        resources,
        summary: buildSummary(resources),
    };

    if (args.output) {
        const outputPath = path.resolve(process.cwd(), args.output);
        await fs.writeFile(outputPath, `${JSON.stringify(result, null, 2)}\n`, "utf8");
        console.error(`Saved results to ${outputPath}`);
    }

    printSummary(result.summary);
    console.log(JSON.stringify(result, null, 2));

    await browser.close();
}

main().catch((error) => {
    console.error(error instanceof Error ? error.message : String(error));
    process.exitCode = 1;
});
