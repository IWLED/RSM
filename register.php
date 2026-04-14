<?php
require '../Files/Database/connection.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["username"]);
    $email     = trim($_POST["email"]);
    $password  = password_hash($_POST["password"], PASSWORD_DEFAULT);

    if ($full_name && $email && $_POST["password"]) {

        // تحقق هل البريد مستخدم فعليًا
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $message = "البريد الإلكتروني مستخدم مسبقًا";
        } else {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO users (full_name, email, password, is_active)
                     VALUES (?, ?, ?, 1)"
                );
                $stmt->execute([$full_name, $email, $password]);

                $message = "تم إنشاء الحساب بنجاح ✅";
            } catch (PDOException $e) {
                $message = "حدث خطأ غير متوقع، حاول لاحقًا";
            }
        }

    } else {
        $message = "يرجى تعبئة جميع الحقول";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إنشاء حساب</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">

<style>
*{
  box-sizing:border-box;
  font-family:'Cairo',sans-serif;
  font-weight:700;
}

body{
  background: linear-gradient(135deg, #0f172a, #1e293b);
  color:#0f172a;
  display:flex;
  justify-content:center;
  align-items:center;
  height:100vh;
  margin:0;
}

/* شريط علوي بسيط */
.top-bar{
  position:fixed;
  top:0;
  width:100%;
  height:3px;
  background:linear-gradient(90deg,#38bdf8,#6366f1,#38bdf8);
}

/* الكرت */
.card{
  width:720px;
  display:flex;
  flex-direction: row-reverse; /* الصورة أصبحت باليسار */
  background:#ffffff;
  border-radius:16px;
  overflow:hidden;
  box-shadow:0 25px 60px rgba(0,0,0,.25);
}

/* الصورة */
.card-image{
  width:45%;
  background:
    linear-gradient(180deg,rgba(15,23,42,.2),rgba(15,23,42,.85)),
    url("../Files/assets/images/resume2.jpg");
  background-size:cover;
  background-position:center;
  display:flex;
  align-items:flex-end;
  padding:30px;
}

.card-image span{
  color:#e5e7eb;
  font-size:15px;
  line-height:1.7;
}

/* الفورم */
.card-form{
  width:55%;
  padding:40px;
}

.card-form h2{
  margin-bottom:22px;
  color:#1e293b;
}

/* المدخلات */
input{
  width:100%;
  padding:13px;
  margin-bottom:14px;
  border-radius:8px;
  border:1px solid #cbd5f5;
  background:#f8fafc;
  color:#1e293b;
  transition:.3s;
}

input:focus{
  outline:none;
  border-color:#6366f1;
  box-shadow:0 0 0 2px rgba(99,102,241,.15);
}

/* الزر */
button{
  width:100%;
  padding:13px;
  border:none;
  border-radius:8px;
  background:linear-gradient(135deg,#6366f1,#3b82f6);
  color:#fff;
  cursor:pointer;
  transition:.3s;
}

button:hover{
  opacity:.9;
  transform:translateY(-1px);
}

/* الروابط */
a{
  display:block;
  margin-top:16px;
  font-size:13px;
  color:#475569;
  text-decoration:none;
}

a:hover{
  text-decoration:underline;
}

/* رسالة الخطأ */
.msg{
  margin-top:12px;
  font-size:13px;
  color:#ef4444;
}

/* موبايل */
@media (max-width:768px){
  .card{
    flex-direction:column;
    width:92%;
  }
  .card-image{
    width:100%;
    height:180px;
  }
  .card-form{
    width:100%;
  }
}
</style>
</head>

<body>

<div class="top-bar"></div>

<div class="card">

  <div class="card-image">
    <span>مرحبًا بك 👋<br>أنشئ حسابك خلال ثواني</span>
  </div>

  <div class="card-form">
    <h2>إنشاء حساب</h2>

    <form method="POST">
      <input type="text" name="username" placeholder="اسم المستخدم" required>
      <input type="email" name="email" placeholder="البريد الإلكتروني" required>
      <input type="password" name="password" placeholder="كلمة المرور" required>
      <button type="submit">تسجيل</button>
    </form>

    <div class="msg"><?= $message ?></div>
    <a href="login.php">لديك حساب؟ تسجيل دخول</a>
  </div>

</div>

</body>
</html>
