# rohrfrisch.at

rohrfrisch.at

## Resource scanner

This repository now includes a Playwright-based webpage resource scanner at `scripts/scan.js`.

### What it captures

- Static HTML resources such as `img`, `script`, `link rel="stylesheet"`, media, and preload hints
- Runtime network traffic from JavaScript-heavy pages, including `fetch` and XHR requests
- CSS-discovered assets such as `background-image`, `@import`, and font/image URLs referenced in styles
- Lazy-loaded resources triggered after load by scrolling and waiting for network idle

### Install dependencies

```powershell
npm.cmd install
npx.cmd playwright install chromium
```

### Usage

```powershell
node scripts/scan.js https://example.com
node scripts/scan.js https://example.com --output scan-result.json
node scripts/scan.js https://example.com --timeout 45000 --post-idle 4000 --scroll-steps 12
```

You can also use the package script:

```powershell
npm.cmd run scan:resources -- https://example.com
```

### Output shape

The scanner prints structured JSON to stdout:

```json
{
  "url": "https://example.com/",
  "final_url": "https://example.com/",
  "redirects": [],
  "resources": [
    {
      "type": "script",
      "url": "https://cdn.example.com/app.js",
      "domain": "cdn.example.com",
      "is_external": true
    }
  ],
  "summary": {
    "total": 1,
    "external": 1,
    "same_origin": 0,
    "by_type": {
      "script": 1
    },
    "external_by_type": {
      "script": 1
    }
  }
}
```
