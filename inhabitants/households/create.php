<?php
require_once '../../config.php';
requireLogin();
requirePermission('inhabitants', 'add');

$pdo = getDB();
$errors = [];
$success = false;
$record_id = null;

// Get existing household members for head of family dropdown
$members = $pdo->query("
    SELECT id, last_name, first_name, middle_name, ext_name 
    FROM household_records 
    ORDER BY last_name, first_name
")->fetchAll();

// Get all citizens for possible head of family (searchable)
$citizens = $pdo->query("
    SELECT id, last_name, first_name, middle_name, ext_name 
    FROM individual_records 
    ORDER BY last_name, first_name
")->fetchAll();

// Define option arrays
$household_types = ['Nuclear', 'Single Parent', 'Extended', 'Childless', 'Grandparent', 'Step Family'];
$dwelling_types = ['Single Family House', 'Townhouse', 'Condominium', 'Duplex', 'Mobile Home', 'Multi Unit', 'Apartment'];
$positions = ['Father', 'Mother', 'Son', 'Daughter', 'Grandmother', 'Grandfather', 'Father in Law', 'Mother in Law', 'Brother in Law', 'Sister in Law'];
$tenure_statuses = ['Owner', 'Renter', 'Living with Parents', 'Living with Relatives', 'Boarder', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $ext_name = trim($_POST['ext_name']);
    $place_of_birth = trim($_POST['place_of_birth']);
    $date_of_birth = $_POST['date_of_birth'];
    $sex = $_POST['sex'];
    $civil_status = $_POST['civil_status'];
    $citizenship = trim($_POST['citizenship']);
    $occupation = trim($_POST['occupation']);
    $profession = trim($_POST['profession']);
    $disability = trim($_POST['disability']);
    $pets = trim($_POST['pets']);
    
    // NEW FIELDS
    $household_type = trim($_POST['household_type']);
    $dwelling_type = trim($_POST['dwelling_type']);
    $household_name = trim($_POST['household_name']);
    $position_in_household = trim($_POST['position_in_household']);
    $tenure_status = trim($_POST['tenure_status']);
    $monthly_income = !empty($_POST['monthly_income']) ? (float)$_POST['monthly_income'] : 0;
    $head_of_family_id = !empty($_POST['head_of_family_id']) ? (int)$_POST['head_of_family_id'] : null;
    
    // Validate
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($date_of_birth)) $errors[] = "Date of birth is required";
    if (empty($sex)) $errors[] = "Sex is required";
    if (empty($civil_status)) $errors[] = "Civil status is required";
    if (empty($household_type)) $errors[] = "Household type is required";
    if (empty($position_in_household)) $errors[] = "Position is required";
    
    // Calculate age
    $age = null;
    if (!empty($date_of_birth)) {
        $age = calculateAge($date_of_birth);
    }
    
    // Handle profile picture upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $profile_picture = uploadFile($_FILES['profile_picture']);
        if (!$profile_picture) {
            $errors[] = "Invalid file format. Only JPG, PNG, GIF allowed (max 2MB).";
        }
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        $sql = "INSERT INTO household_records (
            last_name, first_name, middle_name, ext_name, 
            place_of_birth, date_of_birth, age, sex, 
            civil_status, citizenship, occupation, profession, 
            disability, pets, profile_picture, created_by,
            household_type, dwelling_type, household_name, 
            position_in_household, tenure_status, monthly_income, 
            head_of_family_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $last_name, $first_name, $middle_name, $ext_name,
            $place_of_birth, $date_of_birth, $age, $sex,
            $civil_status, $citizenship, $occupation, $profession,
            $disability, $pets, $profile_picture, $_SESSION['user_id'],
            $household_type, $dwelling_type, $household_name,
            $position_in_household, $tenure_status, $monthly_income,
            $head_of_family_id
        ]);
        
        if ($result) {
            $record_id = $pdo->lastInsertId();
            logAudit($_SESSION['user_id'], 'CREATE', 'household_records', $record_id, 
                "Added household: $first_name $last_name");
            $success = true;
            clearCache('dashboard_stats');
        } else {
            $errors[] = "Failed to save record. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Household - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .form-section-title {
            font-weight: 700;
            color: #28a745;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        .form-section-title i {
            margin-right: 8px;
        }
        .required-star {
            color: #dc3545;
        }
        .help-text {
            font-size: 0.8rem;
            color: #6c757d;
            font-style: italic;
        }
        /* Bootstrap Select customization */
        .bootstrap-select .dropdown-toggle {
            border-radius: 10px !important;
            border: 2px solid #e8ecf1 !important;
            padding: 10px 15px !important;
            background: white !important;
        }
        .bootstrap-select .dropdown-toggle:focus {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        }
        .dropdown-menu {
            border-radius: 10px !important;
            border: 2px solid #e8ecf1 !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
        }
        .dropdown-menu .dropdown-item {
            padding: 8px 15px !important;
            font-size: 0.9rem !important;
        }
        .dropdown-menu .dropdown-item:hover {
            background: #f0f0f0 !important;
        }
        .dropdown-menu .dropdown-item.active {
            background: #28a745 !important;
            color: white !important;
        }
        .bs-searchbox .form-control {
            border-radius: 8px !important;
            border: 2px solid #e8ecf1 !important;
            padding: 8px 12px !important;
        }
        .bs-searchbox .form-control:focus {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-home-plus text-success"></i> CREATE HOUSEHOLD
                    </h1>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show slide-down">
                        <i class="fas fa-check-circle"></i> Household added successfully!
                        <a href="view.php?id=<?= $record_id ?>" class="alert-link">View Record</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger slide-down">
                        <i class="fas fa-exclamation-circle"></i> Please fix the following errors:
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <!-- Profile Picture -->
                    <div class="text-center mb-4">
                        <div class="mb-2">
                            <img id="profilePreview" src="../../assets/images/default-avatar.png" class="profile-preview" alt="Profile Preview">
                        </div>
                        <label class="btn btn-outline-primary">
                            <i class="fas fa-camera"></i> Upload Profile Picture
                            <input type="file" name="profile_picture" accept="image/*" style="display: none;" onchange="previewImage(this)">
                        </label>
                        <small class="d-block text-muted mt-1">JPG, PNG, GIF (Max 2MB)</small>
                    </div>

                    <!-- ============================================ -->
                    <!-- HOUSEHOLD TYPE SECTION -->
                    <!-- ============================================ -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-home"></i> HOUSEHOLD TYPE
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Household Type <span class="required-star">*</span>
                                </label>
                                <select name="household_type" class="form-select" required>
                                    <option value="">Select household type...</option>
                                    <?php foreach ($household_types as $type): ?>
                                        <option value="<?= $type ?>"><?= $type ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Dwelling Type
                                </label>
                                <select name="dwelling_type" class="form-select">
                                    <option value="">Select dwelling type...</option>
                                    <?php foreach ($dwelling_types as $type): ?>
                                        <option value="<?= $type ?>"><?= $type ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- POSITION & FAMILY SECTION -->
                    <!-- ============================================ -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-users"></i> POSITION & FAMILY
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Position in Household <span class="required-star">*</span>
                                </label>
                                <select name="position_in_household" class="form-select" required>
                                    <option value="">Select position...</option>
                                    <?php foreach ($positions as $pos): ?>
                                        <option value="<?= $pos ?>"><?= $pos ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Household Name
                                </label>
                                <input type="text" name="household_name" class="form-control" placeholder="e.g., Dela Cruz Family">
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- TENURE & INCOME SECTION -->
                    <!-- ============================================ -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-money-bill-wave"></i> TENURE & INCOME
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Tenure Status
                                </label>
                                <select name="tenure_status" class="form-select">
                                    <option value="">Select tenure status...</option>
                                    <?php foreach ($tenure_statuses as $status): ?>
                                        <option value="<?= $status ?>"><?= $status ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Monthly Income
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="monthly_income" class="form-control" placeholder="0.00" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- HEAD OF FAMILY SECTION - SEARCHABLE DROPDOWN -->
                    <!-- ============================================ -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-user-tie"></i> HEAD OF THE FAMILY
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    Select Head of the Family
                                </label>
                                <select name="head_of_family_id" id="headOfFamily" class="form-select selectpicker" data-live-search="true" title="Search for member...">
                                    <option value="">Select member...</option>
                                    
                                    <!-- Household Members -->
                                    <?php if (count($members) > 0): ?>
                                        <optgroup label="Household Members">
                                            <?php foreach ($members as $member): ?>
                                                <option value="<?= $member['id'] ?>">
                                                    <?= htmlspecialchars($member['last_name']) ?>, <?= htmlspecialchars($member['first_name']) ?>
                                                    <?php if ($member['middle_name']): ?>
                                                        <?= htmlspecialchars($member['middle_name'][0]) ?>.
                                                    <?php endif; ?>
                                                    <?php if ($member['ext_name']): ?>
                                                        (<?= htmlspecialchars($member['ext_name']) ?>)
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    
                                    <!-- Citizens -->
                                    <?php if (count($citizens) > 0): ?>
                                        <optgroup label="Barangay Citizens">
                                            <?php foreach ($citizens as $citizen): ?>
                                                <option value="<?= $citizen['id'] ?>">
                                                    <?= htmlspecialchars($citizen['last_name']) ?>, <?= htmlspecialchars($citizen['first_name']) ?>
                                                    <?php if ($citizen['middle_name']): ?>
                                                        <?= htmlspecialchars($citizen['middle_name'][0]) ?>.
                                                    <?php endif; ?>
                                                    <?php if ($citizen['ext_name']): ?>
                                                        (<?= htmlspecialchars($citizen['ext_name']) ?>)
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>
                                <div class="help-text mt-1">
                                    <i class="fas fa-info-circle"></i> 
                                    Type to search for the head of the family. You can select from household members or barangay citizens.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- PERSONAL INFORMATION SECTION -->
                    <!-- ============================================ -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-user"></i> PERSONAL INFORMATION
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Last Name <span class="required-star">*</span></label>
                                <input type="text" name="last_name" class="form-control" required>
                                <div class="invalid-feedback">Last name is required</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">First Name <span class="required-star">*</span></label>
                                <input type="text" name="first_name" class="form-control" required>
                                <div class="invalid-feedback">First name is required</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control">
                            </div>
                            <div class="col-md-1 mb-3">
                                <label class="form-label">EXT</label>
                                <input type="text" name="ext_name" class="form-control" placeholder="Jr.">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Place of Birth</label>
                                <input type="text" name="place_of_birth" class="form-control" placeholder="City/Municipality, Province">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth <span class="required-star">*</span></label>
                                <input type="date" name="date_of_birth" class="form-control" required onchange="calculateAge(this)">
                                <small class="text-muted" id="ageDisplay">Age: </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Sex <span class="required-star">*</span></label>
                                <select name="sex" class="form-select" required>
                                    <option value="">Select...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Civil Status <span class="required-star">*</span></label>
                                <select name="civil_status" class="form-select" required>
                                    <option value="">Select...</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Separated">Separated</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Citizenship</label>
                                <input type="text" name="citizenship" class="form-control" placeholder="e.g., Filipino">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Occupation</label>
                                <input type="text" name="occupation" class="form-control" placeholder="e.g., Teacher">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Profession</label>
                                <input type="text" name="profession" class="form-control" placeholder="e.g., Licensed Professional Teacher">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Disability</label>
                                <textarea name="disability" class="form-control" rows="2" placeholder="List any disabilities..."></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pets</label>
                                <textarea name="pets" class="form-control" rows="2" placeholder="List pets in household..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-success btn-lg px-5">
                            <i class="fas fa-save"></i> SAVE
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Bootstrap Select
            $('.selectpicker').selectpicker({
                liveSearch: true,
                liveSearchPlaceholder: 'Type to search for member...',
                size: 10,
                dropupAuto: false
            });
        });

        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function calculateAge(input) {
            if (input.value) {
                var birthDate = new Date(input.value);
                var today = new Date();
                var age = today.getFullYear() - birthDate.getFullYear();
                var m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                document.getElementById('ageDisplay').textContent = 'Age: ' + age + ' years';
            }
        }

        // Form validation
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>