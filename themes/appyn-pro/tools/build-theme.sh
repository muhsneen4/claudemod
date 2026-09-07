#!/usr/bin/env bash
#
# Package the Appyn Pro child theme as an installable WordPress zip.
#
set -euo pipefail

theme_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
theme_name="$(basename "$theme_dir")"
out_dir="$theme_dir/dist"
zip_file="$out_dir/$theme_name.zip"

rm -rf "$out_dir"
mkdir -p "$out_dir"

cd "$theme_dir/.."

zip -r -q "$zip_file" "$theme_name" \
	-x "$theme_name/dist/*" \
	-x "$theme_name/tools/*" \
	-x "*/.DS_Store" \
	-x "*/.git/*"

echo "Built $zip_file"
