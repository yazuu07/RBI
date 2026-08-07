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
$stmt = $pdo->prepare("SELECT * FROM individual_records WHERE id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    header('Location: index.php');
    exit();
}

// Define option arrays
$regions = [
    'Autonomous Region in Muslim Mindanao', 'Bicol Region', 'Cagayan Valley', 
    'Calabarzon', 'Caraga', 'Central Luzon', 'Central Visayas', 
    'Cordillera Administrative Region', 'Davao Region', 'Eastern Visayas', 
    'Ilocos Region', 'Mimaropa', 'National Capital Region', 
    'Northern Mindanao', 'Soccsksargen', 'Western Visayas', 'Zamboanga Peninsula'
];

$provinces = [
    'Basilan', 'Cavite', 'Cebu', 'Davao del Sur', 'Ilocos Norte', 
    'Laguna', 'Manila', 'Quezon', 'Rizal', 'Zamboanga del Norte'
];

$barangays = ['Calut', 'Santo Cristo', 'San Antonio', 'San Jose', 'San Miguel'];

$blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'];

$ethnicities = ['Ivatan', 'Christian', 'Muslim', 'Chinese', 'Other'];

$positions = ['Father', 'Mother', 'Son', 'Daughter', 'Grandfather', 'Grandmother'];

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $ext_name = trim($_POST['ext_name']);
    $birthdate = $_POST['birthdate'];
    $birth_place = trim($_POST['birth_place']);
    $gender = $_POST['gender'];
    $civil_status = $_POST['civil_status'];
    $highest_education = $_POST['highest_education'];
    $educational_status = trim($_POST['educational_status']);
    $profession = trim($_POST['profession']);
    $philsys_number = trim($_POST['philsys_number']);
    
    // Contact Details
    $email = trim($_POST['email']);
    $mobile_number = trim($_POST['mobile_number']);
    $telephone_number = trim($_POST['telephone_number']);
    $region = trim($_POST['region']);
    $province = trim($_POST['province']);
    $city_municipality = trim($_POST['city_municipality']);
    $barangay = trim($_POST['barangay']);
    $house_address = trim($_POST['house_address']);
    $street = trim($_POST['street']);
    $subdivision = trim($_POST['subdivision']);
    $zip_code = trim($_POST['zip_code']);
    
    // Identity Information
    $blood_type = trim($_POST['blood_type']);
    $weight = $_POST['weight'] ? (float)$_POST['weight'] : null;
    $height = trim($_POST['height']);
    $citizenship = trim($_POST['citizenship']);
    $registered_voter = isset($_POST['registered_voter']) ? 1 : 0;
    $voter_not_resident = isset($_POST['voter_not_resident']) ? 1 : 0;
    $ethnicity = trim($_POST['ethnicity']);
    $position_in_household = trim($_POST['position_in_household']);
    $mother_maiden_name = trim($_POST['mother_maiden_name']);
    $has_pet = isset($_POST['has_pet']) ? 1 : 0;
    
    // Sectoral Information
    $sectors = isset($_POST['sectors']) ? $_POST['sectors'] : [];
    $sector_other = trim($_POST['sector_other']);
    
    // Validate
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($birthdate)) $errors[] = "Birthdate is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($civil_status)) $errors[] = "Civil status is required";
    if (empty($mobile_number)) $errors[] = "Mobile number is required";
    if (empty($citizenship)) $errors[] = "Citizenship is required";
    
    // Calculate age
    $age = null;
    if (!empty($birthdate)) {
        $age = calculateAge($birthdate);
    }
    
    // Handle profile picture upload
    $profile_picture = $record['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
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
    
    // Update database
    if (empty($errors)) {
        $sql = "UPDATE individual_records SET 
            last_name = ?, first_name = ?, middle_name = ?, ext_name = ?,
            date_of_birth = ?, place_of_birth = ?, age = ?, sex = ?,
            civil_status = ?, highest_education = ?, profile_picture = ?,
            educational_status = ?, philsys_number = ?, email = ?, mobile_number = ?,
            telephone_number = ?, region = ?, province = ?, city_municipality = ?,
            barangay_address = ?, house_address = ?, street = ?, subdivision = ?, zip_code = ?,
            blood_type = ?, weight = ?, height = ?, citizenship = ?, registered_voter = ?,
            voter_not_resident = ?, ethnicity = ?, position_in_household = ?,
            mother_maiden_name = ?, has_pet = ?, sectors = ?, sector_other = ?, profession = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $last_name, $first_name, $middle_name, $ext_name,
            $birthdate, $birth_place, $age, $gender,
            $civil_status, $highest_education, $profile_picture,
            $educational_status, $philsys_number, $email, $mobile_number,
            $telephone_number, $region, $province, $city_municipality,
            $barangay, $house_address, $street, $subdivision, $zip_code,
            $blood_type, $weight, $height, $citizenship, $registered_voter,
            $voter_not_resident, $ethnicity, $position_in_household,
            $mother_maiden_name, $has_pet, implode(',', $sectors), $sector_other,
            $profession, $id
        ]);
        
        if ($result) {
            logAudit($_SESSION['user_id'], 'UPDATE', 'individual_records', $id, 
                "Updated citizen: $first_name $last_name");
            $success = true;
            clearCache('dashboard_stats');
            
            // Refresh record
            $stmt = $pdo->prepare("SELECT * FROM individual_records WHERE id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch();
        } else {
            $errors[] = "Failed to update record. Please try again.";
        }
    }
}

