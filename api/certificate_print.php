<?php
require_once '../config.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('Invalid certificate ID');
}

$pdo = getDB();

// Get certificate details with resident info
$stmt = $pdo->prepare("
    SELECT c.*, 
           i.last_name, i.first_name, i.middle_name, i.ext_name,
           i.sex, i.date_of_birth, i.place_of_birth,
           i.civil_status, i.highest_education,
           u.full_name as issued_by_name
    FROM certificates c 
    LEFT JOIN individual_records i ON c.resident_id = i.id 
    LEFT JOIN users u ON c.issued_by = u.id 
    WHERE c.id = ?
");
$stmt->execute([$id]);
$certificate = $stmt->fetch();

if (!$certificate) {
    die('Certificate not found');
}

// Format full name
$full_name = $certificate['last_name'] . ', ' . $certificate['first_name'];
if ($certificate['middle_name']) {
    $full_name .= ' ' . $certificate['middle_name'][0] . '.';
}
if ($certificate['ext_name']) {
    $full_name .= ' ' . $certificate['ext_name'];
}

// Format date of birth
$dob = $certificate['date_of_birth'] ? date('M d, Y', strtotime($certificate['date_of_birth'])) : 'N/A';

// Calculate age
$age = $certificate['date_of_birth'] ? calculateAge($certificate['date_of_birth']) : 'N/A';

// Generate ID number if not exists
$id_number = $certificate['certificate_number'] ?: 'ID No: ' . date('Y-m-d') . '-' . str_pad($certificate['id'], 3, '0', STR_PAD_LEFT);

// Get address (using place of birth as address for demo)
$address = $certificate['place_of_birth'] ?: 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay ID - <?= $full_name ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', serif;
            background: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .certificate-container {
            background: white;
            width: 800px;
            max-width: 100%;
            border: 3px solid #1a3c6e;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            position: relative;
        }
        .certificate-container::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 2px solid #1a3c6e;
            border-radius: 5px;
            pointer-events: none;
        }
        
        /* Header */
        .header {
            text-align: center;
            border-bottom: 3px double #1a3c6e;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header .seal {
            font-size: 50px;
            color: #1a3c6e;
            display: block;
            margin-bottom: 5px;
        }
        .header h1 {
            font-size: 22px;
            color: #1a3c6e;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 18px;
            color: #1a3c6e;
            font-weight: normal;
            letter-spacing: 2px;
        }
        .header .subtitle {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
        
        /* Content */
        .content {
            padding: 10px 0;
        }
        
        /* ID Card Style */
        .id-card {
            border: 2px solid #1a3c6e;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
        }
        .id-card .id-title {
            background: #1a3c6e;
            color: white;
            text-align: center;
            padding: 8px;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            border-radius: 4px 4px 0 0;
            margin-top: -20px;
            margin-left: -20px;
            margin-right: -20px;
        }
        .id-card .id-body {
            padding: 20px 0 10px 0;
        }
        
        /* ID Fields */
        .id-row {
            display: flex;
            margin-bottom: 8px;
            padding: 4px 0;
            border-bottom: 1px dashed #e0e0e0;
        }
        .id-row:last-child {
            border-bottom: none;
        }
        .id-label {
            font-weight: bold;
            width: 120px;
            min-width: 120px;
            color: #1a3c6e;
            font-size: 13px;
            text-transform: uppercase;
        }
        .id-value {
            flex: 1;
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }
        
        /* Photo Placeholder */
        .photo-section {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 15px;
        }
        .photo-box {
            width: 120px;
            height: 140px;
            border: 2px solid #1a3c6e;
            border-radius: 5px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-box .no-photo {
            color: #999;
            font-size: 12px;
            text-align: center;
        }
        .photo-box .no-photo i {
            font-size: 40px;
            display: block;
            margin-bottom: 5px;
        }
        .id-details {
            flex: 1;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #1a3c6e;
            text-align: center;
        }
        .footer .note {
            font-size: 12px;
            color: #555;
            font-style: italic;
            margin-bottom: 10px;
        }
        .footer .return-note {
            font-size: 11px;
            color: #888;
            margin-bottom: 15px;
        }
        .footer .signature {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 10px;
        }
        .footer .signature .sign-line {
            flex: 1;
            border-top: 1px solid #333;
            margin-top: 30px;
            padding-top: 5px;
            text-align: center;
            font-size: 13px;
        }
        .footer .signature .sign-line .title {
            font-size: 11px;
            color: #666;
        }
        
        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            font-size: 100px;
            color: #1a3c6e;
            pointer-events: none;
            font-weight: bold;
            z-index: 0;
        }
        
        /* Print Button */
        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #1a3c6e;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: all 0.3s;
            z-index: 1000;
        }
        .print-btn:hover {
            background: #2a4c7e;
            transform: scale(1.05);
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 3px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-badge.issued {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-badge.expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Responsive */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .print-btn {
                display: none !important;
            }
            .certificate-container {
                border: 2px solid #1a3c6e;
                box-shadow: none;
                border-radius: 0;
                padding: 20px;
            }
            .certificate-container::before {
                display: none;
            }
            .id-card {
                border: 1px solid #1a3c6e;
            }
            .photo-box {
                border: 1px solid #1a3c6e;
            }
        }
        
        @media (max-width: 600px) {
            .photo-section {
                flex-direction: column;
                align-items: center;
            }
            .id-row {
                flex-direction: column;
                padding: 8px 0;
            }
            .id-label {
                width: 100%;
                margin-bottom: 2px;
            }
            .signature {
                flex-direction: column;
                gap: 20px;
            }
            .certificate-container {
                padding: 15px;
            }
            .id-card .id-title {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <!-- Watermark -->
        <div class="watermark">RBIS</div>
        
        <!-- Header -->
        <div class="header">
            <span class="seal">🏛️</span>
            <h1>Republic of the Philippines</h1>
            <h2>Barangay <?= htmlspecialchars($_SESSION['barangay_name'] ?? 'Santo Cristo') ?></h2>
            <div class="subtitle">Quezon City, Metro Manila</div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="id-card">
                <div class="id-title">
                    BARANGAY IDENTIFICATION
                    <span class="status-badge <?= strtolower($certificate['status']) ?> ms-2">
                        <?= $certificate['status'] ?>
                    </span>
                </div>
                <div class="id-body">
                    <div class="photo-section">
                        <div class="photo-box">
                            <?php 
                            // Try to get resident profile picture
                            $stmt = $pdo->prepare("SELECT profile_picture FROM individual_records WHERE id = ?");
                            $stmt->execute([$certificate['resident_id']]);
                            $resident = $stmt->fetch();
                            
                            if ($resident && $resident['profile_picture'] && file_exists('../uploads/' . $resident['profile_picture'])): 
                            ?>
                                <img src="../uploads/<?= $resident['profile_picture'] ?>" alt="Photo">
                            <?php else: ?>
                                <div class="no-photo">
                                    <i>👤</i>
                                    No Photo
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="id-details">
                            <div class="id-row">
                                <span class="id-label">NAME</span>
                                <span class="id-value"><?= strtoupper($full_name) ?></span>
                            </div>
                            <div class="id-row">
                                <span class="id-label">ID NO.</span>
                                <span class="id-value"><?= $id_number ?></span>
                            </div>
                            <div class="id-row">
                                <span class="id-label">GENDER</span>
                                <span class="id-value"><?= $certificate['sex'] ?? 'N/A' ?></span>
                            </div>
                            <div class="id-row">
                                <span class="id-label">BIRTHDATE</span>
                                <span class="id-value"><?= $dob ?> (Age: <?= $age ?>)</span>
                            </div>
                            <div class="id-row">
                                <span class="id-label">CIVIL STATUS</span>
                                <span class="id-value"><?= $certificate['civil_status'] ?? 'N/A' ?></span>
                            </div>
                            <div class="id-row">
                                <span class="id-label">ADDRESS</span>
                                <span class="id-value"><?= strtoupper($address) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="note">
                "Holder is a bonafide constituent of this barangay and is entitled to all privilege and services holder may require"
            </div>
            
            <div class="return-note">
                ⚠️ If found, please return to the Barangay Secretariat, San Antonio Hall, Quezon City.
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 10px;">
                <div style="text-align: left;">
                    <div style="border-top: 1px solid #333; width: 150px; padding-top: 5px;">
                        <small><?= date('F d, Y', strtotime($certificate['created_at'])) ?></small>
                    </div>
                    <div style="font-size: 11px; color: #666;">Date Issued</div>
                </div>
                
                <div style="text-align: center;">
                    <div style="border-top: 1px solid #333; width: 200px; padding-top: 5px;">
                        <strong>HON. DANIEL BERROYA</strong>
                    </div>
                    <div style="font-size: 11px; color: #666;">Barangay Chairman</div>
                </div>
                
                <div style="text-align: right;">
                    <div style="border-top: 1px solid #333; width: 150px; padding-top: 5px;">
                        <small><?= $_SESSION['full_name'] ?? 'System' ?></small>
                    </div>
                    <div style="font-size: 11px; color: #666;">Issued By</div>
                </div>
            </div>
        </div>
    </div>
    
    <button class="print-btn" onclick="window.print()">
        🖨️ Print ID Card
    </button>
    
    <script>
        // Auto-print when loaded (optional)
        // window.onload = function() { setTimeout(function() { window.print(); }, 1000); };
    </script>
</body>
</html>