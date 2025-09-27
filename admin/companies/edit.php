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

// Get company ID from query parameter
$companyId = $_GET['id'] ?? '';
if (empty($companyId)) {
    setFlash('error', 'No company ID provided.');
    header('Location: list.php');
    exit;
}

// Get company data
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$companyId]);
$company = $stmt->fetch();

if (!$company) {
    setFlash('error', 'Company not found.');
    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . urlencode($companyId));
        exit;
    }
    
    $name = sanitizeInput($_POST['name'] ?? '');
    $contact_name = sanitizeInput($_POST['contact_name'] ?? '');
    $address1 = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $state = sanitizeInput($_POST['state'] ?? '');
    $zip_code = sanitizeInput($_POST['zip_code'] ?? '');
    $country = sanitizeInput($_POST['country'] ?? 'US');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $contact_email = sanitizeInput($_POST['contact_email'] ?? '');
    $contact_phone = sanitizeInput($_POST['contact_phone'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Company name is required.';
    }
    if (empty($phone)) {
        $errors[] = 'Primary phone is required.';
    }
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!empty($contact_email) && !validateEmail($contact_email)) {
        $errors[] = 'Please enter a valid contact email address.';
    }
    
    // Check if company name already exists (excluding current company)
    $stmt = $pdo->prepare("SELECT id FROM companies WHERE company_name = ? AND id != ?");
    $stmt->execute([$name, $companyId]);
    if ($stmt->fetch()) {
        $errors[] = 'A company with this name already exists.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE companies SET company_name = ?, address1 = ?, city = ?, state_or_province = ?, zip = ?, primary_phone = ?, contact_name = ?, contact_email = ?, contact_phone = ?, enabled = ? WHERE id = ?");
            $result = $stmt->execute([$name, $address1, $city, $state, $zip_code, $phone, $contact_name, $contact_email, $contact_phone, $is_active, $companyId]);
            
            if ($result) {
                setFlash('success', 'Company updated successfully!');
                header('Location: list.php');
                exit;
            } else {
                $errors[] = 'Failed to update company.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        setFlash('error', implode('<br>', $errors));
    }
    
    // Refresh company data in case of validation errors
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$companyId]);
    $company = $stmt->fetch();
}

// If form was submitted with errors, use POST data; otherwise use DB data
$name = htmlspecialchars($_POST['name'] ?? $company['company_name'] ?? '');
$contact_name = htmlspecialchars($_POST['contact_name'] ?? $company['contact_name'] ?? '');
$address1 = htmlspecialchars($_POST['address'] ?? $company['address1'] ?? '');
$city = htmlspecialchars($_POST['city'] ?? $company['city'] ?? '');
$state = htmlspecialchars($_POST['state'] ?? $company['state_or_province'] ?? '');
$zip_code = htmlspecialchars($_POST['zip_code'] ?? $company['zip'] ?? '');
$country = htmlspecialchars($_POST['country'] ?? $company['country'] ?? 'US');
$phone = htmlspecialchars($_POST['phone'] ?? $company['primary_phone'] ?? '');
$email = htmlspecialchars($_POST['email'] ?? $company['contact_email'] ?? '');
$contact_email = htmlspecialchars($_POST['contact_email'] ?? $company['contact_email'] ?? '');
$contact_phone = htmlspecialchars($_POST['contact_phone'] ?? $company['contact_phone'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : (int)$company['enabled'];

$csrf_token = generateCSRFToken();

// Set breadcrumbs for this page
setCustomBreadcrumbs([
    ['name' => 'Dashboard', 'url' => getRelativePath('admin/dashboard.php')],
    ['name' => 'Companies', 'url' => getRelativePath('admin/companies/list.php')],
    ['name' => 'Edit Company', 'url' => ''], // Current page, not clickable
]);

$title = "Edit Company - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Edit Company: <?php echo htmlspecialchars($company['company_name']); ?></h3>
                <a href="view.php?id=<?php echo urlencode($company['id']); ?>" class="btn btn-sm btn-info">View Details</a>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Company Name *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" required>
                        <div class="form-text">The official name of the company.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="company_number" class="form-label">Company Number</label>
                        <input type="text" class="form-control" id="company_number" name="company_number" value="<?php echo htmlspecialchars($company['company_number']); ?>" readonly>
                        <div class="form-text">Unique identifier for the company.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">Contact Name</label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name" value="<?php echo htmlspecialchars($_POST['contact_name'] ?? $company['contact_name']); ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo $address; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="zip_code" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo $zip_code; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo $city; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" value="<?php echo $state; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <select class="form-control" id="country" name="country">
                                    <option value="US" <?php echo ($country === 'US') ? 'selected' : ''; ?>>United States</option>
                                    <option value="CA" <?php echo ($country === 'CA') ? 'selected' : ''; ?>>Canada</option>
                                    <option value="UK" <?php echo ($country === 'UK') ? 'selected' : ''; ?>>United Kingdom</option>
                                    <!-- Add more countries as needed -->
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $phone; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="website" name="website" value="<?php echo $website; ?>">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo $is_active ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Company is active</label>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Company</button>
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