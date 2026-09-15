#!/bin/bash
# SAEC Cloud - Backup to private GitHub repo
# Usage: ./backup-to-github.sh <github-repo-url>

set -e

REPO_URL="${1}"
if [ -z "$REPO_URL" ]; then
    echo "Usage: $0 <github-repo-url>"
    echo "Example: $0 git@github.com:SAEC-Ltd/saec-cloud-private.git"
    exit 1
fi

cd /mnt/nas-web/cloud

echo "=== SAEC Cloud Backup to GitHub ==="
echo "Target repo: $REPO_URL"

# Verify we're in a git repo
if [ ! -d .git ]; then
    echo "Error: Not a git repository"
    exit 1
fi

# Check current branch
BRANCH=$(git branch --show-current)
echo "Current branch: $BRANCH"

# Check for uncommitted changes
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "Warning: Uncommitted changes detected"
    git status --short
fi

# Show recent commits
echo "Recent commits:"
git log --oneline -5

# Add remote if not exists
if ! git remote | grep -q "origin"; then
    echo "Adding remote 'origin'..."
    git remote add origin "$REPO_URL"
else
    echo "Remote 'origin' exists, updating URL..."
    git remote set-url origin "$REPO_URL"
fi

# Push to GitHub
echo "Pushing to GitHub..."
git push -u origin "$BRANCH" --force-with-lease

echo "=== Backup complete ==="
echo "Repo: $REPO_URL"
echo "Branch: $BRANCH"