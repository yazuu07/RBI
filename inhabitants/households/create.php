<?php
require_once '../../config.php';
requireLogin();
requirePermission('inhabitants', 'add');

$pdo = getDB();
$errors = [];
$success = false;
$record_id = null;

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
    
    // Validate
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($date_of_birth)) $errors[] = "Date of birth is required";
    if (empty($sex)) $errors[] = "Sex is required";
    if (empty($civil_status)) $errors[] = "Civil status is required";
    
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
            disability, pets, profile_picture, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $last_name, $first_name, $middle_name, $ext_name,
            $place_of_birth, $date_of_birth, $age, $sex,
            $civil_status, $citizenship, $occupation, $profession,
            $disability, $pets, $profile_picture, $_SESSION['user_id']
        ]);
        
        if ($result) {
            $record_id = $pdo->lastInsertId();
            logAudit($_SESSION['user_id'], 'CREATE', 'household_records', $record_id, 
                "Added household: $first_name $last_name");
            $success = true;
            
            // Clear cache
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
    <title>Add Household - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-home-plus text-success"></i> Add New Household
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

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="row">
                                <!-- Profile Picture -->
                                <div class="col-md-12 mb-4 text-center">
                                    <div class="mb-2">
                                        <img id="profilePreview" src="../../assets/images/default-avatar.png" class="profile-preview" alt="Profile Preview">
                                    </div>
                                    <label class="btn btn-outline-primary">
                                        <i class="fas fa-camera"></i> Upload Profile Picture
                                        <input type="file" name="profile_picture" accept="image/*" style="display: none;" onchange="previewImage(this)">
                                    </label>
                                    <small class="d-block text-muted mt-1">
                                        <i class="fas fa-info-circle"></i> JPG, PNG, GIF (Max 2MB)
                                    </small>
                                </div>

                                <!-- Name Fields -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" required>
                                    <div class="invalid-feedback">Last name is required</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
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

                                <!-- Personal Details -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Place of Birth</label>
                                    <input type="text" name="place_of_birth" class="form-control" placeholder="City/Municipality, Province">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                    <input type="date" name="date_of_birth" class="form-control" required onchange="calculateAge(this)">
                                    <small class="text-muted" id="ageDisplay">Age: </small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Sex <span class="text-danger">*</span></label>
                                    <select name="sex" class="form-select" required>
                                        <option value="">Select...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="invalid-feedback">Sex is required</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                                    <select name="civil_status" class="form-select" required>
                                        <option value="">Select...</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Widowed">Widowed</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Separated">Separated</option>
                                    </select>
                                    <div class="invalid-feedback">Civil status is required</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Citizenship</label>
                                    <input type="text" name="citizenship" class="form-control" placeholder="e.g., Filipino">
                                </div>

                                <!-- Occupation & Profession -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Occupation</label>
                                    <input type="text" name="occupation" class="form-control" placeholder="e.g., Teacher">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Profession (if any)</label>
                                    <input type="text" name="profession" class="form-control" placeholder="e.g., Licensed Professional Teacher">
                                </div>

                                <!-- Disability & Pets -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Disability</label>
                                    <textarea name="disability" class="form-control" rows="2" placeholder="List any disabilities..."></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Pets</label>
                                    <textarea name="pets" class="form-control" rows="2" placeholder="List pets in household..."></textarea>
                                </div>

                                <!-- Buttons -->
                                <div class="col-md-12 mt-3">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Save Household
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
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