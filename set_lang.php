<?php
session_start();
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'sw'])) {
    $_SESSION['lang'] = $_GET['lang'];
    echo json_encode(['status' => 'ok', 'lang' => $_GET['lang']]);
} else {
    echo json_encode(['status' => 'error']);
}