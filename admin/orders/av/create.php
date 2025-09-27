<?php
require_once '../../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../../auth/login.php');
    exit;
}

// Check if user has permission to create orders (superadmin, accounts, or client)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'accounts'];
$isClient = in_array($userRole, ['client', 'client_admin']);

if (!$isClient && !in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to create orders.');
    header('Location: ../../../admin/dashboard.php');
    exit;
}

// Check tour_id from URL parameter
$tourId = $_GET['tour_id'] ?? null;
$companyId = $_GET['company_id'] ?? null;
$userId = $_GET['user_id'] ?? null;  // Added to handle user from URL

// Variables for form data
$companyInfo = [];
$tourInfo = [];
$userInfo = [];

// If user is a client, use their company
if ($isClient) {
    // Get user's company from session or database
    global $pdo;
    $userStmt = $pdo->prepare("SELECT u.*, c.* FROM users u LEFT JOIN companies c ON u.company_id = c.id WHERE u.id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $userCompany = $userStmt->fetch();
    
    if (!$userCompany) {
        setFlash('error', 'User information not found.');
        header('Location: ../../../clients/dashboard.php');
        exit;
    }
    
    // Check if company is enabled
    if (!$userCompany['enabled']) {
        setFlash('error', 'Company is not allowed to create new orders.');
        header('Location: ../../../clients/dashboard.php');
        exit;
    }
    
    // Check if user is active
    if (!$userCompany['is_active']) {
        setFlash('error', 'User is not allowed to create new orders.');
        header('Location: ../../../clients/dashboard.php');
        exit;
    }
    
    $companyInfo = $userCompany;
    $companyId = $userCompany['id'];
} else {
    // For admin users, we may have company_id in the URL
    if ($companyId) {
        $companyStmt = $pdo->prepare("SELECT * FROM companies WHERE id = ? AND enabled = 1");
        $companyStmt->execute([$companyId]);
        $companyInfo = $companyStmt->fetch();
        
        if (!$companyInfo) {
            setFlash('error', 'Company not found or disabled.');
            header('Location: ../../../admin/dashboard.php');
            exit;
        }
        
        // Check if user is provided and is active for this company
        if ($userId) {
            $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND company_id = ? AND is_active = 1");
            $userStmt->execute([$userId, $companyId]);
            $userInfo = $userStmt->fetch();
            
            if (!$userInfo) {
                setFlash('error', 'User not found or inactive for this company.');
                header('Location: ../../../admin/dashboard.php');
                exit;
            }
        }
    }
    
    // Load tour info if tour_id is provided
    if ($tourId) {
        $tourStmt = $pdo->prepare("SELECT * FROM tours WHERE id = ? AND active = 1");
        $tourStmt->execute([$tourId]);
        $tourInfo = $tourStmt->fetch();
        
        if (!$tourInfo) {
            setFlash('error', 'Tour not found or not active.');
            header('Location: ../../../admin/dashboard.php');
            exit;
        }
    }
}

// Settings variables
$settings = [
    'hide_toast' => false,
    'invoice_cost' => 0.00,
    'audio_rush_order' => 125.00,
    'video_rush_order' => 150.00
];

$title = "Audio/Video Order Form - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Audio/Video Order Form</h3>
                <div>
                    <?php if (!$isClient): ?>
                        <button type="button" class="btn btn-info me-2" onclick="loadCompanyInfo()">Load Company</button>
                        <button type="button" class="btn btn-info me-2" onclick="loadUserInfo()">Load User</button>
                        <?php if (!$tourId): ?>
                            <button type="button" class="btn btn-info me-2" onclick="loadTourInfo()">Load Tour</button>
                        <?php endif; ?>
                    <?php endif; ?>
                    <button type="submit" form="av-order-form" class="btn btn-primary">Save Order</button>
                </div>
            </div>
            <div class="card-body">
                <form id="av-order-form" method="POST">
                    <!-- Hidden fields -->
                    <div style="display: none;">
                        <input type="hidden" name="company_id" id="company_id" value="<?php echo $companyId ?? ($_SESSION['company_id'] ?? ''); ?>">
                        <input type="hidden" name="user_id" id="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                        <input type="hidden" name="account_rep_id" id="account_rep_id" value="<?php echo htmlspecialchars($userInfo['account_rep_id'] ?? ''); ?>">
                        <input type="hidden" name="tour_id" id="tour_id" value="<?php echo $tourId ?? ''; ?>">
                    </div>

                    <?php if (!$isClient && !$companyId): ?>
                    <!-- Company selection for admin users -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="company_selector" class="form-label">Select Company *</label>
                            <select class="form-control" id="company_selector" name="company_selector" required onchange="onCompanyChange()">
                                <option value="">Choose a company...</option>
                                <?php
                                global $pdo;
                                $companies = $pdo->query("SELECT id, company_name FROM companies WHERE enabled = 1 ORDER BY company_name")->fetchAll();
                                foreach ($companies as $company):
                                ?>
                                    <option value="<?php echo $company['id']; ?>" <?php echo $companyId == $company['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($company['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="user_selector" class="form-label">Select User *</label>
                            <select class="form-control" id="user_selector" name="user_selector" required onchange="onUserChange()" <?php echo !$companyId ? 'disabled' : ''; ?>>
                                <option value="">Choose a user...</option>
                                <?php if ($companyId): 
                                $users = $pdo->query("SELECT id, name, email FROM users WHERE company_id = $companyId AND is_active = 1 ORDER BY name")->fetchAll();
                                foreach ($users as $user):
                                ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $userId == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                    </option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <?php if (!$tourId): ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="tour_input" class="form-label">Select Tour *</label>
                            <input type="text" class="form-control" id="tour_input" name="tour_input" placeholder="Start typing to search for a tour..." autocomplete="off">
                            <div id="tour_suggestions" class="list-group" style="display: none; position: absolute; z-index: 1000; width: 100%;"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($tourId): ?>
                    <div class="alert alert-info">
                        <strong>Tour Information Loaded:</strong> 
                        <span id="loaded_tour_name"><?php echo htmlspecialchars($tourInfo['headliner'] ?? ''); ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- Promoter Information -->
                    <div class="row mb-4">
                        <h4>Promoter Information</h4>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="promoter_name" class="form-label">Promoter Full Name</label>
                                <input type="text" class="form-control" id="promoter_name" name="promoter_name" value="<?php echo htmlspecialchars($userInfo['name'] ?? ''); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="promoter_phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="promoter_phone" name="promoter_phone" value="<?php echo htmlspecialchars($userInfo['phone'] ?? ''); ?>" onchange="formatPhoneNumber('promoter_phone')">
                                <div class="form-text">Format: (###) ###-####</div>
                            </div>
                            <div class="mb-3">
                                <label for="promoter_phone_ext" class="form-label">Phone Extension</label>
                                <input type="text" class="form-control" id="promoter_phone_ext" name="promoter_phone_ext" value="<?php echo htmlspecialchars($userInfo['phone_ext'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="promoter_email" class="form-label">Promoter Email</label>
                                <input type="email" class="form-control" id="promoter_email" name="promoter_email" value="<?php echo htmlspecialchars($userInfo['email'] ?? ''); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="promoter_company" class="form-label">Promoter Company Name</label>
                                <input type="text" class="form-control" id="promoter_company" name="promoter_company" value="<?php echo htmlspecialchars($companyInfo['company_name'] ?? ''); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Order Details -->
                    <div class="row mb-4">
                        <h4>Order Details</h4>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order_details_purchase_order_number" class="form-label">Purchase Order #</label>
                                <input type="text" class="form-control" id="order_details_purchase_order_number" name="order_details_purchase_order_number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Today's Date</label>
                                <span id="current_date" class="form-control-plaintext"><?php echo date('Y-m-d'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="order_details_audio_order" name="order_details_audio_order" onchange="toggleRushOrder('audio', this.checked)">
                                <label class="form-check-label" for="order_details_audio_order">Audio Order</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="order_details_audio_order_rush" name="order_details_audio_order_rush" disabled onchange="updateInvoiceCost()">
                                <label class="form-check-label text-danger" for="order_details_audio_order_rush">
                                    RUSH ORDER ($<span id="audio_rush_amount"><?php echo $settings['audio_rush_order']; ?></span>)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="order_details_video_order" name="order_details_video_order" onchange="toggleRushOrder('video', this.checked)">
                                <label class="form-check-label" for="order_details_video_order">Video Order</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="order_details_video_order_rush" name="order_details_video_order_rush" disabled onchange="updateInvoiceCost()">
                                <label class="form-check-label text-danger" for="order_details_video_order_rush">
                                    RUSH ORDER ($<span id="video_rush_amount"><?php echo $settings['video_rush_order']; ?></span>)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order_details_audio_received_by" class="form-label">Audio must be received by</label>
                                <input type="date" class="form-control" id="order_details_audio_received_by" name="order_details_audio_received_by">
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="order_details_audio_order_requires_approval" name="order_details_audio_order_requires_approval">
                                <label class="form-check-label" for="order_details_audio_order_requires_approval">Do not ship audio spots without approval</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order_details_video_order_received_by" class="form-label">Video must be received by</label>
                                <input type="date" class="form-control" id="order_details_video_order_received_by" name="order_details_video_order_received_by">
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="order_details_video_order_requires_approval" name="order_details_video_order_requires_approval">
                                <label class="form-check-label" for="order_details_video_order_requires_approval">Do not ship video spots without approval</label>
                            </div>
                        </div>
                    </div>

                    <!-- Event Information -->
                    <div class="row mb-4">
                        <h4>Event Information</h4>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_info_headliner" class="form-label">Tour/Event/Headliner *</label>
                                <textarea class="form-control" id="event_info_headliner" name="event_info_headliner" rows="2" required><?php echo htmlspecialchars($tourInfo['headliner'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="event_info_support" class="form-label">Support</label>
                                <textarea class="form-control" id="event_info_support" name="event_info_support" rows="2"><?php echo htmlspecialchars($tourInfo['support'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="event_info_venue" class="form-label">Venue *</label>
                                <input type="text" class="form-control" id="event_info_venue" name="event_info_venue" placeholder="Start typing to search venues..." autocomplete="off">
                                <div id="venue_suggestions" class="list-group" style="display: none; position: absolute; z-index: 1000; width: 100%;"></div>
                                <div class="form-text">NOTE: we will only say market if it's written in the Venue Section</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="event_info_mention_the" name="event_info_mention_the">
                                    <label class="form-check-label" for="event_info_mention_the">Mention "The" in Venue Name?</label>
                                </div>
                                <label for="event_info_market" class="form-label">City/Market *</label>
                                <input type="text" class="form-control" id="event_info_market" name="event_info_market" placeholder="Start typing to search markets..." autocomplete="off">
                                <div id="market_suggestions" class="list-group" style="display: none; position: absolute; z-index: 1000; width: 100%;"></div>
                            </div>
                            <div class="mb-3">
                                <label for="event_info_date" class="form-label">Date *</label>
                                <input type="date" class="form-control" id="event_info_date" name="event_info_date">
                            </div>
                            <div class="mb-3">
                                <label for="event_info_times" class="form-label">Time/s</label>
                                <input type="text" class="form-control" id="event_info_times" name="event_info_times" placeholder="e.g., 8pm">
                            </div>
                            <div class="form-text mb-2">NOTE: We only mention Fri/Sat in spots unless otherwise requested.</div>
                        </div>
                    </div>

                    <!-- Additional Dates Repeater -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <button type="button" class="btn btn-sm btn-outline-primary mb-2" onclick="addAdditionalDate()">Add Additional Dates</button>
                            <div id="additional_dates_container"></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="event_info_custom_dates_string" class="form-label">Custom string of dates</label>
                            <textarea class="form-control" id="event_info_custom_dates_string" name="event_info_custom_dates_string" rows="2" placeholder="Example: Two shows every Friday in July, at 5pm and 8pm"></textarea>
                            <div class="form-text">Type residency or other custom dates</div>
                        </div>
                    </div>

                    <!-- Local Copy -->
                    <div class="row mb-4">
                        <h4>Local Copy</h4>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="local_copy_intro_line" class="form-label">Intro Line</label>
                                <textarea class="form-control" id="local_copy_intro_line" name="local_copy_intro_line" rows="2"><?php echo htmlspecialchars($tourInfo['intro_line'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="local_copy_produced_by" class="form-label">Produced By</label>
                                <textarea class="form-control" id="local_copy_produced_by" name="local_copy_produced_by" rows="2"><?php echo htmlspecialchars($tourInfo['produced_by'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="local_copy_outro_line" class="form-label">Outro Line</label>
                                <textarea class="form-control" id="local_copy_outro_line" name="local_copy_outro_line" rows="2"><?php echo htmlspecialchars($tourInfo['outro_line'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-text mb-2">NOTE: Please type out exactly how you want your station mentioned. If you include a "." (Point) in the station ID, the word "Point" will be said.</div>
                        </div>
                    </div>

                    <!-- Ticket Information -->
                    <div class="row mb-4">
                        <h4>Ticket Information</h4>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ticket_info_on_sale_day_time" class="form-label">On Sale Day/Time</label>
                                <input type="text" class="form-control" id="ticket_info_on_sale_day_time" name="ticket_info_on_sale_day_time" placeholder="Only list date if required">
                                <div class="form-text">Only list date if required. Otherwise, only date and time will be mentioned in pre-sale spot.</div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="ticket_info_mention_reserved_seats" name="ticket_info_mention_reserved_seats">
                                <label class="form-check-label" for="ticket_info_mention_reserved_seats">Mention Reserved Seats</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ticket_info_locations" class="form-label">Ticket Information/Locations</label>
                                <textarea class="form-control" id="ticket_info_locations" name="ticket_info_locations" rows="3"></textarea>
                            </div>
                            <div class="form-text">NOTE: :30, :15, :10 spots have limited time for ticket information</div>
                        </div>
                    </div>

                    <!-- Spot Information -->
                    <div class="row mb-4">
                        <h4>Spot Information</h4>
                        <div class="form-text mb-3">NOTE: :10 and :15 spots may not be available for all tours</div>
                        <div class="form-text mb-3">At least one spot must be selected</div>

                        <!-- AUDIO -->
                        <h5 class="mb-3">AUDIO</h5>
                        <div class="mb-3">
                            <strong>Presale</strong>
                            <div class="d-flex justify-content-start gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_presale_60" name="spot_info_audio_presale_60">
                                    <label class="form-check-label" for="spot_info_audio_presale_60">60</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_presale_30" name="spot_info_audio_presale_30">
                                    <label class="form-check-label" for="spot_info_audio_presale_30">30</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_presale_15" name="spot_info_audio_presale_15">
                                    <label class="form-check-label" for="spot_info_audio_presale_15">15</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_presale_10" name="spot_info_audio_presale_10">
                                    <label class="form-check-label" for="spot_info_audio_presale_10">10</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>On Sale Now</strong>
                            <div class="d-flex justify-content-start gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_on_sale_now_60" name="spot_info_audio_on_sale_now_60">
                                    <label class="form-check-label" for="spot_info_audio_on_sale_now_60">60</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_on_sale_now_30" name="spot_info_audio_on_sale_now_30">
                                    <label class="form-check-label" for="spot_info_audio_on_sale_now_30">30</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_on_sale_now_15" name="spot_info_audio_on_sale_now_15">
                                    <label class="form-check-label" for="spot_info_audio_on_sale_now_15">15</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_on_sale_now_10" name="spot_info_audio_on_sale_now_10">
                                    <label class="form-check-label" for="spot_info_audio_on_sale_now_10">10</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>Week Of</strong>
                            <div class="d-flex justify-content-start gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_week_of_60" name="spot_info_audio_week_of_60">
                                    <label class="form-check-label" for="spot_info_audio_week_of_60">60</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_week_of_30" name="spot_info_audio_week_of_30">
                                    <label class="form-check-label" for="spot_info_audio_week_of_30">30</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_week_of_15" name="spot_info_audio_week_of_15">
                                    <label class="form-check-label" for="spot_info_audio_week_of_15">15</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_week_of_10" name="spot_info_audio_week_of_10">
                                    <label class="form-check-label" for="spot_info_audio_week_of_10">10</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>Day Prior</strong>
                            <div class="d-flex justify-content-start gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_day_prior_60" name="spot_info_audio_day_prior_60">
                                    <label class="form-check-label" for="spot_info_audio_day_prior_60">60</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_day_prior_30" name="spot_info_audio_day_prior_30">
                                    <label class="form-check-label" for="spot_info_audio_day_prior_30">30</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_day_prior_15" name="spot_info_audio_day_prior_15">
                                    <label class="form-check-label" for="spot_info_audio_day_prior_15">15</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_day_prior_10" name="spot_info_audio_day_prior_10">
                                    <label class="form-check-label" for="spot_info_audio_day_prior_10">10</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>Day Of</strong>
                            <div class="d-flex justify-content-start gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_day_of_60" name="spot_info_audio_day_of_60">
                                    <label class="form-check-label" for="spot_info_audio_day_of_60">60</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_day_of_30" name="spot_info_audio_day_of_30">
                                    <label class="form-check-label" for="spot_info_audio_day_of_30">30</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_day_of_15" name="spot_info_audio_day_of_15">
                                    <label class="form-check-label" for="spot_info_audio_day_of_15">15</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_audio_day_of_10" name="spot_info_audio_day_of_10">
                                    <label class="form-check-label" for="spot_info_audio_day_of_10">10</label>
                                </div>
                            </div>
                        </div>

                        <!-- VIDEO -->
                        <h5 class="mb-3">VIDEO</h5>
                        <div class="mb-3">
                            <strong>Presale</strong>
                            <div class="d-flex justify-content-start gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_presale_30" name="spot_info_video_presale_30">
                                    <label class="form-check-label" for="spot_info_video_presale_30">30</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_presale_15" name="spot_info_video_presale_15">
                                    <label class="form-check-label" for="spot_info_video_presale_15">15</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_presale_10" name="spot_info_video_presale_10">
                                    <label class="form-check-label" for="spot_info_video_presale_10">10</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>On Sale Now</strong>
                            <div class="d-flex justify-content-start gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_on_sale_now_30" name="spot_info_video_on_sale_now_30">
                                    <label class="form-check-label" for="spot_info_video_on_sale_now_30">30</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_on_sale_now_15" name="spot_info_video_on_sale_now_15">
                                    <label class="form-check-label" for="spot_info_video_on_sale_now_15">15</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_on_sale_now_10" name="spot_info_video_on_sale_now_10">
                                    <label class="form-check-label" for="spot_info_video_on_sale_now_10">10</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>Week Of</strong>
                            <div class="d-flex justify-content-start gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_week_of_30" name="spot_info_video_week_of_30">
                                    <label class="form-check-label" for="spot_info_video_week_of_30">30</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_week_of_15" name="spot_info_video_week_of_15">
                                    <label class="form-check-label" for="spot_info_video_week_of_15">15</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_week_of_10" name="spot_info_video_week_of_10">
                                    <label class="form-check-label" for="spot_info_video_week_of_10">10</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>Day Prior</strong>
                            <div class="d-flex justify-content-start gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_day_prior_30" name="spot_info_video_day_prior_30">
                                    <label class="form-check-label" for="spot_info_video_day_prior_30">30</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_day_prior_15" name="spot_info_video_day_prior_15">
                                    <label class="form-check-label" for="spot_info_video_day_prior_15">15</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_day_prior_10" name="spot_info_video_day_prior_10">
                                    <label class="form-check-label" for="spot_info_video_day_prior_10">10</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>Day Of</strong>
                            <div class="d-flex justify-content-start gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_day_of_30" name="spot_info_video_day_of_30">
                                    <label class="form-check-label" for="spot_info_video_day_of_30">30</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_day_of_15" name="spot_info_video_day_of_15">
                                    <label class="form-check-label" for="spot_info_video_day_of_15">15</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="spot_info_video_day_of_10" name="spot_info_video_day_of_10">
                                    <label class="form-check-label" for="spot_info_video_day_of_10">10</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="spot_info_custom_isci" class="form-label">Custom ISCI code, if required (must be limited to 13 characters)</label>
                            <input type="text" class="form-control" id="spot_info_custom_isci" name="spot_info_custom_isci" maxlength="13">
                        </div>
                    </div>

                    <!-- Special Instructions -->
                    <div class="row mb-4">
                        <h4>Special Instructions</h4>
                        <div class="form-text mb-3">NOTE: Pronunciations are your responsibility! If there is a doubt, please provide guidance.</div>
                        <div class="col-12">
                            <textarea class="form-control" id="special_instructions" name="special_instructions" rows="4" placeholder="Enter special instructions here..."></textarea>
                        </div>
                    </div>

                    <!-- Invoice Cost Display -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5>Invoice Cost: $<span id="invoice_cost_display"><?php echo number_format($settings['invoice_cost'], 2); ?></span></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Format phone number to (###) ###-####
    function formatPhoneNumber(fieldId) {
        let phoneField = document.getElementById(fieldId);
        let value = phoneField.value.replace(/\D/g, '');  // Remove all non-digit characters
        
        if (value.length >= 6) {
            value = '(' + value.substring(0, 3) + ') ' + value.substring(3, 6) + '-' + value.substring(6, 10);
        } else if (value.length >= 3) {
            value = '(' + value.substring(0, 3) + ') ' + value.substring(3);
        }
        
        phoneField.value = value;
    }

    // Toggle rush order checkboxes based on main order selection
    function toggleRushOrder(type, isChecked) {
        let rushCheckbox = document.getElementById(`order_details_${type}_order_rush`);
        rushCheckbox.disabled = !isChecked;
        
        if (!isChecked) {
            rushCheckbox.checked = false;
        }
        
        updateInvoiceCost();
    }

    // Update invoice cost based on selections
    function updateInvoiceCost() {
        let invoiceCost = 0;
        let audioRush = <?php echo $settings['audio_rush_order']; ?>;
        let videoRush = <?php echo $settings['video_rush_order']; ?>;
        
        // Add rush order costs if selected
        if (document.getElementById('order_details_audio_order').checked && 
            document.getElementById('order_details_audio_order_rush').checked) {
            invoiceCost += audioRush;
        }
        
        if (document.getElementById('order_details_video_order').checked && 
            document.getElementById('order_details_video_order_rush').checked) {
            invoiceCost += videoRush;
        }
        
        // Update display
        document.getElementById('invoice_cost_display').textContent = invoiceCost.toFixed(2);
    }

    // Function to add additional dates (repeater functionality)
    function addAdditionalDate() {
        const container = document.getElementById('additional_dates_container');
        const dateCount = container.children.length + 1;
        
        const dateDiv = document.createElement('div');
        dateDiv.className = 'additional-date mb-2';
        dateDiv.innerHTML = `
            <div class="row">
                <div class="col-md-5">
                    <input type="date" class="form-control" name="additional_dates[${dateCount}][date]" placeholder="Date">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="additional_dates[${dateCount}][time]" placeholder="Time">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAdditionalDate(this)">Remove</button>
                </div>
            </div>
        `;
        
        container.appendChild(dateDiv);
    }

    // Function to remove additional date
    function removeAdditionalDate(button) {
        button.parentElement.parentElement.parentElement.remove();
    }

    // Company change handler
    function onCompanyChange() {
        const companyId = document.getElementById('company_selector').value;
        document.getElementById('company_id').value = companyId;
        
        if (companyId) {
            // Enable user selector and populate users for this company
            document.getElementById('user_selector').disabled = false;
            
            // Make AJAX request to load company data
            loadCompanyInfo();
        } else {
            document.getElementById('user_selector').disabled = true;
            // Clear user-related fields
            document.getElementById('user_selector').innerHTML = '<option value="">Choose a user...</option>';
        }
    }

    // User change handler
    function onUserChange() {
        const userId = document.getElementById('user_selector').value;
        document.getElementById('user_id').value = userId;
        
        if (userId) {
            // Make AJAX request to load user data
            loadUserInfo();
        }
    }

    // Load company information
    function loadCompanyInfo() {
        const companyId = document.getElementById('company_selector').value;
        if (!companyId) return;
        
        // In a real implementation, this would be an AJAX call
        // For now, we'll rely on server-side data if available
        console.log('Loading company info for ID: ' + companyId);
    }

    // Load user information
    function loadUserInfo() {
        const userId = document.getElementById('user_selector').value;
        if (!userId) return;
        
        // In a real implementation, this would be an AJAX call
        // For now, we'll rely on server-side data if available
        console.log('Loading user info for ID: ' + userId);
    }

    // Load tour information
    function loadTourInfo() {
        // Implement tour autocomplete functionality
        console.log('Loading tour info');
    }

    // Update invoice cost when rush order checkboxes change
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial invoice cost display
        updateInvoiceCost();

        // Add event listeners to all checkboxes that affect cost
        const audioOrderCheckbox = document.getElementById('order_details_audio_order');
        const audioRushCheckbox = document.getElementById('order_details_audio_order_rush');
        const videoOrderCheckbox = document.getElementById('order_details_video_order');
        const videoRushCheckbox = document.getElementById('order_details_video_order_rush');

        if (audioOrderCheckbox) audioOrderCheckbox.addEventListener('change', function() {
            toggleRushOrder('audio', this.checked);
        });
        if (audioRushCheckbox) audioRushCheckbox.addEventListener('change', updateInvoiceCost);
        if (videoOrderCheckbox) videoOrderCheckbox.addEventListener('change', function() {
            toggleRushOrder('video', this.checked);
        });
        if (videoRushCheckbox) videoRushCheckbox.addEventListener('change', updateInvoiceCost);
    });

    // Venue and market autocomplete functionality
    document.addEventListener('input', function(e) {
        if (e.target.id === 'event_info_venue') {
            // Implement venue autocomplete
            console.log('Searching for venues...');
        } else if (e.target.id === 'event_info_market') {
            // Implement market autocomplete
            console.log('Searching for markets...');
        } else if (e.target.id === 'tour_input') {
            // Implement tour autocomplete
            console.log('Searching for tours...');
        }
    });
</script>

<?php
$content = ob_get_clean();
include '../../../includes/layout.php';
?>