// Decode sectors for display
$sector_list = !empty($record['sectors']) ? explode(',', $record['sectors']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Citizen - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .form-section-title {
            font-weight: 700;
            color: #0d6efd;
            font-size: 1.1rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .form-section-title i {
            margin-right: 10px;
        }
        .required-star {
            color: #dc3545;
        }
        .photo-upload-box {
            width: 150px;
            height: 150px;
            border: 3px solid #dee2e6;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
        }
        .photo-upload-box:hover {
            border-color: #0d6efd;
        }
        .photo-upload-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .sector-checkbox-group {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .sector-checkbox-group .form-check {
            padding: 8px 12px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        .sector-checkbox-group .form-check:hover {
            background: #e9ecef;
        }
        @media (max-width: 768px) {
            .sector-checkbox-group {
                grid-template-columns: repeat(2, 1fr);
            }
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
                        <i class="fas fa-user-edit text-warning"></i> Edit Citizen
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
                        <i class="fas fa-check-circle"></i> Citizen updated successfully!
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

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <!-- Profile Photo -->
                            <div class="text-center mb-4">
                                <div class="photo-upload-box" onclick="document.getElementById('profilePicture').click()">
                                    <?php if ($record['profile_picture']): ?>
                                        <img id="photoPreview" src="../../uploads/<?= $record['profile_picture'] ?>" alt="Profile">
                                    <?php else: ?>
                                        <img id="photoPreview" src="../../assets/images/default-avatar.png" alt="Default" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="profile_picture" id="profilePicture" accept="image/*" style="display: none;" onchange="previewImage(this)">
                                <small class="d-block text-muted mt-2">Click to change photo. JPG, PNG, GIF (Max 2MB)</small>
                                <?php if ($record['profile_picture']): ?>
                                    <small class="d-block text-muted">Current: <?= $record['profile_picture'] ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- ============================================ -->
                            <!-- PERSONAL INFORMATION -->
                            <!-- ============================================ -->
                            <div class="form-section-title">
                                <i class="fas fa-user"></i> Personal Information
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">First Name <span class="required-star">*</span></label>
                                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($record['first_name']) ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($record['middle_name']) ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Last Name <span class="required-star">*</span></label>
                                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($record['last_name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Extension Name</label>
                                    <input type="text" name="ext_name" class="form-control" value="<?= htmlspecialchars($record['ext_name']) ?>" placeholder="E.G. SR., JR., II, III.">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Birthdate <span class="required-star">*</span></label>
                                    <input type="date" name="birthdate" class="form-control" value="<?= $record['date_of_birth'] ?>" required onchange="calculateAge(this)">
                                    <small class="text-muted" id="ageDisplay">Age: <?= $record['age'] ?? '' ?> years</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Birth Place <span class="required-star">*</span></label>
                                    <input type="text" name="birth_place" class="form-control" value="<?= htmlspecialchars($record['place_of_birth']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender <span class="required-star">*</span></label>
                                    <select name="gender" class="form-select" required>
                                        <option value="">Select...</option>
                                        <option value="Male" <?= $record['sex'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= $record['sex'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= $record['sex'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
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
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Highest Educational Attainment</label>
                                    <select name="highest_education" class="form-select">
                                        <option value="">Select...</option>
                                        <option value="Elementary" <?= $record['highest_education'] == 'Elementary' ? 'selected' : '' ?>>Elementary</option>
                                        <option value="High School" <?= $record['highest_education'] == 'High School' ? 'selected' : '' ?>>High School</option>
                                        <option value="Vocational" <?= $record['highest_education'] == 'Vocational' ? 'selected' : '' ?>>Vocational</option>
                                        <option value="College" <?= $record['highest_education'] == 'College' ? 'selected' : '' ?>>College</option>
                                        <option value="Post Graduate" <?= $record['highest_education'] == 'Post Graduate' ? 'selected' : '' ?>>Post Graduate</option>
                                        <option value="Doctorate" <?= $record['highest_education'] == 'Doctorate' ? 'selected' : '' ?>>Doctorate</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Educational Status</label>
                                    <input type="text" name="educational_status" class="form-control" value="<?= htmlspecialchars($record['educational_status']) ?>" placeholder="e.g., Graduate, Undergraduate">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Profession / Occupation</label>
                                    <input type="text" name="profession" class="form-control" value="<?= htmlspecialchars($record['profession']) ?>" placeholder="e.g., Computer Programmer">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">PhilSys Number</label>
                                    <input type="text" name="philsys_number" class="form-control" value="<?= htmlspecialchars($record['philsys_number']) ?>" placeholder="e.g., 2312-2312-3123-1233">
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- CONTACT DETAILS -->
                            <!-- ============================================ -->
                            <div class="form-section-title mt-4">
                                <i class="fas fa-address-card"></i> Contact Details
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($record['email']) ?>" placeholder="email@example.com">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mobile Number <span class="required-star">*</span></label>
                                    <input type="text" name="mobile_number" class="form-control" value="<?= htmlspecialchars($record['mobile_number']) ?>" required placeholder="09158541583">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Telephone Number</label>
                                    <input type="text" name="telephone_number" class="form-control" value="<?= htmlspecialchars($record['telephone_number']) ?>" placeholder="(02) 123-4567">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Region <span class="required-star">*</span></label>
                                    <select name="region" class="form-select" required>
                                        <option value="">Select Region...</option>
                                        <?php foreach ($regions as $region): ?>
                                            <option value="<?= $region ?>" <?= $record['region'] == $region ? 'selected' : '' ?>><?= $region ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Province <span class="required-star">*</span></label>
                                    <select name="province" class="form-select" required>
                                        <option value="">Select Province...</option>
                                        <?php foreach ($provinces as $province): ?>
                                            <option value="<?= $province ?>" <?= $record['province'] == $province ? 'selected' : '' ?>><?= $province ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City / Municipality <span class="required-star">*</span></label>
                                    <input type="text" name="city_municipality" class="form-control" value="<?= htmlspecialchars($record['city_municipality']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Barangay <span class="required-star">*</span></label>
                                    <select name="barangay" class="form-select" required>
                                        <option value="">Select Barangay...</option>
                                        <?php foreach ($barangays as $brgy): ?>
                                            <option value="<?= $brgy ?>" <?= $record['barangay_address'] == $brgy ? 'selected' : '' ?>><?= $brgy ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">House / Block / Lot no. / Unit no. <span class="required-star">*</span></label>
                                    <input type="text" name="house_address" class="form-control" value="<?= htmlspecialchars($record['house_address']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Street <span class="required-star">*</span></label>
                                    <input type="text" name="street" class="form-control" value="<?= htmlspecialchars($record['street']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Subdivision / Village</label>
                                    <input type="text" name="subdivision" class="form-control" value="<?= htmlspecialchars($record['subdivision']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Zip Code</label>
                                    <input type="text" name="zip_code" class="form-control" value="<?= htmlspecialchars($record['zip_code']) ?>" placeholder="1105">
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- IDENTITY INFORMATION -->
                            <!-- ============================================ -->
                            <div class="form-section-title mt-4">
                                <i class="fas fa-id-card"></i> Identity Information
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Blood Type</label>
                                    <select name="blood_type" class="form-select">
                                        <option value="">Select...</option>
                                        <?php foreach ($blood_types as $type): ?>
                                            <option value="<?= $type ?>" <?= $record['blood_type'] == $type ? 'selected' : '' ?>><?= $type ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Weight (Kilograms)</label>
                                    <input type="number" name="weight" class="form-control" value="<?= $record['weight'] ?>" step="0.1" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Height (Feet)</label>
                                    <input type="text" name="height" class="form-control" value="<?= htmlspecialchars($record['height']) ?>" placeholder="e.g., 5'8\", Medium">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Citizenship <span class="required-star">*</span></label>
                                    <input type="text" name="citizenship" class="form-control" value="<?= htmlspecialchars($record['citizenship']) ?>" required placeholder="e.g., Filipino">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ethnicity</label>
                                    <select name="ethnicity" class="form-select">
                                        <option value="">Select...</option>
                                        <?php foreach ($ethnicities as $ethnicity): ?>
                                            <option value="<?= $ethnicity ?>" <?= $record['ethnicity'] == $ethnicity ? 'selected' : '' ?>><?= $ethnicity ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="registered_voter" id="registeredVoter" class="form-check-input" value="1" <?= $record['registered_voter'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="registeredVoter">Registered Resident Voter</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="voter_not_resident" id="voterNotResident" class="form-check-input" value="1" <?= $record['voter_not_resident'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="voterNotResident">Registered voter, but not a resident</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="has_pet" id="hasPet" class="form-check-input" value="1" <?= $record['has_pet'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="hasPet">Do you have pet?</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Position in Household</label>
                                    <select name="position_in_household" class="form-select">
                                        <option value="">Select...</option>
                                        <?php foreach ($positions as $pos): ?>
                                            <option value="<?= $pos ?>" <?= $record['position_in_household'] == $pos ? 'selected' : '' ?>><?= $pos ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mother's Maiden Name</label>
                                    <input type="text" name="mother_maiden_name" class="form-control" value="<?= htmlspecialchars($record['mother_maiden_name']) ?>" placeholder="Mother's maiden name">
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- SECTORAL INFORMATION -->
                            <!-- ============================================ -->
                            <div class="form-section-title mt-4">
                                <i class="fas fa-building"></i> Sectoral Information
                            </div>
                            <p class="text-muted mb-3">
                                Please check a sector, You may select more than one as applicable
                            </p>
                            <div class="sector-checkbox-group">
                                <?php
                                $sector_options = [
                                    'Unemployed', 'Overseas Filipino Worker (OFW)', 
                                    'Person with Disabilities (PWD)', 'Out of School Children (OSC)',
                                    'Out of School Youth (OSY)', 'Student', 'Employed',
                                    'Senior Citizen (SC)', 'Solo Parent', 'Indigenous People (IP)',
                                    'Pregnant', 'Migrant'
                                ];
                                foreach ($sector_options as $option):
                                    $checked = in_array($option, $sector_list) ? 'checked' : '';
                                ?>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sector<?= str_replace(' ', '', $option) ?>" value="<?= $option ?>" class="form-check-input" <?= $checked ?>>
                                        <label class="form-check-label" for="sector<?= str_replace(' ', '', $option) ?>"><?= $option ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Other Sector</label>
                                <input type="text" name="sector_other" class="form-control" value="<?= htmlspecialchars($record['sector_other']) ?>" placeholder="Specify other sector if not listed above">
                            </div>

                            <!-- Save Button -->
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-warning btn-lg px-5">
                                    <i class="fas fa-save"></i> Update Citizen
                                </button>
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
                    document.getElementById('photoPreview').src = e.target.result;
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
            var dobInput = document.querySelector('input[name="birthdate"]');
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