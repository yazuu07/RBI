<?php
// includes/sidebar.php - COMPLETE VERSION with all navigation items
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['PHP_SELF'];
$user_role = $_SESSION['role'] ?? 'enumerator';

// Set your base path - CHANGE THIS to match your folder structure
$base_path = '/dashboard/RBI';  // Adjust this to your actual path
?>
<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 0;
    width: 16.66666667%;
    background: #0d2214;
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}
.sidebar-sticky {
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}
.sidebar-brand {
    padding: 20px 0;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.sidebar-brand .brand-icon {
    font-size: 2rem;
    color: #e1e2e7; 
}
.sidebar-brand .brand-text {
    color: white;
    font-size: 1rem;
    font-weight: 600;
    margin-top: 5px;
}
.sidebar-brand .brand-sub {
    color: #8892b0;
    font-size: 0.7rem;
}
.sidebar .nav-link {
    color: #8892b0 !important;
    padding: 10px 20px;
    border-radius: 8px;
    margin: 2px 10px;
    transition: all 0.3s;
}
.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.05);
    color: white !important;
}
.sidebar .nav-link.active {
    background: linear-gradient(90deg, #15844d 0%, #4ba27c 100%);
    color: white !important;
}
.sidebar .nav-link i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}
.sidebar .collapse .nav-link {
    padding-left: 45px;
    font-size: 0.85rem;
}
.sidebar .collapse .nav-link:hover {
    background: rgba(255,255,255,0.03);
}
.sidebar .nav-item .collapse-icon {
    float: right;
    transition: transform 0.3s;
}
.sidebar .nav-item .collapsed .collapse-icon {
    transform: rotate(-90deg);
}
.sidebar-user-info {
    position: relative;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 15px;
    border-top: 1px solid rgba(255,255,255,0.05);
    background: rgba(0,0,0,0.2);
}
.sidebar-user-info .user-name {
    color: white;
    font-size: 0.9rem;
    font-weight: 600;
}
.sidebar-user-info .user-role {
    font-size: 0.7rem;
    color: #8892b0;
}
.sidebar::-webkit-scrollbar {
    width: 4px;
}
.sidebar::-webkit-scrollbar-track {
    background: #1a1a2e;
}
.sidebar::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 10px;
}
.sidebar .brand-icon {
  text-align: center;
  padding: 20px;
}

.sidebar .brand-icon img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
}
.main-content {
    margin-left: 16.66666667%;
    padding: 0;
}
@media (max-width: 767.98px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        min-height: auto;
    }
    .sidebar-sticky {
        height: auto;
        position: relative;
    }
    .main-content {
        margin-left: 0;
    }
}
</style>

