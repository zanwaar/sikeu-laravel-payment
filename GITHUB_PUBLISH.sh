#!/bin/bash

# SIKEU Laravel Payment - GitHub Publishing Script
# Run this script to push package to GitHub

echo "======================================"
echo "SIKEU Laravel Payment - GitHub Setup"
echo "======================================"
echo ""

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found. Please run this script from integration/php-laravel directory"
    exit 1
fi

echo "📦 Package: sikeu/laravel-payment"
echo ""

# Ask for GitHub username
echo "Please enter your GitHub username:"
read github_username

if [ -z "$github_username" ]; then
    echo "❌ Error: GitHub username cannot be empty"
    exit 1
fi

echo ""
echo "📝 Instructions:"
echo "1. Go to https://github.com/new"
echo "2. Repository name: sikeu-laravel-payment"
echo "3. Description: Laravel package untuk integrasi SIKEU Payment Gateway - supporting BRI, BNI, BSI with Virtual Account & QRIS"
echo "4. Set as: Public"
echo "5. DO NOT initialize with README, .gitignore, or LICENSE"
echo "6. Click 'Create repository'"
echo ""
echo "Press ENTER after you've created the repository on GitHub..."
read

# Add remote
echo ""
echo "🔗 Adding GitHub remote..."
git remote remove origin 2>/dev/null
git remote add origin "https://github.com/$github_username/sikeu-laravel-payment.git"

# Rename branch to main
echo "🔀 Renaming branch to main..."
git branch -M main

# Push to GitHub
echo "📤 Pushing to GitHub..."
git push -u origin main

# Create and push tag
echo ""
echo "🏷️  Creating version tag v1.0.0..."
git tag -a v1.0.0 -m "Release version 1.0.0 - Initial release with BRI, BNI, BSI support"
git push origin v1.0.0

echo ""
echo "✅ Success! Package has been published to GitHub"
echo ""
echo "📍 Repository URL: https://github.com/$github_username/sikeu-laravel-payment"
echo ""
echo "Next steps:"
echo "1. Visit your repository on GitHub"
echo "2. Add topics/tags: laravel, payment-gateway, indonesia, virtual-account, qris"
echo "3. (Optional) Publish to Packagist: https://packagist.org/packages/submit"
echo ""
echo "Installation command for other projects:"
echo "composer require sikeu/laravel-payment"
echo ""
