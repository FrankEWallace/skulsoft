#!/bin/bash

echo "=== SkulSoft Logo Setup Helper ==="
echo ""
echo "Please drag and drop your logo image file here, then press Enter:"
read LOGO_PATH

# Remove quotes if present
LOGO_PATH="${LOGO_PATH//\'/}"
LOGO_PATH="${LOGO_PATH//\"/}"

# Trim whitespace
LOGO_PATH=$(echo "$LOGO_PATH" | xargs)

if [ ! -f "$LOGO_PATH" ]; then
    echo "❌ File not found: $LOGO_PATH"
    exit 1
fi

echo ""
echo "Found: $LOGO_PATH"
echo ""
echo "Copying to SkulSoft locations..."

# Copy to different locations
cp "$LOGO_PATH" public/images/skulsoft-logo.png
echo "✅ Copied to public/images/skulsoft-logo.png"

cp "$LOGO_PATH" public/images/skulsoft-logo-light.png
echo "✅ Copied to public/images/skulsoft-logo-light.png"

cp "$LOGO_PATH" public/images/skulsoft-icon.png
echo "✅ Copied to public/images/skulsoft-icon.png"

cp "$LOGO_PATH" public/images/skulsoft-favicon.png
echo "✅ Copied to public/images/skulsoft-favicon.png"

# Also update the default locations
cp "$LOGO_PATH" public/images/logo.png
cp "$LOGO_PATH" public/images/logo-light.png
cp "$LOGO_PATH" public/images/icon.png
cp "$LOGO_PATH" public/images/favicon.png

echo ""
echo "✅ Logo files copied successfully!"
echo ""
echo "Now running configuration script..."
echo ""

php setup_skulsoft_logo.php
