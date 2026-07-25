<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$pdo = getDB();

// Get demographic statistics
$stats = [];

// 1. Gender distribution (Individual)
$gender_stmt = $pdo->query("
    SELECT sex, COUNT(*) as count 
    FROM individual_records 
    GROUP BY sex
");
$stats['gender_individual'] = $gender_stmt->fetchAll();

// 2. Gender distribution (Household)
$gender_household_stmt = $pdo->query("
    SELECT sex, COUNT(*) as count 
    FROM household_records 
    GROUP BY sex
");
$stats['gender_household'] = $gender_household_stmt->fetchAll();

// 3. Civil status distribution
$civil_stmt = $pdo->query("
    SELECT civil_status, COUNT(*) as count 
    FROM individual_records 
    GROUP BY civil_status
");
$stats['civil_status'] = $civil_stmt->fetchAll();

// 4. Education distribution
$education_stmt = $pdo->query("
    SELECT highest_education, COUNT(*) as count 
    FROM individual_records 
    WHERE highest_education IS NOT NULL
    GROUP BY highest_education
");
$stats['education'] = $education_stmt->fetchAll();

// 5. Age distribution (Individual)
$age_stmt = $pdo->query("
    SELECT 
        CASE 
            WHEN age <= 17 THEN '0-17'
            WHEN age <= 25 THEN '18-25'
            WHEN age <= 35 THEN '26-35'
            WHEN age <= 45 THEN '36-45'
            WHEN age <= 55 THEN '46-55'
            WHEN age <= 65 THEN '56-65'
            ELSE '65+'
        END as age_group,
        COUNT(*) as count
    FROM individual_records
    WHERE age IS NOT NULL
    GROUP BY age_group
    ORDER BY 
        CASE 
            WHEN age_group = '0-17' THEN 1
            WHEN age_group = '18-25' THEN 2
            WHEN age_group = '26-35' THEN 3
            WHEN age_group = '36-45' THEN 4
            WHEN age_group = '46-55' THEN 5
            WHEN age_group = '56-65' THEN 6
            ELSE 7
        END
");
$stats['age_distribution'] = $age_stmt->fetchAll();

// 6. Citizenship distribution
$citizenship_stmt = $pdo->query("
    SELECT citizenship, COUNT(*) as count 
    FROM household_records 
    WHERE citizenship IS NOT NULL AND citizenship != ''
    GROUP BY citizenship
    ORDER BY count DESC
    LIMIT 10
");
$stats['citizenship'] = $citizenship_stmt->fetchAll();

// 7. Occupation distribution (Top 10)
$occupation_stmt = $pdo->query("
    SELECT occupation, COUNT(*) as count 
    FROM household_records 
    WHERE occupation IS NOT NULL AND occupation != ''
    GROUP BY occupation
    ORDER BY count DESC
    LIMIT 10
");
$stats['occupation'] = $occupation_stmt->fetchAll();

// 8. Total statistics
$total_stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM individual_records) as total_individuals,
        (SELECT COUNT(*) FROM household_records) as total_households,
        (SELECT COUNT(*) FROM individual_records WHERE sex = 'Male') as total_male,
        (SELECT COUNT(*) FROM individual_records WHERE sex = 'Female') as total_female,
        (SELECT COUNT(*) FROM individual_records WHERE DATE_FORMAT(date_of_birth, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')) as birthday_today
");
$stats['totals'] = $total_stmt->fetch();

// 9. Monthly registration trend (last 12 months)
$monthly_stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total
    FROM individual_records 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$stats['monthly_trend'] = $monthly_stmt->fetchAll();

echo json_encode($stats);
?>