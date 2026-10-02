#!/usr/bin/env bash
# Replaces the files of data/ with the package archive of a release of the builder.
#
# Usage: scripts/update-data.sh [VERSION]
#   VERSION  a release of https://github.com/Stanislas-Poisson/French-Postal-Code, for example 4.0.0.
#            The latest release by default.
#
# The archive is checked against the SHA256SUMS file of the release before anything is replaced.
set -euo pipefail

REPO="Stanislas-Poisson/French-Postal-Code"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="${1:-latest}"

if [ "$VERSION" = "latest" ]; then
    VERSION="$(curl -fsSIL -o /dev/null -w '%{url_effective}' "https://github.com/$REPO/releases/latest")"
    VERSION="${VERSION##*/}"
fi

ARCHIVE="french-postal-code-$VERSION-package.zip"
BASE="https://github.com/$REPO/releases/download/$VERSION"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

echo "Release $VERSION of $REPO"

curl -fsSL -o "$TMP/$ARCHIVE" "$BASE/$ARCHIVE"
curl -fsSL -o "$TMP/SHA256SUMS" "$BASE/SHA256SUMS"

(cd "$TMP" && grep " $ARCHIVE\$" SHA256SUMS | sha256sum -c -)

unzip -q -o "$TMP/$ARCHIVE" -d "$TMP/data"
rm -f "$ROOT"/data/*.csv "$ROOT"/data/manifest.json
cp "$TMP"/data/* "$ROOT/data/"

echo "data/ now holds the release $VERSION:"
ls -l "$ROOT/data" | awk 'NR>1 {printf "  %10d  %s\n", $5, $9}'
