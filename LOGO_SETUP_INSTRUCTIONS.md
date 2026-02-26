# SkulSoft Logo & Favicon Setup Instructions

## Step 1: Save Your Logo Image

1. Save your logo image (the geometric pattern you showed) as:
   - `public/images/logo.png` (for dark backgrounds)
   - `public/images/logo-light.png` (for light backgrounds)
   - `public/images/icon.png` (app icon - 512x512 recommended)
   - `public/images/favicon.png` (favicon - 256x256 recommended)
   - `public/images/favicon.ico` (convert to .ico format for browser compatibility)

## Recommended Sizes:
- **Logo (full):** 200x60px to 300x90px (transparent background)
- **Icon/Favicon:** 512x512px (square, transparent background)
- **Favicon.ico:** 32x32px, 64x64px (multi-size .ico file)

## Step 2: Quick Setup Script

Run this command after saving your images:

```bash
php setup_skulsoft_logo.php
```

This will:
- Update database configuration to use new logo paths
- Clear all caches
- Verify logo files exist

## Step 3: Manual Image Placement

If you have the image file ready:

```bash
# Copy your logo to the right locations
cp /path/to/your/logo.png public/images/logo.png
cp /path/to/your/logo.png public/images/logo-light.png
cp /path/to/your/icon.png public/images/icon.png
cp /path/to/your/favicon.png public/images/favicon.png

# For favicon.ico, use an online converter or ImageMagick:
convert public/images/favicon.png -define icon:auto-resize=64,48,32,16 public/images/favicon.ico
```

## Image Specifications for Your Geometric Logo

Based on your image:
- **Style:** Geometric hexagonal pattern with triangular elements
- **Colors:** Black on transparent background (recommended)
- **Format:** PNG with transparency
- **Variations Needed:**
  1. Full logo with text (if you want to add "SkulSoft" text)
  2. Icon only (just the geometric symbol)
  3. Light version (white/light color for dark backgrounds)

## Database Configuration

The following configurations will be updated:
- `logo`: Path to main logo
- `logo_light`: Path to light version of logo
- `icon`: Path to app icon
- `favicon`: Path to favicon

All pointing to: `/images/skulsoft-logo.png` (or your preferred name)
