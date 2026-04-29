<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../Backend/login/Login.php");
    exit();
}

$user = $_SESSION['user'];
$userEmail = $user['EMAIL'] ?? $user['email'] ?? null;

if (!$userEmail) {
    header("Location: ../Backend/login/Login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

function buildAppRedirect(string $redirect): string
{
    $appRoot = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    if ($appRoot === '') {
        $appRoot = '/';
    }

    $parts = parse_url($redirect);
    $path = $parts['path'] ?? 'index.php';
    $path = preg_replace('#^(\./|\.\./)+#', '', $path);
    $path = ltrim($path, '/');
    if ($path === '') {
        $path = 'index.php';
    }

    $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

    return ($appRoot === '/' ? '' : $appRoot) . '/' . $path . $query;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$redirect = trim($_POST['redirect'] ?? '../index.php');

if ($redirect === '' || preg_match('/^https?:\/\//i', $redirect) || strpos($redirect, '//') === 0) {
    $redirect = '../index.php';
}

$redirect = buildAppRedirect($redirect);

if ($itemId > 0) {
    $deleteSql = "DELETE FROM cart_items WHERE id = ? AND user_email = ?";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$itemId, $userEmail]);
}

$separator = strpos($redirect, '?') !== false ? '&' : '?';
header("Location: " . $redirect . $separator . "removed=1");
exit();
