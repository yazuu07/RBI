<?php
require_once '../../config.php';
requireLogin();
requirePermission('inhabitants', 'edit');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit();
}

$pdo = getDB();

// Get record details
$stmt = $pdo->prepare("SELECT * FROM household_records WHERE id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = false;

// Get existing household members for head of family dropdown
$members = $pdo->query("SELECT id, last_name, first_name, middle_name FROM household_records ORDER BY last_name, first_name")->fetchAll();

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
    $profile_picture = $record['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        // Delete old picture
        if ($record['profile_picture']) {
            deleteFile($record['profile_picture']);
        }
        
        $new_picture = uploadFile($_FILES['profile_picture']);
        if ($new_picture) {
            $profile_picture = $new_picture;
        } else {
            $errors[] = "Invalid file format. Only JPG, PNG, GIF allowed (max 2MB).";
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        $sql = "UPDATE household_records SET 
            last_name = ?, first_name = ?, middle_name = ?, ext_name = ?,
            place_of_birth = ?, date_of_birth = ?, age = ?, sex = ?,
            civil_status = ?, citizenship = ?, occupation = ?, profession = ?,
            disability = ?, pets = ?, profile_picture = ?,
            household_type = ?, dwelling_type = ?, household_name = ?,
            position_in_household = ?, tenure_status = ?, monthly_income = ?,
            head_of_family_id = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $last_name, $first_name, $middle_name, $ext_name,
            $place_of_birth, $date_of_birth, $age, $sex,
            $civil_status, $citizenship, $occupation, $profession,
            $disability, $pets, $profile_picture,
            $household_type, $dwelling_type, $household_name,
            $position_in_household, $tenure_status, $monthly_income,
            $head_of_family_id, $id
        ]);
        
        if ($result) {
            logAudit($_SESSION['user_id'], 'UPDATE', 'household_records', $id, 
                "Updated household: $first_name $last_name");
            $success = true;
            
            // Clear cache
            clearCache('dashboard_stats');
            
            // Refresh record data
            $stmt = $pdo->prepare("SELECT * FROM household_records WHERE id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch();
        } else {
            $errors[] = "Failed to update record. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Household - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        .form-section-title {
            font-weight: 700;
            color: #ffc107;
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-home-edit text-warning"></i> EDIT HOUSEHOLD
                    </h1>
                    <div>
                        <a href="view.php?id=<?= $record['id'] ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show slide-down">
                        <i class="fas fa-check-circle"></i> Household updated successfully!
                        <a href="view.php?id=<?= $record['id'] ?>" class="alert-link">View Record</a>
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
                            <?php if ($record['profile_picture']): ?>
                                <img id="profilePreview" src="../../uploads/<?= $record['profile_picture'] ?>" class="profile-preview" alt="Profile">
                            <?php else: ?>
                                <img id="profilePreview" src="../../assets/images/default-avatar.png" class="profile-preview" alt="Default">
                            <?php endif; ?>
                        </div>
                        <label class="btn btn-outline-primary">
                            <i class="fas fa-camera"></i> Change Profile Picture
                            <input type="file" name="profile_picture" accept="image/*" style="display: none;" onchange="previewImage(this)">
                        </label>
                        <small class="d-block text-muted mt-1">
                            <i class="fas fa-info-circle"></i> JPG, PNG, GIF (Max 2MB)
                        </small>
                        <?php if ($record['profile_picture']): ?>
                            <small class="d-block text-muted">Current: <?= $record['profile_picture'] ?></small>
                        <?php endif; ?>
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
                                        <option value="<?= $type ?>" <?= $record['household_type'] == $type ? 'selected' : '' ?>>
                                            <?= $type ?>
                                        </option>
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
                                        <option value="<?= $type ?>" <?= $record['dwelling_type'] == $type ? 'selected' : '' ?>>
                                            <?= $type ?>
                                        </option>
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
                                        <option value="<?= $pos ?>" <?= $record['position_in_household'] == $pos ? 'selected' : '' ?>>
                                            <?= $pos ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Household Name
                                </label>
                                <input type="text" name="household_name" class="form-control" value="<?= htmlspecialchars($record['household_name']) ?>" placeholder="e.g., Dela Cruz Family">
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
                                        <option value="<?= $status ?>" <?= $record['tenure_status'] == $status ? 'selected' : '' ?>>
                                            <?= $status ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Monthly Income
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="monthly_income" class="form-control" value="<?= $record['monthly_income'] ?? 0 ?>" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================ -->
                    <!-- HEAD OF FAMILY SECTION -->
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
                                <select name="head_of_family_id" class="form-select">
                                    <option value="">Select member...</option>
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?= $member['id'] ?>" <?= $record['head_of_family_id'] == $member['id'] ? 'selected' : '' ?>>
                                            <?= $member['last_name'] ?>, <?= $member['first_name'] ?>
                                            <?php if ($member['middle_name']): ?>
                                                <?= $member['middle_name'][0] ?>.
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="help-text mt-1">
                                    <i class="fas fa-info-circle"></i> 
                                    Identify the head of the family by selecting from existing members.
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
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($record['last_name']) ?>" required>
                                <div class="invalid-feedback">Last name is required</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">First Name <span class="required-star">*</span></label>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($record['first_name']) ?>" required>
                                <div class="invalid-feedback">First name is required</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($record['middle_name']) ?>">
                            </div>
                            <div class="col-md-1 mb-3">
                                <label class="form-label">EXT</label>
                                <input type="text" name="ext_name" class="form-control" placeholder="Jr." value="<?= htmlspecialchars($record['ext_name']) ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Place of Birth</label>
                                <input type="text" name="place_of_birth" class="form-control" value="<?= htmlspecialchars($record['place_of_birth']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth <span class="required-star">*</span></label>
                                <input type="date" name="date_of_birth" class="form-control" value="<?= $record['date_of_birth'] ?>" required onchange="calculateAge(this)">
                                <small class="text-muted" id="ageDisplay">Age: <?= $record['age'] ?? '' ?> years</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Sex <span class="required-star">*</span></label>
                                <select name="sex" class="form-select" required>
                                    <option value="">Select...</option>
                                    <option value="Male" <?= $record['sex'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $record['sex'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $record['sex'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Civil Status <span class="required-star">*</span></label>
                                <select name="civil_status" class="form-select" required>
                                    <option value="">Select...</option>
                                    <option value="Single" <?= $record['civil_status'] == 'Single' ? 'selected' : '' ?>>Single</option>
                                    <option value="Married" <?= $record['civil_status'] == 'Married' ? 'selected' : '' ?>>Married</option>
                                    <option value="Widowed" <?= $record['civil_status'] == 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                                    <option value="Divorced" <?= $record['civil_status'] == 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                                    <option value="Separated" <?= $record['civil_status'] == 'Separated' ? 'selected' : '' ?>>Separated</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Citizenship</label>
                                <input type="text" name="citizenship" class="form-control" value="<?= htmlspecialchars($record['citizenship']) ?>" placeholder="e.g., Filipino">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Occupation</label>
                                <input type="text" name="occupation" class="form-control" value="<?= htmlspecialchars($record['occupation']) ?>" placeholder="e.g., Teacher">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Profession</label>
                                <input type="text" name="profession" class="form-control" value="<?= htmlspecialchars($record['profession']) ?>" placeholder="e.g., Licensed Professional Teacher">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Disability</label>
                                <textarea name="disability" class="form-control" rows="2" placeholder="List any disabilities..."><?= htmlspecialchars($record['disability']) ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pets</label>
                                <textarea name="pets" class="form-control" rows="2" placeholder="List pets in household..."><?= htmlspecialchars($record['pets']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-warning btn-lg px-5">
                            <i class="fas fa-save"></i> UPDATE
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        // Trigger age calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            var dobInput = document.querySelector('input[name="date_of_birth"]');
            if (dobInput && dobInput.value) {
                calculateAge(dobInput);
            }
        });

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