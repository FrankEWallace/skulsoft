#!/bin/bash

echo "=== Updating Academic namespace references ==="
echo ""

# Find all PHP files with old Academic namespace and update them
find app -type f -name "*.php" -print0 | while IFS= read -r -d '' file; do
    if grep -q "use App\\\\Models\\\\Academic\\\\" "$file"; then
        echo "Updating: $file"
        # Use sed to replace the namespace
        sed -i.bak 's/use App\\Models\\Academic\\/use App\\Domain\\Academic\\Models\\/g' "$file"
        # Remove backup file
        rm "${file}.bak"
    fi
done

echo ""
echo "✅ Update complete!"
echo ""
echo "Running composer dump-autoload..."
composer dump-autoload --optimize

echo ""
echo "Clearing caches..."
php artisan optimize:clear

echo ""
echo "✅ All done! You can now login successfully."
