<?php
require_once '../db.php';

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchBy = isset($_GET['searchBy']) ? $_GET['searchBy'] : 'all';
$filterType = isset($_GET['filterType']) ? $_GET['filterType'] : 'all';
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'user_type';
$sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'ASC';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$usersPerPage = 10;
$offset = ($page - 1) * $usersPerPage;

// Build WHERE clause
$whereClause = [];
$params = [];

if ($search !== '') {
    switch($searchBy) {
        case 'user_id':
            $whereClause[] = "user_id = :search";
            $params[':search'] = $search;
            break;
        case 'first_name':
            $whereClause[] = "first_name LIKE :search";
            $params[':search'] = "%$search%";
            break;
        case 'middle_name':
            $whereClause[] = "middle_name LIKE :search";
            $params[':search'] = "%$search%";
            break;
        case 'last_name':
            $whereClause[] = "last_name LIKE :search";
            $params[':search'] = "%$search%";
            break;
        case 'email':
            $whereClause[] = "email LIKE :search";
            $params[':search'] = "%$search%";
            break;
        case 'all':
            $whereClause[] = "(first_name LIKE :search OR middle_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR user_id = :search_id)";
            $params[':search'] = "%$search%";
            $params[':search_id'] = $search;
            break;
    }
}

if ($filterType !== 'all') {
    $whereClause[] = "user_type = :user_type";
    $params[':user_type'] = $filterType;
}

$whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';

// Get total count for pagination
$countSQL = "SELECT COUNT(*) as total FROM users $whereSQL";
$stmt = $pdo->prepare($countSQL);
$stmt->execute($params);
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalUsers / $usersPerPage);

// Fetch users
$sql = "SELECT * FROM users $whereSQL ORDER BY 
        CASE 
            WHEN user_type = 'admin' AND email = 'admin@gmail.com' THEN 1
            WHEN user_type = 'admin' THEN 2
            WHEN user_type = 'staff' THEN 3
            ELSE 4
        END,
        $sortBy $sortOrder 
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $usersPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for super admin
$loggedInEmail = $_SESSION['email'] ?? '';
$isSuperAdmin = $loggedInEmail === 'admin@gmail.com';

// Prepare HTML response
$html = '';
if (count($users) > 0) {    foreach ($users as $user) {
        $html .= '<tr>';
        // Row number
        $html .= '<td class="text-center">' . ($offset + 1) . '</td>';
        
        // User ID
        $html .= '<td>' . htmlspecialchars($user['user_id']) . '</td>';
        
        // Names
        $html .= '<td>' . htmlspecialchars($user['first_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($user['middle_name'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($user['last_name']) . '</td>';
        
        // Email
        $html .= '<td>' . htmlspecialchars($user['email']) . '</td>';
        
        // User Type Badge with centered alignment
        $html .= '<td class="text-center">';
        if ($user['user_type'] === 'admin' && $user['email'] === 'admin@gmail.com') {
            $html .= '<span class="badge badge-danger">Super Admin</span>';
        } elseif ($user['user_type'] === 'admin') {
            $html .= '<span class="badge badge-warning">Admin</span>';
        } elseif ($user['user_type'] === 'staff') {
            $html .= '<span class="badge badge-info">Staff</span>';
        } else {
            $html .= '<span class="badge badge-secondary">Client</span>';
        }
        $html .= '</td>';        // Action Buttons
        $html .= '<td class="text-center">';
        
        // Super admin can edit/delete anyone except themselves
        // Regular admin can only edit/delete non-admin users
        if ($isSuperAdmin && $user['email'] !== 'admin@gmail.com') {
            // Super admin can edit/delete anyone except themselves
            $html .= '<button class="btn btn-sm btn-primary" onclick="editUser(' . htmlspecialchars(json_encode($user)) . ')"><i class="fas fa-edit"></i></button> ';
            $html .= '<button class="btn btn-sm btn-danger" onclick="deleteUser(' . $user['user_id'] . ')"><i class="fas fa-trash"></i></button> ';
        } elseif (!$isSuperAdmin && $user['user_type'] !== 'admin' && $user['email'] !== 'admin@gmail.com') {
            // Regular admin can only edit/delete non-admin users
            $html .= '<button class="btn btn-sm btn-primary" onclick="editUser(' . htmlspecialchars(json_encode($user)) . ')"><i class="fas fa-edit"></i></button> ';
            $html .= '<button class="btn btn-sm btn-danger" onclick="deleteUser(' . $user['user_id'] . ')"><i class="fas fa-trash"></i></button> ';
        }
        
        if ($user['user_type'] === 'user') {
            $html .= '<button class="btn btn-sm btn-warning" onclick="suspendUser(' . $user['user_id'] . ')"><i class="fas fa-ban"></i></button>';
        }        $html .= '</td>';
        $html .= '</tr>';
    }
} else {
    $html = '<tr><td colspan="7" class="text-center">No users found</td></tr>';
}

// Prepare pagination
$pagination = '';
if ($totalPages > 1) {
    $pagination .= '<ul class="pagination justify-content-center">';
    if ($page > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="#" data-page="' . ($page - 1) . '">&laquo;</a></li>';
    }
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $page ? ' active' : '';
        $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
    }
    if ($page < $totalPages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="#" data-page="' . ($page + 1) . '">&raquo;</a></li>';
    }
    $pagination .= '</ul>';
}

// Return JSON response
echo json_encode([
    'html' => $html,
    'pagination' => $pagination,
    'totalUsers' => $totalUsers
]);
