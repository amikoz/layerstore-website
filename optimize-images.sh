#!/bin/bash

# Image Optimization Script for LayerStore
# This script compresses all JPEG images to improve website performance

echo "🖼️  LayerStore Image Optimization Script"
echo "=========================================="

# Check if ImageMagick is installed
if ! command -v magick &> /dev/null; then
    echo "❌ ImageMagick not found!"
    echo "📦 Install with: brew install imagemagick"
    exit 1
fi

# Find all JPEG images in the project
echo ""
echo "🔍 Finding JPEG images..."
find "/Users/afdnrw/Library/Mobile Documents/com~apple~CloudDocs/layerstore/layerstore website" -type f \( -name "*.jpeg" -o -name "*.jpg" \) -print0 | while IFS= read -r -d '' img; do
    original_size=$(stat -f%z "$img" 2>/dev/null || stat -c%s "$img" 2>/dev/null)
    original_size_mb=$(echo "scale=2; $original_size / 1048576" | bc)

    echo "📁 Processing: $(basename "$img")"
    echo "   Original size: ${original_size_mb} MB"

    # Create backup
    cp "$img" "${img}.backup"

    # Compress image with 85% quality (good balance between quality and size)
    magick "$img" -quality 85 -strip "${img}.tmp"

    # Replace original
    mv "${img}.tmp" "$img"

    new_size=$(stat -f%z "$img" 2>/dev/null || stat -c%s "$img" 2>/dev/null)
    new_size_mb=$(echo "scale=2; $new_size / 1048576" | bc)
    savings=$(echo "scale=1; (1 - $new_size / $original_size) * 100" | bc)

    echo "   New size:     ${new_size_mb} MB"
    echo "   💰 Savings:    ${savings}%"
    echo ""
done

echo "✅ Image optimization complete!"
echo ""
echo "📊 Summary:"
echo "   - All JPEG images compressed to 85% quality"
echo "   - Backups created with .backup extension"
echo "   - To restore: mv image.jpeg.backup image.jpeg"
