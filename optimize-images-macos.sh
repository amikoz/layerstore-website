#!/bin/bash

# Image Optimization Script for macOS (using sips)
# This script compresses all JPEG images to improve website performance

echo "🖼️  LayerStore Image Optimization Script (macOS)"
echo "================================================"

# Find all JPEG images larger than 500KB
echo ""
echo "🔍 Finding large JPEG images (>500KB)..."
find "/Users/afdnrw/Library/Mobile Documents/com~apple~CloudDocs/layerstore/layerstore website" -type f \( -name "*.jpeg" -o -name "*.jpg" \) -size +500k | while read img; do
    original_size=$(stat -f%z "$img")
    original_size_mb=$(echo "scale=2; $original_size / 1048576" | bc)

    echo "📁 Processing: $(basename "$img")"
    echo "   Original size: ${original_size_mb} MB"

    # Create backup
    cp "$img" "${img}.backup"

    # Compress image using sips (macOS built-in)
    sips -s format jpeg -s formatOptions 60 "$img" --out "${img}.tmp" &> /dev/null

    # Replace original
    mv "${img}.tmp" "$img"

    new_size=$(stat -f%z "$img")
    new_size_mb=$(echo "scale=2; $new_size / 1048576" | bc)
    savings=$(echo "scale=1; (1 - $new_size / $original_size) * 100" | bc)

    echo "   New size:     ${new_size_mb} MB"
    echo "   💰 Savings:    ${savings}%"
    echo ""
done

echo "✅ Image optimization complete!"
echo ""
echo "📊 Summary:"
echo "   - Large JPEG images (>500KB) compressed to 60% quality"
echo "   - Backups created with .backup extension"
echo "   - To restore: mv image.jpeg.backup image.jpeg"
