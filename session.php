<?php
session_start();

if (!isset($_SESSION['accounts'])) {
    $_SESSION['accounts'] = [];
}
