<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Check if user has admin access
if (isClient()) {
    header('Location: ../../clients/dashboard.php');
    exit;
}

// Check if user has permission to manage companies (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to manage companies.');
    header('Location: ../dashboard.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $tax_id = sanitizeInput($_POST['tax_id'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $state = sanitizeInput($_POST['state'] ?? '');
    $zip_code = sanitizeInput($_POST['zip_code'] ?? '');
    $country = sanitizeInput($_POST['country'] ?? 'US');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $website = sanitizeInput($_POST['website'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Company name is required.';
    }
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Check if company name already exists
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM companies WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        $errors[] = 'A company with this name already exists.';
    }
    
    if (empty($errors)) {
        try {
            // Generate a unique company number
            $companyNumber = 'CMP' . strtoupper(substr(md5(uniqid()), 0, 8));
            
            $stmt = $pdo->prepare("INSERT INTO companies (company_name, company_number, enabled, address1, address2, city, state_or_province, zip, primary_phone, contact_name, contact_email, contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$name, $companyNumber, $is_active, $address, '', $city, $state, $zip_code, $phone, '', $email, '']);
            
            if ($result) {
                setFlash('success', 'Company created successfully!');
                header('Location: list.php');
                exit;
            } else {
                $errors[] = 'Failed to create company.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        setFlash('error', implode('<br>', $errors));
    }
}

$csrf_token = generateCSRFToken();

// Set breadcrumbs for this page
setCustomBreadcrumbs([
    ['name' => 'Dashboard', 'url' => getRelativePath('admin/dashboard.php')],
    ['name' => 'Companies', 'url' => getRelativePath('admin/companies/list.php')],
    ['name' => 'Create Company', 'url' => ''], // Current page, not clickable
]);

$title = "Create Company - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Create New Company</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Company Name *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        <div class="form-text">The official name of the company.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="company_number" class="form-label">Company Number</label>
                        <input type="text" class="form-control" id="company_number" name="company_number" value="<?php echo htmlspecialchars($_POST['company_number'] ?? ''); ?>" readonly>
                        <div class="form-text">Automatically generated unique identifier.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">Contact Name</label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name" value="<?php echo htmlspecialchars($_POST['contact_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="zip_code" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($_POST['zip_code'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <select class="form-control" id="country" name="country">
                                    <option value="US" <?php echo (($_POST['country'] ?? 'US') === 'US') ? 'selected' : ''; ?>>United States</option>
                                    <option value="CA" <?php echo (($_POST['country'] ?? 'US') === 'CA') ? 'selected' : ''; ?>>Canada</option>
                                    <option value="UK" <?php echo (($_POST['country'] ?? 'US') === 'UK') ? 'selected' : ''; ?>>United Kingdom</option>
                                    <!-- Add more countries as needed -->
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Primary Phone *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Contact Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_name" class="form-label">Contact Name</label>
                                <input type="text" class="form-control" id="contact_name" name="contact_name" value="<?php echo htmlspecialchars($_POST['contact_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_phone" class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo (isset($_POST['is_active']) || !isset($_POST['name'])) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Company is active</label>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Company</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?>