<nav class="col-md-2 d-md-block sidebar">
    <div class="sidebar-sticky">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="brand-icon">
                 <img src="http://localhost/dashboard/RBI/assets/images/Logo.png" alt="Landmark Icon">
            </div>
            <div class="brand-text">RBIS</div>
            <div class="brand-sub">Barangay Registry</div>
        </div>
        
        <!-- Navigation -->
        <ul class="nav flex-column mt-3">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="<?= $base_path ?>/dashboard.php">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>
        
            
            <!-- INHABITANTS -->
            <li class="nav-item">
                <a class="nav-link text-white" data-bs-toggle="collapse" href="#inhabitantsMenu" role="button" 
                   aria-expanded="<?= strpos($current_path, 'inhabitants') !== false ? 'true' : 'false' ?>">
                    <i class="fas fa-users"></i> Inhabitants
                    <i class="fas fa-chevron-down collapse-icon"></i>
                </a>
                <div class="collapse <?= strpos($current_path, 'inhabitants') !== false ? 'show' : '' ?>" id="inhabitantsMenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_path, 'citizens') !== false ? 'active' : '' ?>" href="<?= $base_path ?>/inhabitants/citizens/index.php">
                                <i class="fas fa-user"></i> Barangay Citizens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_path, 'households') !== false ? 'active' : '' ?>" href="<?= $base_path ?>/inhabitants/households/index.php">
                                <i class="fas fa-home"></i> Barangay Households
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            
            <!-- DEMOGRAPHIC -->
            <li class="nav-item">
                <a class="nav-link text-white" data-bs-toggle="collapse" href="#demographicMenu" role="button"
                   aria-expanded="<?= strpos($current_path, 'demographic') !== false ? 'true' : 'false' ?>">
                    <i class="fas fa-chart-bar"></i> Demographic
                    <i class="fas fa-chevron-down collapse-icon"></i>
                </a>
                <div class="collapse <?= strpos($current_path, 'demographic') !== false ? 'show' : '' ?>" id="demographicMenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_path, 'populations') !== false ? 'active' : '' ?>" href="<?= $base_path ?>/demographic/populations.php">
                                <i class="fas fa-users"></i> Populations Demographic
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_path, 'households_demo') !== false ? 'active' : '' ?>" href="<?= $base_path ?>/demographic/households.php">
                                <i class="fas fa-home"></i> Households Demographic
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            
            <!-- CERTIFICATION -->
            <?php if (hasPermission('certification', 'view')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_path, 'certification') !== false ? 'active' : '' ?>" href="<?= $base_path ?>/certification/index.php">
                        <i class="fas fa-certificate"></i> Certification
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- EXTRAS -->
            <?php if (hasPermission('extras', 'view')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_path, 'extras') !== false ? 'active' : '' ?>" href="<?= $base_path ?>/extras/vehicles.php">
                        <i class="fas fa-car"></i> Extras
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- REPORTS -->
            <?php if (hasPermission('reports', 'view')): ?>
                <li class="nav-item">
                    <a class="nav-link text-white" data-bs-toggle="collapse" href="#reportsMenu" role="button"
                       aria-expanded="<?= strpos($current_path, 'reports') !== false ? 'true' : 'false' ?>">
                        <i class="fas fa-file-alt"></i> Reports
                        <i class="fas fa-chevron-down collapse-icon"></i>
                    </a>
                    <div class="collapse <?= strpos($current_path, 'reports') !== false ? 'show' : '' ?>" id="reportsMenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $base_path ?>/reports/household.php">
                                    <i class="fas fa-home"></i> Household
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $base_path ?>/reports/voters_list.php">
                                    <i class="fas fa-vote-yea"></i> Voters List
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $base_path ?>/reports/population_by_age.php">
                                    <i class="fas fa-calendar-alt"></i> Population By Age
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $base_path ?>/reports/population_by_sector.php">
                                    <i class="fas fa-building"></i> Population By Sector
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $base_path ?>/reports/population_by_street.php">
                                    <i class="fas fa-road"></i> Population By Street
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            <?php endif; ?>
            
            <!-- SYSTEM -->
            <?php if (hasPermission('system', 'view')): ?>
                <li class="nav-item">
                    <a class="nav-link text-white" data-bs-toggle="collapse" href="#systemMenu" role="button"
                       aria-expanded="<?= strpos($current_path, 'system') !== false ? 'true' : 'false' ?>">
                        <i class="fas fa-cogs"></i> System
                        <i class="fas fa-chevron-down collapse-icon"></i>
                    </a>
                    <div class="collapse <?= strpos($current_path, 'system') !== false ? 'show' : '' ?>" id="systemMenu">
                        <ul class="nav flex-column ms-3">
                            <!-- Users List -->
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_path, 'users.php') !== false && strpos($current_path, 'groups') === false && strpos($current_path, 'settings') === false ? 'active' : '' ?>" href="<?= $base_path ?>/system/users.php">
                                    <i class="fas fa-users-cog"></i> Users List
                                </a>
                            </li>
                            
                            <!-- Users Group (SuperAdmin/Admin only) -->
                            <?php if (hasPermission('system', 'manage')): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= strpos($current_path, 'groups.php') !== false ? 'active' : '' ?>" href="<?= $base_path ?>/system/groups.php">
                                        <i class="fas fa-user-tag"></i> Users Group
                                    </a>
                                </li>
                                
                                <!-- Settings (SuperAdmin/Admin only) -->
                                <li class="nav-item">
                                    <a class="nav-link <?= strpos($current_path, 'settings.php') !== false ? 'active' : '' ?>" href="<?= $base_path ?>/system/settings.php">
                                        <i class="fas fa-sliders-h"></i> Settings
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Database (SuperAdmin only) -->
                            <?php if (hasPermission('system', 'sql_execute')): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= strpos($current_path, 'database') !== false ? 'active' : '' ?>" href="<?= $base_path ?>/system/database/index.php">
                                        <i class="fas fa-database"></i> Database
                                    </a>
                                </li>
                                
                                <!-- Audit Trails (SuperAdmin only) -->
                                <li class="nav-item">
                                    <a class="nav-link <?= strpos($current_path, 'audit.php') !== false ? 'active' : '' ?>" href="<?= $base_path ?>/system/audit.php">
                                        <i class="fas fa-clipboard-list"></i> Audit Trails
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
        
        <!-- User Info -->
        <div class="sidebar-user-info">
            <div class="text-center">
                <div class="user-name"><?= $_SESSION['full_name'] ?? 'User' ?></div>
                <div class="user-role">
                    <span class="badge bg-<?= 
                        $user_role == 'superadmin' ? 'danger' : 
                        ($user_role == 'admin' ? 'primary' : 
                        ($user_role == 'editor' ? 'success' : 'warning')) 
                    ?>">
                        <?= strtoupper($user_role) ?>
                    </span>
                </div>
                <div class="mt-1">
                    <a href="<?= $base_path ?>/logout.php" class="text-white-50 text-decoration-none small">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>