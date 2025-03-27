<?php
/**
 * House Rental Management System
 * Admin Reports
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'Reports';

// Initialize database
$db = new Database();

// Get report type from query string
$report_type = isset($_GET['type']) ? $_GET['type'] : 'occupancy';

// Get date range from query string
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month

// Generate report data based on type
$report_data = [];
$chart_data = [];

switch($report_type) {
    case 'occupancy':
        // Get occupancy data
        $db->query("SELECT c.name as category, 
                   COUNT(h.id) as total_houses,
                   SUM(CASE WHEN h.status = 1 THEN 1 ELSE 0 END) as occupied_houses,
                   SUM(CASE WHEN h.status = 0 THEN 1 ELSE 0 END) as vacant_houses
                   FROM categories c
                   LEFT JOIN houses h ON c.id = h.category_id
                   GROUP BY c.name");
        $report_data = $db->resultSet();
        
        // Format data for chart
        foreach($report_data as $row) {
            $chart_data[] = [
                'category' => $row['category'],
                'occupied' => (int)$row['occupied_houses'],
                'vacant' => (int)$row['vacant_houses']
            ];
        }
        break;
}