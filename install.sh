#!/bin/bash

# ============================================================================
# SIKEU Payment Laravel Starter - Installation Script
# ============================================================================
# Usage: ./install.sh /path/to/your/laravel/project
# ============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
print_header() {
    echo ""
    echo -e "${BLUE}============================================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}============================================================================${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# Check if target directory provided
if [ -z "$1" ]; then
    print_error "Please provide Laravel project path"
    echo "Usage: $0 /path/to/your/laravel/project"
    exit 1
fi

TARGET_DIR="$1"

# Validate target directory
if [ ! -d "$TARGET_DIR" ]; then
    print_error "Directory not found: $TARGET_DIR"
    exit 1
fi

# Check if it's a Laravel project
if [ ! -f "$TARGET_DIR/artisan" ]; then
    print_error "Not a Laravel project (artisan file not found)"
    exit 1
fi

print_header "SIKEU Payment Laravel Starter - Installation"

print_info "Target directory: $TARGET_DIR"
echo ""

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Installation steps

# 1. Copy config
print_info "Installing config file..."
if [ -f "$SCRIPT_DIR/../config/sikeu.php" ]; then
    cp "$SCRIPT_DIR/../config/sikeu.php" "$TARGET_DIR/config/sikeu.php"
    print_success "Config installed: config/sikeu.php"
else
    print_error "Config file not found in package"
    exit 1
fi

# 2. Copy services
print_info "Installing Payment Service..."
mkdir -p "$TARGET_DIR/app/Services/Payment"
if [ -d "$SCRIPT_DIR/../app/Services/Payment" ]; then
    cp -r "$SCRIPT_DIR/../app/Services/Payment/"* "$TARGET_DIR/app/Services/Payment/"
    print_success "Services installed: app/Services/Payment/"
else
    print_warning "Services directory not found, skipping"
fi

# 3. Copy controllers
print_info "Installing Controllers..."
if [ -f "$SCRIPT_DIR/../app/Http/Controllers/PaymentController.php" ]; then
    cp "$SCRIPT_DIR/../app/Http/Controllers/PaymentController.php" "$TARGET_DIR/app/Http/Controllers/"
    print_success "Controller installed: app/Http/Controllers/PaymentController.php"
else
    print_warning "Controller not found, skipping"
fi

# 4. Copy requests
print_info "Installing Form Requests..."
mkdir -p "$TARGET_DIR/app/Http/Requests"
if [ -f "$SCRIPT_DIR/../app/Http/Requests/CreatePaymentRequest.php" ]; then
    cp "$SCRIPT_DIR/../app/Http/Requests/CreatePaymentRequest.php" "$TARGET_DIR/app/Http/Requests/"
    print_success "Request installed: app/Http/Requests/CreatePaymentRequest.php"
else
    print_warning "Request file not found, skipping"
fi

# 5. Copy exceptions
print_info "Installing Exceptions..."
if [ -f "$SCRIPT_DIR/../app/Exceptions/SikeuPaymentException.php" ]; then
    cp "$SCRIPT_DIR/../app/Exceptions/SikeuPaymentException.php" "$TARGET_DIR/app/Exceptions/"
    print_success "Exception installed: app/Exceptions/SikeuPaymentException.php"
else
    print_warning "Exception file not found, skipping"
fi

# 6. Copy jobs (optional)
print_info "Installing Jobs (optional)..."
mkdir -p "$TARGET_DIR/app/Jobs"
if [ -f "$SCRIPT_DIR/../app/Jobs/CreatePaymentJob.php" ]; then
    cp "$SCRIPT_DIR/../app/Jobs/CreatePaymentJob.php" "$TARGET_DIR/app/Jobs/"
    print_success "Job installed: app/Jobs/CreatePaymentJob.php"
else
    print_warning "Job file not found, skipping"
fi

# 7. Append routes
print_info "Installing routes..."
if [ -f "$SCRIPT_DIR/routes/api.php" ]; then
    echo "" >> "$TARGET_DIR/routes/api.php"
    echo "// SIKEU Payment Routes (auto-generated)" >> "$TARGET_DIR/routes/api.php"
    cat "$SCRIPT_DIR/routes/api.php" >> "$TARGET_DIR/routes/api.php"
    print_success "Routes appended to: routes/api.php"
else
    print_warning "Routes file not found, you need to add routes manually"
fi

# 8. Copy tests (optional)
read -p "Install tests? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_info "Installing tests..."
    mkdir -p "$TARGET_DIR/tests/Feature"
    if [ -d "$SCRIPT_DIR/../tests/Feature" ]; then
        cp "$SCRIPT_DIR/../tests/Feature/"* "$TARGET_DIR/tests/Feature/" 2>/dev/null || true
        print_success "Tests installed: tests/Feature/"
    fi
fi

# 9. Update .env
print_info "Updating .env file..."
if [ -f "$TARGET_DIR/.env" ]; then
    if ! grep -q "SIKEU_API_KEY" "$TARGET_DIR/.env"; then
        echo "" >> "$TARGET_DIR/.env"
        echo "# SIKEU Payment API Configuration (auto-added)" >> "$TARGET_DIR/.env"
        cat "$SCRIPT_DIR/.env.example" | grep -v "^#" | grep -v "^$" >> "$TARGET_DIR/.env"
        print_success ".env updated with SIKEU configuration"
        print_warning "Please update SIKEU_API_KEY and SIKEU_SHARED_SECRET in .env"
    else
        print_info "SIKEU configuration already exists in .env"
    fi
else
    print_warning ".env file not found"
fi

# 10. Clear cache
print_info "Clearing Laravel cache..."
cd "$TARGET_DIR"
php artisan config:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
php artisan cache:clear > /dev/null 2>&1
print_success "Cache cleared"

# 11. Dump autoload
print_info "Refreshing autoloader..."
composer dump-autoload > /dev/null 2>&1
print_success "Autoloader refreshed"

# Summary
print_header "Installation Complete!"

echo "Installed files:"
echo "  ✓ config/sikeu.php"
echo "  ✓ app/Services/Payment/SikeuPaymentService.php"
echo "  ✓ app/Services/Payment/PaymentResponse.php"
echo "  ✓ app/Http/Controllers/PaymentController.php"
echo "  ✓ app/Http/Requests/CreatePaymentRequest.php"
echo "  ✓ app/Exceptions/SikeuPaymentException.php"
echo "  ✓ routes/api.php (updated)"
echo ""

print_warning "Next Steps:"
echo ""
echo "1. Update .env with your SIKEU credentials:"
echo "   - SIKEU_API_KEY=your-api-key"
echo "   - SIKEU_SHARED_SECRET=your-shared-secret"
echo ""
echo "2. Clear config cache:"
echo "   php artisan config:clear"
echo ""
echo "3. Test the integration:"
echo "   php artisan tinker"
echo "   >>> app(\App\Services\Payment\SikeuPaymentService::class)"
echo ""
echo "4. Review routes:"
echo "   php artisan route:list | grep payment"
echo ""
echo "5. Read documentation:"
echo "   - Laravel Integration Guide"
echo "   - API Authentication Guide"
echo ""

print_success "Installation completed successfully!"
echo ""

exit 0
