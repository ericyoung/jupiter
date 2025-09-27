<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

setCustomBreadcrumbs([
    ['name' => 'Dashboard', 'url' => getRelativePath('admin/dashboard.php')],
    ['name' => 'Orders', 'url' => getRelativePath('admin/orders/list.php')],
    ['name' => 'Create', 'url' => '']
]);

// Check if user has permission to create orders (superadmin, accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'accounts'];

if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to create orders.');
    header('Location: ../dashboard.php');
    exit;
}

$title = "Create New Order - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Create New Order</h3>
            </div>
            <div class="card-body">
                <form id="orderCreationForm" method="GET" action="av/create.php" onsubmit="return false;">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="company_id" class="form-label">Select Company *</label>
                            <select class="form-control" id="company_id" name="company_id" required onchange="loadUsersForCompany()">
                                <option value="">Choose a company...</option>
                                <?php
                                global $pdo;
                                $companies = $pdo->query("SELECT id, company_name FROM companies WHERE enabled = 1 ORDER BY company_name")->fetchAll();
                                foreach ($companies as $company):
                                ?>
                                    <option value="<?php echo $company['id']; ?>">
                                        <?php echo htmlspecialchars($company['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="user_id" class="form-label">Select User *</label>
                            <select class="form-control" id="user_id" name="user_id" required disabled>
                                <option value="">Choose a user...</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="tour_input" class="form-label">Select Tour *</label>
                            <input type="text" class="form-control" id="tour_input" placeholder="Start typing to search for a tour..." autocomplete="off">
                            <div id="tour_suggestions" class="list-group" style="display: none; position: absolute; z-index: 1000; width: 100%;"></div>
                            <input type="hidden" id="tour_id" name="tour_id" value="">
                        </div>
                    </div>

                    <div id="confirmation_message" class="alert alert-info" style="display: none;">
                        <p>Start a new order for <strong id="confirm_company"></strong>, <strong id="confirm_user"></strong><span id="confirm_tour_span"></span>?</p>
                        <button type="button" class="btn btn-success" onclick="proceedToOrder()">Continue</button>
                        <button type="button" class="btn btn-secondary" onclick="hideConfirmation()">Cancel</button>
                    </div>

                    <div id="no_selection_message" class="alert alert-secondary">
                        Please select a company and user to continue. Tour selection is optional.
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function loadUsersForCompany() {
        const companyId = document.getElementById('company_id').value;
        const userSelect = document.getElementById('user_id');
        const tourInput = document.getElementById('tour_input');
        const confirmDiv = document.getElementById('confirmation_message');
        const noSelectionDiv = document.getElementById('no_selection_message');
        
        // Reset user selection but keep tour input enabled
        userSelect.innerHTML = '<option value="">Choose a user...</option>';
        userSelect.disabled = true;
        confirmDiv.style.display = 'none';
        noSelectionDiv.style.display = 'block';
        document.getElementById('tour_id').value = '';
        
        if (companyId) {
            // Make AJAX call to fetch users for the selected company
            fetchUsersForCompany(companyId);
        }
        
        // Keep tour input enabled for user to enter search term
        tourInput.disabled = false;
    }

    function fetchUsersForCompany(companyId) {
        // Create an AJAX request to fetch users for the selected company
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../../includes/api.php', true); // We'll create this endpoint
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                const userSelect = document.getElementById('user_id');

                if (response.success && response.users && response.users.length > 0) {
                    userSelect.innerHTML = '<option value="">Choose a user...</option>';

                    response.users.forEach(function(user) {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.name + ' (' + user.email + ')';
                        userSelect.appendChild(option);
                    });

                    userSelect.disabled = false;
                } else {
                    userSelect.innerHTML = '<option value="">No users found</option>';
                    userSelect.disabled = true;
                }
            }
        };

        // For now, simulate the response
        simulateUserResponse(companyId);
    }

    // Get users for the selected company
    function simulateUserResponse(companyId) {
        const userSelect = document.getElementById('user_id');
        userSelect.innerHTML = '<option value="">Choose a user...</option>';
        
        // Use our dedicated API endpoint
        fetch(`./get_users.php?company_id=${companyId}`)
        .then(response => response.json())
        .then(users => {
            if(users.length > 0 && !users.error) {
                users.forEach(function(user) {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name + ' (' + user.email + ')';
                    userSelect.appendChild(option);
                });
                userSelect.disabled = false;
            } else {
                userSelect.innerHTML = '<option value="">No active users found</option>';
                userSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error fetching users:', error);
            userSelect.innerHTML = '<option value="">Error loading users</option>';
            userSelect.disabled = true;
        });
    }

    function hideConfirmation() {
        document.getElementById('confirmation_message').style.display = 'none';
        document.getElementById('no_selection_message').style.display = 'block';
    }

    // Tour autocomplete functionality
    let tourTimeout;
    document.getElementById('tour_input').addEventListener('input', function() {
        const searchTerm = this.value;
        const tourSuggestions = document.getElementById('tour_suggestions');

        // Clear previous timeout
        if (tourTimeout) {
            clearTimeout(tourTimeout);
        }

        if (searchTerm.length >= 2) {
            // Wait for a brief moment before searching to avoid too many requests
            tourTimeout = setTimeout(() => {
                // Create search functionality
                searchTours(searchTerm);
            }, 300);
        } else {
            tourSuggestions.style.display = 'none';
        }
    });

    function searchTours(searchTerm) {
        const tourSuggestions = document.getElementById('tour_suggestions');

        // For now, using a simulated search
        // In real implementation, this would be an AJAX call
        // Log the search term to console for debugging
        console.log('Searching for tours with term:', searchTerm);
        
        fetch(`./get_tours.php?search=${encodeURIComponent(searchTerm)}`)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(tours => {
            console.log('Tours received:', tours);
            tourSuggestions.innerHTML = '';

            if (Array.isArray(tours) && tours.length > 0) {
                // Filter out any error fields if present
                const validTours = tours.filter(tour => !tour.error);
                
                if (validTours.length > 0) {
                    validTours.forEach(function(tour) {
                        const item = document.createElement('div');
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = tour.headliner;
                        item.onclick = function() {
                            selectTour(tour.id, tour.headliner);
                        };
                        tourSuggestions.appendChild(item);
                    });
                    tourSuggestions.style.display = 'block';
                } else {
                    // Check if there are error messages
                    if (tours[0] && tours[0].error) {
                        const item = document.createElement('div');
                        item.className = 'list-group-item';
                        item.textContent = 'Error: ' + tours[0].error;
                        tourSuggestions.appendChild(item);
                        tourSuggestions.style.display = 'block';
                    } else {
                        const item = document.createElement('div');
                        item.className = 'list-group-item';
                        item.textContent = 'No tours found';
                        tourSuggestions.appendChild(item);
                        tourSuggestions.style.display = 'block';
                    }
                }
            } else if (tours.error) {
                const item = document.createElement('div');
                item.className = 'list-group-item';
                item.textContent = 'Error: ' + tours.error;
                tourSuggestions.appendChild(item);
                tourSuggestions.style.display = 'block';
            } else {
                const item = document.createElement('div');
                item.className = 'list-group-item';
                item.textContent = 'No tours found';
                tourSuggestions.appendChild(item);
                tourSuggestions.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error searching tours:', error);
            tourSuggestions.innerHTML = '<div class="list-group-item">Error: ' + error.message + '</div>';
            tourSuggestions.style.display = 'block';
        });
    }

    document.getElementById('tour_input').addEventListener('focusout', function() {
        // Hide suggestions after a short delay to allow clicking on them
        setTimeout(() => {
            document.getElementById('tour_suggestions').style.display = 'none';
        }, 200);
    });

    document.getElementById('tour_input').addEventListener('focusin', function() {
        const searchTerm = this.value;
        if (searchTerm.length >= 2) {
            document.getElementById('tour_suggestions').style.display = 'block';
        }
    });

    // Tour selection handler
    function selectTour(tourId, tourName) {
        document.getElementById('tour_id').value = tourId;
        document.getElementById('tour_input').value = tourName;
        document.getElementById('tour_suggestions').style.display = 'none';

        // Show confirmation message
        updateConfirmationMessage();
    }

    function updateConfirmationMessage() {
        const companyId = document.getElementById('company_id').value;
        const companySelect = document.getElementById('company_id');
        const companyName = companySelect.options[companySelect.selectedIndex]?.text || '';
        const userId = document.getElementById('user_id').value;
        const userSelect = document.getElementById('user_id');
        const userName = userSelect.options[userSelect.selectedIndex]?.text || '';
        const tourId = document.getElementById('tour_id').value;
        const tourName = document.getElementById('tour_input').value;

        if (companyId && userId) { // Removed tourId from requirement since it's optional
            document.getElementById('confirm_company').textContent = companyName;
            document.getElementById('confirm_user').textContent = userName;
            
            // Update the tour span - only show it if tourId exists
            const confirmTourSpan = document.getElementById('confirm_tour_span');
            if (tourId) {
                confirmTourSpan.textContent = ', and ' + tourName;
            } else {
                confirmTourSpan.textContent = '';
            }

            document.getElementById('no_selection_message').style.display = 'none';
            document.getElementById('confirmation_message').style.display = 'block';
        } else {
            document.getElementById('confirmation_message').style.display = 'none';
            document.getElementById('no_selection_message').style.display = 'block';
        }
    }

    // Add event listener to detect when user is selected
    document.getElementById('user_id').addEventListener('change', updateConfirmationMessage);

    // Function to proceed to the order creation with proper parameters
    function proceedToOrder() {
        const companyId = document.getElementById('company_id').value;
        const userId = document.getElementById('user_id').value;
        const tourId = document.getElementById('tour_id').value;

        if (!companyId || !userId) {
            alert('Please select both a company and a user before continuing.');
            return false;
        }

        // Build the URL with proper parameters
        let url = `av/create.php?company_id=${encodeURIComponent(companyId)}&user_id=${encodeURIComponent(userId)}`;
        
        // Only add tour_id to the URL if it's not empty
        if (tourId) {
            url += `&tour_id=${encodeURIComponent(tourId)}`;
        }

        // Redirect to the order creation page
        window.location.href = url;
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Make tour input editable
        document.getElementById('tour_input').disabled = false;
    });
</script>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?>
