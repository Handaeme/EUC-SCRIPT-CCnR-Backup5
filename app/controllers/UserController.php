<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;

class UserController extends Controller {

    public function index() {
        if (!$this->isAdmin()) {
            // header("Location: index.php?controller=dashboard");
            // exit;
            // For now, assume if they can access this URL, check check isAdmin() logic roughly.
        }

        $userModel = new UserModel();
        $users = $userModel->getAll();
        
        $this->view('user/index', [
            'users' => array_slice($users, (max(1,intval($_GET['page']??1))-1)*10, 10),
            'currentPage' => max(1,intval($_GET['page']??1)),
            'totalPages' => max(1, ceil(count($users)/10)),
            'totalItems' => count($users),
            'perPage' => 10
        ]);
    }

    public function create() {
        if (!$this->isAdmin()) { header("Location: index.php"); exit; }
        $this->view('user/create');
    }

    public function store() {
        if (!$this->isAdmin()) { header("Location: index.php"); exit; }
        
        $data = [
            'userid' => $_POST['userid'],
            'fullname' => $_POST['fullname'],
            'password' => $_POST['password'], 
            'dept' => $_POST['dept'],
            'job_function' => $_POST['job_function'] ?? '',
            'divisi' => $_POST['divisi'] ?? '',
            'ldap' => isset($_POST['ldap']) ? 1 : 0,
            'group_name' => $_POST['group_name'] ?? '',
            'is_active' => 1
        ];

        // Basic validation
        if (empty($data['userid']) || empty($data['dept'])) {
            // Handle error
            header("Location: ?controller=user&action=create&error=Missing fields");
            exit;
        }

        $userModel = new UserModel();
        $res = $userModel->create($data);

        if ($res['status'] == 'success') {
            header("Location: ?controller=user&action=index&msg=User created");
        } else {
            header("Location: ?controller=user&action=create&error=" . urlencode($res['message']));
        }
    }

    public function edit() {
        if (!$this->isAdmin()) { header("Location: index.php"); exit; }
        $id = $_GET['id'] ?? null;
        if (!$id) { header("Location: ?controller=user&action=index"); exit; }

        $userModel = new UserModel();
        $user = $userModel->getById($id);

        if (!$user) { header("Location: ?controller=user&action=index&error=User not found"); exit; }
        
        // Pass as 'editUser' to avoid conflict with 'user' in header.php (which is session user)
        $this->view('user/edit', ['editUser' => $user]);
    }

    public function update() {
        if (!$this->isAdmin()) { header("Location: index.php"); exit; }
        
        $id = $_POST['original_userid'] ?? '';
        
        if (empty($id)) {
            header("Location: ?controller=user&action=index&error=Update Failed: User ID missing. Please try editing again.");
            exit;
        }

        $data = [
            'fullname' => $_POST['fullname'],
            'dept' => $_POST['dept'],
            'job_function' => $_POST['job_function'] ?? '',
            'divisi' => $_POST['divisi'] ?? '',
            'ldap' => isset($_POST['ldap']) ? 1 : 0,
            'group_name' => $_POST['group_name'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }

        $userModel = new UserModel();
        $res = $userModel->update($id, $data);

        if ($res['status'] == 'success') {
            header("Location: ?controller=user&action=index&msg=User updated");
        } else {
            header("Location: ?controller=user&action=edit&id=$id&error=" . urlencode($res['message']));
        }
    }

    public function delete() {
        if (!$this->isAdmin()) { header("Location: index.php"); exit; }
        $id = $_GET['id'];
        $userModel = new UserModel();
        $userModel->delete($id);
        header("Location: ?controller=user&action=index&msg=User deactivated");
    }

    // --- ROLE SWITCHING LOGIC ---
    public function switchRole() {
        // Only allow if current role is ADMIN OR if they are already impersonating (to switch between others?)
        // Or strictly strictly only if 'original_role' is ADMIN.
        
        // Security Check: You must be ADMIN to switch.
        // But if I already switched to Maker, I am now Maker.
        // So I need to rely on SESSION 'original_role'.

        session_start();
        $targetRole = $_GET['role'];
        
        $currentRole = $_SESSION['user']['dept'];
        $originalRole = $_SESSION['original_role'] ?? null;

        if ($currentRole === 'ADMIN' || $originalRole === 'ADMIN') {
            
            // 1. Save Original Role if not saved yet
            if (!$originalRole) {
                $_SESSION['original_role'] = $currentRole;
            }

            // 2. Switch
            $_SESSION['user']['dept'] = $targetRole;
            
            // Redirect to Dashboard (New Role View)
            header("Location: index.php");
            exit;
        } else {
            die("Unauthorized to switch User Role.");
        }
    }

    public function restoreRole() {
        session_start();
        if (isset($_SESSION['original_role'])) {
            $_SESSION['user']['dept'] = $_SESSION['original_role'];
            unset($_SESSION['original_role']);
            header("Location: index.php");
        } else {
            header("Location: index.php");
        }
    }

    private function isAdmin() {
        // Check session role
        if (session_status() == PHP_SESSION_NONE) session_start();
        $role = $_SESSION['user']['dept'] ?? '';
        return ($role === 'ADMIN' || (isset($_SESSION['original_role']) && $_SESSION['original_role'] === 'ADMIN'));
    }
}
