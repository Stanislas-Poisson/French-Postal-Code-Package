#!/usr/bin/env bash
# Opens a pull request that updates data/ when the builder has published a newer dataset.
#
# Usage: scripts/propose-data-update.sh [--dry-run]
#   --dry-run  say what would be done and print the description, without touching git or GitHub.
#
# It runs scripts/update-data.sh (latest release of the builder, checked against SHA256SUMS), and does
# nothing when data/ does not change. Tagging and publishing stay manual: the pull request is reviewed.
# In CI it needs GH_TOKEN (the token of the workflow) and the right to create pull requests.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

DRY_RUN=false
[ "${1:-}" = "--dry-run" ] && DRY_RUN=true

BASE_BRANCH="${BASE_BRANCH:-develop}"
ISSUE="${DATA_UPDATE_ISSUE:-9}"

old_manifest="$(mktemp)"
trap 'rm -f "$old_manifest"' EXIT
cp data/manifest.json "$old_manifest"

output="$(scripts/update-data.sh)"
echo "$output"

version="$(printf '%s\n' "$output" | sed -n 's/^Release \([^ ]*\) of .*/\1/p' | head -n 1)"

if [ -z "$(git status --porcelain data)" ]; then
    echo "data/ already holds the release $version: nothing to propose."
    exit 0
fi

summary() {
    jq -r '"- INSEE COG: \(.cog_vintage)\n- La Poste: \(.laposte_version)\n- Generated: \(.generated_at)\n" + ([.tables | to_entries[] | "- \(.key): \(.value.rows) rows"] | join("\n"))' "$1"
}

body="$(mktemp)"
{
    echo "The builder published the release $version of the dataset. \`scripts/update-data.sh\` replaced the files of \`data/\` with its package archive, after checking it against \`SHA256SUMS\`."
    echo
    echo "## New dataset"
    echo
    summary data/manifest.json
    echo
    echo "## Before"
    echo
    summary "$old_manifest"
    echo
    echo "## What to do"
    echo
    echo "- The CI checks the data against the schemas and the manifest."
    echo "- Choose the next version from the change of layout of the files (the SemVer rules are in the README), merge, then tag and publish as usual: this is not automatic."
    echo
    echo "Part of #$ISSUE"
} >"$body"

if $DRY_RUN; then
    echo "--- dry run: a pull request would be opened with this description ---"
    cat "$body"
    git checkout -- data
    git clean -fdq data
    exit 0
fi

branch="chore/data-$version"

git config user.name "github-actions[bot]"
git config user.email "41898282+github-actions[bot]@users.noreply.github.com"
git checkout -b "$branch"
git add data
git commit -q -m "chore(data): #$ISSUE update data/ to the release $version of the builder"
git push -q origin "$branch"

gh pr create --base "$BASE_BRANCH" --head "$branch" \
    --title "chore(data): #$ISSUE update data/ to the release $version of the builder" \
    --body-file "$body"

# A pull request opened with the token of a workflow does not start the other workflows (GitHub's
# rule against loops), except by dispatch: start the CI by hand so that the required check runs.
gh workflow run ci.yml --ref "$branch"
