<?php
session_start();

/* حذف جميع بيانات الجلسة */
$_SESSION = [];

/* تدمير الجلسة */
session_destroy();

/* إعادة التوجيه لصفحة تسجيل الدخول */
header("Location: login.php");
exit;
