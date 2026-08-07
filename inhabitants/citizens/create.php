<?php
require_once '../../config.php';
requireLogin();
requirePermission('inhabitants', 'add');

$pdo = getDB();
$errors = [];
$success = false;
$record_id = null;

// Get existing data for dropdowns
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Personal Information
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
    
    // Step 2: Contact Details
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
    
    // Step 3: Identity Information
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
    
    // Step 4: Sectoral Information
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
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $profile_picture = uploadFile($_FILES['profile_picture']);
        if (!$profile_picture) {
            $errors[] = "Invalid file format. Only JPG, PNG, GIF allowed (max 2MB).";
        }
    }
    
    // Save to database
    if (empty($errors)) {
        // Check if we need to add new columns first
        try {
            // Check if columns exist, add if not
            $columns_to_add = [
                'educational_status', 'philsys_number', 'email', 'mobile_number', 
                'telephone_number', 'region', 'province', 'city_municipality', 
                'barangay_address', 'house_address', 'street', 'subdivision', 'zip_code',
                'blood_type', 'weight', 'height', 'citizenship', 'registered_voter',
                'voter_not_resident', 'ethnicity', 'position_in_household', 
                'mother_maiden_name', 'has_pet', 'sectors', 'sector_other'
            ];
            
            // Check existing columns
            $stmt = $pdo->query("SHOW COLUMNS FROM individual_records");
            $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($columns_to_add as $col) {
                if (!in_array($col, $existing_columns)) {
                    // Add column with appropriate type
                    $type = 'TEXT';
                    if (in_array($col, ['registered_voter', 'voter_not_resident', 'has_pet'])) {
                        $type = 'TINYINT(1) DEFAULT 0';
                    } elseif (in_array($col, ['weight'])) {
                        $type = 'DECIMAL(5,2) NULL';
                    } elseif (in_array($col, ['mobile_number', 'telephone_number', 'zip_code', 'philsys_number'])) {
                        $type = 'VARCHAR(50) NULL';
                    } elseif (in_array($col, ['blood_type', 'gender', 'civil_status', 'educational_status', 'ethnicity', 'position_in_household', 'citizenship'])) {
                        $type = 'VARCHAR(50) NULL';
                    } elseif (in_array($col, ['sectors'])) {
                        $type = 'TEXT NULL';
                    } else {
                        $type = 'VARCHAR(255) NULL';
                    }
                    
                    $pdo->exec("ALTER TABLE individual_records ADD COLUMN $col $type");
                }
            }
        } catch (PDOException $e) {
            // Columns may already exist, continue
        }
        
        $sql = "INSERT INTO individual_records (
            last_name, first_name, middle_name, ext_name, 
            date_of_birth, place_of_birth, age, sex, 
            civil_status, highest_education, profile_picture, created_by,
            educational_status, philsys_number, email, mobile_number, 
            telephone_number, region, province, city_municipality, 
            barangay_address, house_address, street, subdivision, zip_code,
            blood_type, weight, height, citizenship, registered_voter,
            voter_not_resident, ethnicity, position_in_household, 
            mother_maiden_name, has_pet, sectors, sector_other, profession
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $last_name, $first_name, $middle_name, $ext_name,
            $birthdate, $birth_place, $age, $gender,
            $civil_status, $highest_education, $profile_picture, $_SESSION['user_id'],
            $educational_status, $philsys_number, $email, $mobile_number,
            $telephone_number, $region, $province, $city_municipality,
            $barangay, $house_address, $street, $subdivision, $zip_code,
            $blood_type, $weight, $height, $citizenship, $registered_voter,
            $voter_not_resident, $ethnicity, $position_in_household,
            $mother_maiden_name, $has_pet, implode(',', $sectors), $sector_other,
            $profession
        ]);
        
        if ($result) {
            $record_id = $pdo->lastInsertId();
            logAudit($_SESSION['user_id'], 'CREATE', 'individual_records', $record_id, 
                "Added citizen: $first_name $last_name");
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
    <title>Add Citizen - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .step-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .step-progress::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 5%;
            right: 5%;
            height: 3px;
            background: #e9ecef;
            z-index: 0;
        }
        .step-progress .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .step-progress .step .circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s;
            border: 3px solid #e9ecef;
        }
        .step-progress .step.active .circle {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .step-progress .step.completed .circle {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        .step-progress .step .label {
            font-size: 0.7rem;
            margin-top: 5px;
            color: #6c757d;
            text-align: center;
        }
        .step-progress .step.active .label {
            color: #0d6efd;
            font-weight: 600;
        }
        .step-progress .step.completed .label {
            color: #28a745;
            font-weight: 600;
        }
        .step-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        .step-content.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
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
            border: 3px dashed #dee2e6;
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            margin: 0 auto;
            overflow: hidden;
        }
        .photo-upload-box:hover {
            border-color: #0d6efd;
            background: #f8f9fa;
        }
        .photo-upload-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-upload-box .placeholder {
            color: #6c757d;
            text-align: center;
        }
        .photo-upload-box .placeholder i {
            font-size: 40px;
            display: block;
            margin-bottom: 5px;
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
        .sector-checkbox-group .form-check input:checked + label {
            color: #0d6efd;
            font-weight: 600;
        }
        .sector-checkbox-group .form-check input:checked ~ .sector-checkbox-group .form-check {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        .step-nav-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
        }
        .profile-preview-sm {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }
        @media (max-width: 768px) {
            .sector-checkbox-group {
                grid-template-columns: repeat(2, 1fr);
            }
            .step-progress .step .label {
                font-size: 0.6rem;
            }
            .step-progress .step .circle {
                width: 30px;
                height: 30px;
                font-size: 12px;
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
                        <i class="fas fa-user-plus text-success"></i> Add New Citizen
                    </h1>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show slide-down">
                        <i class="fas fa-check-circle"></i> Citizen added successfully!
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
                        <form method="POST" enctype="multipart/form-data" id="citizenForm">
                            <!-- Step Progress -->
                            <div class="step-progress" id="stepProgress">
                                <div class="step active" data-step="1">
                                    <div class="circle">1</div>
                                    <div class="label">Personal Info</div>
                                </div>
                                <div class="step" data-step="2">
                                    <div class="circle">2</div>
                                    <div class="label">Contact Details</div>
                                </div>
                                <div class="step" data-step="3">
                                    <div class="circle">3</div>
                                    <div class="label">Identity Info</div>
                                </div>
                                <div class="step" data-step="4">
                                    <div class="circle">4</div>
                                    <div class="label">Sectoral Info</div>
                                </div>
                                <div class="step" data-step="5">
                                    <div class="circle">5</div>
                                    <div class="label">Profile Photo</div>
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- STEP 1: PERSONAL INFORMATION -->
                            <!-- ============================================ -->
                            <div class="step-content active" data-step="1">
                                <div class="form-section-title">
                                    <i class="fas fa-user"></i> Personal Information
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">First Name <span class="required-star">*</span></label>
                                        <input type="text" name="first_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" name="middle_name" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Last Name <span class="required-star">*</span></label>
                                        <input type="text" name="last_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Extension Name</label>
                                        <input type="text" name="ext_name" class="form-control" placeholder="E.G. SR., JR., II, III.">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Birthdate <span class="required-star">*</span></label>
                                        <input type="date" name="birthdate" class="form-control" required onchange="calculateAge(this)">
                                        <small class="text-muted" id="ageDisplay">Age: </small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Birth Place <span class="required-star">*</span></label>
                                        <input type="text" name="birth_place" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Gender <span class="required-star">*</span></label>
                                        <select name="gender" class="form-select" required>
                                            <option value="">Select...</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
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
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Highest Educational Attainment</label>
                                        <select name="highest_education" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Elementary">Elementary</option>
                                            <option value="High School">High School</option>
                                            <option value="Vocational">Vocational</option>
                                            <option value="College">College</option>
                                            <option value="Post Graduate">Post Graduate</option>
                                            <option value="Doctorate">Doctorate</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Educational Status</label>
                                        <input type="text" name="educational_status" class="form-control" placeholder="e.g., Graduate, Undergraduate">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Profession / Occupation</label>
                                        <input type="text" name="profession" class="form-control" placeholder="e.g., Computer Programmer">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">PhilSys Number</label>
                                        <input type="text" name="philsys_number" class="form-control" placeholder="e.g., 2312-2312-3123-1233">
                                    </div>
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- STEP 2: CONTACT DETAILS -->
                            <!-- ============================================ -->
                            <div class="step-content" data-step="2">
                                <div class="form-section-title">
                                    <i class="fas fa-address-card"></i> Contact Details
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" class="form-control" placeholder="email@example.com">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Mobile Number <span class="required-star">*</span></label>
                                        <input type="text" name="mobile_number" class="form-control" required placeholder="09158541583">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Telephone Number</label>
                                        <input type="text" name="telephone_number" class="form-control" placeholder="(02) 123-4567">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Region <span class="required-star">*</span></label>
                                        <select name="region" class="form-select" required>
                                            <option value="">Select Region...</option>
                                            <?php foreach ($regions as $region): ?>
                                                <option value="<?= $region ?>"><?= $region ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Province <span class="required-star">*</span></label>
                                        <select name="province" class="form-select" required>
                                            <option value="">Select Province...</option>
                                            <?php foreach ($provinces as $province): ?>
                                                <option value="<?= $province ?>"><?= $province ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">City / Municipality <span class="required-star">*</span></label>
                                        <input type="text" name="city_municipality" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Barangay <span class="required-star">*</span></label>
                                        <select name="barangay" class="form-select" required>
                                            <option value="">Select Barangay...</option>
                                            <?php foreach ($barangays as $brgy): ?>
                                                <option value="<?= $brgy ?>"><?= $brgy ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">House / Block / Lot no. / Unit no. <span class="required-star">*</span></label>
                                        <input type="text" name="house_address" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Street <span class="required-star">*</span></label>
                                        <input type="text" name="street" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Subdivision / Village</label>
                                        <input type="text" name="subdivision" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Zip Code</label>
                                        <input type="text" name="zip_code" class="form-control" placeholder="1105">
                                    </div>
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- STEP 3: IDENTITY INFORMATION -->
                            <!-- ============================================ -->
                            <div class="step-content" data-step="3">
                                <div class="form-section-title">
                                    <i class="fas fa-id-card"></i> Identity Information
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Blood Type</label>
                                        <select name="blood_type" class="form-select">
                                            <option value="">Select...</option>
                                            <?php foreach ($blood_types as $type): ?>
                                                <option value="<?= $type ?>"><?= $type ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Weight (Kilograms)</label>
                                        <input type="number" name="weight" class="form-control" step="0.1" min="0">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Height (Feet)</label>
                                        <input type="text" name="height" class="form-control" placeholder="e.g., 5'8\", Medium">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Citizenship <span class="required-star">*</span></label>
                                        <input type="text" name="citizenship" class="form-control" required placeholder="e.g., Filipino">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Ethnicity</label>
                                        <select name="ethnicity" class="form-select">
                                            <option value="">Select...</option>
                                            <?php foreach ($ethnicities as $ethnicity): ?>
                                                <option value="<?= $ethnicity ?>"><?= $ethnicity ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="registered_voter" id="registeredVoter" class="form-check-input" value="1">
                                            <label class="form-check-label" for="registeredVoter">Registered Resident Voter</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="voter_not_resident" id="voterNotResident" class="form-check-input" value="1">
                                            <label class="form-check-label" for="voterNotResident">Registered voter, but not a resident</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="has_pet" id="hasPet" class="form-check-input" value="1">
                                            <label class="form-check-label" for="hasPet">Do you have pet?</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Position in Household</label>
                                        <select name="position_in_household" class="form-select">
                                            <option value="">Select...</option>
                                            <?php foreach ($positions as $pos): ?>
                                                <option value="<?= $pos ?>"><?= $pos ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Mother's Maiden Name</label>
                                        <input type="text" name="mother_maiden_name" class="form-control" placeholder="Mother's maiden name">
                                    </div>
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- STEP 4: SECTORAL INFORMATION -->
                            <!-- ============================================ -->
                            <div class="step-content" data-step="4">
                                <div class="form-section-title">
                                    <i class="fas fa-building"></i> Sectoral Information
                                </div>
                                <p class="text-muted mb-3">
                                    Please check a sector, You may select more than one as applicable
                                </p>
                                <div class="sector-checkbox-group">
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorUnemployed" value="Unemployed" class="form-check-input">
                                        <label class="form-check-label" for="sectorUnemployed">Unemployed</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorOFW" value="Overseas Filipino Worker (OFW)" class="form-check-input">
                                        <label class="form-check-label" for="sectorOFW">Overseas Filipino Worker (OFW)</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorPWD" value="Person with Disabilities (PWD)" class="form-check-input">
                                        <label class="form-check-label" for="sectorPWD">Person with Disabilities (PWD)</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorOSC" value="Out of School Children (OSC)" class="form-check-input">
                                        <label class="form-check-label" for="sectorOSC">Out of School Children (OSC)</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorOSY" value="Out of School Youth (OSY)" class="form-check-input">
                                        <label class="form-check-label" for="sectorOSY">Out of School Youth (OSY)</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorStudent" value="Student" class="form-check-input">
                                        <label class="form-check-label" for="sectorStudent">Student</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorEmployed" value="Employed" class="form-check-input">
                                        <label class="form-check-label" for="sectorEmployed">Employed</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorSC" value="Senior Citizen (SC)" class="form-check-input">
                                        <label class="form-check-label" for="sectorSC">Senior Citizen (SC)</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorSoloParent" value="Solo Parent" class="form-check-input">
                                        <label class="form-check-label" for="sectorSoloParent">Solo Parent</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorIP" value="Indigenous People (IP)" class="form-check-input">
                                        <label class="form-check-label" for="sectorIP">Indigenous People (IP)</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorPregnant" value="Pregnant" class="form-check-input">
                                        <label class="form-check-label" for="sectorPregnant">Pregnant</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="sectors[]" id="sectorMigrant" value="Migrant" class="form-check-input">
                                        <label class="form-check-label" for="sectorMigrant">Migrant</label>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">Other Sector</label>
                                    <input type="text" name="sector_other" class="form-control" placeholder="Specify other sector if not listed above">
                                </div>
                            </div>

                            <!-- ============================================ -->
                            <!-- STEP 5: PROFILE PHOTO -->
                            <!-- ============================================ -->
                            <div class="step-content" data-step="5">
                                <div class="form-section-title">
                                    <i class="fas fa-camera"></i> Profile Photo
                                </div>
                                <div class="text-center">
                                    <div class="photo-upload-box" onclick="document.getElementById('profilePicture').click()">
                                        <img id="photoPreview" src="../../assets/images/default-avatar.png" alt="Profile Photo" style="display: block; width: 100%; height: 100%; object-fit: cover;">
                                        <div class="placeholder" id="photoPlaceholder">
                                            <i class="fas fa-plus"></i>
                                            <small>Upload Photo</small>
                                        </div>
                                    </div>
                                    <input type="file" name="profile_picture" id="profilePicture" accept="image/*" style="display: none;" onchange="previewImage(this)">
                                    <small class="d-block text-muted mt-2">JPG, PNG, GIF (Max 2MB)</small>
                                </div>
                            </div>

                            <!-- Navigation Buttons -->
                            <div class="step-nav-buttons">
                                <button type="button" class="btn btn-secondary" id="prevStep" style="display: none;">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </button>
                                <div>
                                    <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                    <button type="button" class="btn btn-primary" id="nextStep">
                                        Next <i class="fas fa-arrow-right"></i>
                                    </button>
                                    <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                                        <i class="fas fa-save"></i> Save Citizen
                                    </button>
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
        let currentStep = 1;
        const totalSteps = 5;

        // Show/hide steps
        function updateSteps() {
            // Update step content
            document.querySelectorAll('.step-content').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.add('active');

            // Update progress
            document.querySelectorAll('.step-progress .step').forEach((el, index) => {
                const stepNum = index + 1;
                el.classList.remove('active', 'completed');
                if (stepNum === currentStep) {
                    el.classList.add('active');
                } else if (stepNum < currentStep) {
                    el.classList.add('completed');
                }
            });

            // Update buttons
            document.getElementById('prevStep').style.display = currentStep > 1 ? 'inline-block' : 'none';
            
            if (currentStep === totalSteps) {
                document.getElementById('nextStep').style.display = 'none';
                document.getElementById('submitBtn').style.display = 'inline-block';
            } else {
                document.getElementById('nextStep').style.display = 'inline-block';
                document.getElementById('submitBtn').style.display = 'none';
            }
        }

        // Next step
        document.getElementById('nextStep').addEventListener('click', function() {
            // Validate current step
            const currentContent = document.querySelector(`.step-content[data-step="${currentStep}"]`);
            const inputs = currentContent.querySelectorAll('input[required], select[required]');
            let valid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    valid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!valid) {
                alert('Please fill in all required fields before proceeding.');
                return;
            }

            if (currentStep < totalSteps) {
                currentStep++;
                updateSteps();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        // Previous step
        document.getElementById('prevStep').addEventListener('click', function() {
            if (currentStep > 1) {
                currentStep--;
                updateSteps();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        // Calculate age
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

        // Preview image
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                    document.getElementById('photoPreview').style.display = 'block';
                    document.getElementById('photoPlaceholder').style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Initialize
        updateSteps();
    </script>
</body>
</html>